<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GeneralException;
use App\Helpers\ApiConstants;
use App\Helpers\ApiHelper;
use App\Http\Controllers\Controller;
use App\Services\DirectCheckout\DirectCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class DirectCheckoutController extends Controller
{
    /**
     * Initialize direct checkout
     */
    public function init(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'price_id' => 'required|string',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'use_type' => 'nullable|string|in:patient,clinic',
            ]);

            $service = new DirectCheckoutService();
            $checkoutData = $service->initializeCheckout(
                $validated['product_id'],
                $validated['price_id'],
                $validated['first_name'],
                $validated['last_name'],
                $validated['email'],
                $validated['use_type'] ?? null
            );

            return ApiHelper::validResponse(
                'Checkout initialized successfully',
                $checkoutData
            );
        } catch (ValidationException $e) {
            return ApiHelper::inputErrorResponse(
                $e->getMessage(),
                ApiConstants::VALIDATION_ERR_CODE,
                $request,
                $e
            );
        } catch (GeneralException $e) {
            return ApiHelper::problemResponse(
                $e->getMessage(),
                ApiConstants::BAD_REQ_ERR_CODE
            );
        } catch (Throwable $e) {
            // Log the full error for debugging but don't expose it to users
            Log::error('Direct checkout initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return ApiHelper::problemResponse(
                'An error occurred while initializing checkout. Please try again later.',
                ApiConstants::SERVER_ERR_CODE,
                $request,
                $e
            );
        }
    }

    /**
     * Initialize order sheet checkout (multiple products)
     */
    public function orderSheetInit(Request $request)
    {
        try {
            $validated = $request->validate([
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|integer|exists:products,id',
                'products.*.price_id' => 'required|string',
                'products.*.quantity' => 'nullable|integer|min:1',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:255',
                'account_number' => 'nullable|string|max:255',
                'location' => 'nullable|string|max:255',
                'shipping_address' => 'nullable|string',
                'additional_information' => 'nullable|string',
                'discount_code' => 'nullable|string',
            ]);

            $service = new DirectCheckoutService();
            $checkoutData = $service->initializeOrderSheetCheckout(
                $validated['products'],
                $validated['first_name'],
                $validated['last_name'],
                $validated['email'],
                $validated['phone'],
                $validated['account_number'] ?? '',
                $validated['location'] ?? '',
                $validated['shipping_address'] ?? null,
                $validated['additional_information'] ?? null,
                $validated['discount_code'] ?? null
            );

            return ApiHelper::validResponse(
                'Order sheet checkout initialized successfully',
                $checkoutData
            );
        } catch (ValidationException $e) {
            return ApiHelper::inputErrorResponse(
                $e->getMessage(),
                ApiConstants::VALIDATION_ERR_CODE,
                $request,
                $e
            );
        } catch (\Exception $e) {
            Log::error('Order sheet checkout initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return ApiHelper::problemResponse(
                $e->getMessage(),
                ApiConstants::BAD_REQ_ERR_CODE,
                $request,
                $e
            );
        } catch (Throwable $e) {
            Log::error('Order sheet checkout initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return ApiHelper::problemResponse(
                'An error occurred while initializing checkout. Please try again later.',
                ApiConstants::SERVER_ERR_CODE,
                $request,
                $e
            );
        }
    }

    /**
     * Checkout info by reference (order_type, products, totals, customer)
     */
    public function checkoutInfo(Request $request)
    {
        $request->validate([
            'reference' => 'required|string',
        ]);

        $reference = $request->get('reference');

        $payment = \App\Models\Payment::with(['paymentProducts.product', 'formSession'])
            ->where('reference', $reference)
            ->first();
        if (!$payment) {
            return ApiHelper::problemResponse('Payment not found', ApiConstants::NOT_FOUND_ERR_CODE);
        }

        $formSession = $payment->formSession;

        $orderType = $payment->order_type ?? ($formSession->metadata['order_type'] ?? 'regular');

        // Build base amounts from payment fields
        $subTotal = (float) ($payment->sub_total ?? 0);
        $shippingFee = (float) ($payment->shipping_fee ?? 0);
        $taxRate = (float) ($payment->tax_rate ?? 0);
        $taxAmount = (float) ($payment->tax_amount ?? 0);
        $discountCode = $payment->discount_code ?? null;
        $discountAmount = (float) ($payment->discount_amount ?? 0);
        $total = (float) ($payment->total ?? 0);
        // Order sheet checkouts always use USD
        $currency = ($orderType === 'order_sheet') ? 'USD' : ($payment->currency ?? 'USD');

        // Products: for order_sheet, use paymentProducts; otherwise try product from form session metadata
        $products = [];
        if ($orderType === 'order_sheet') {
            foreach ($payment->paymentProducts as $pp) {
                $product = $pp->product;
                if (!$product) {
                    continue;
                }
                // Price is stored as array in PaymentProduct
                $priceData = is_array($pp->price) ? $pp->price : ['value' => 0, 'currency' => $currency];
                $selectedPrice = [
                    'value' => (float) ($priceData['value'] ?? 0),
                    'currency' => $priceData['currency'] ?? $currency,
                    'frequency' => $priceData['frequency'] ?? null,
                    'unit' => $priceData['unit'] ?? null,
                ];
                $products[] = [
                    'product_id' => $product->id,
                    'product_slug' => $product->slug,
                    'name' => $product->name,
                    'selected_price' => $selectedPrice,
                    'quantity' => (int) ($pp->quantity ?? 1),
                ];
            }
        } else {
            // Regular: attempt single product from payment->product or metadata
            if ($payment->product) {
                $product = $payment->product;
                $selectedPrice = $product->selected_price ?? [
                    'value' => (float) ($payment->amount ?? 0),
                    'currency' => $currency,
                ];
                $products[] = [
                    'product_id' => $product->id,
                    'product_slug' => $product->slug,
                    'name' => $product->name,
                    'selected_price' => $selectedPrice,
                    'quantity' => 1,
                ];
            }
        }

        // Customer info (if available)
        $customer = [
            'first_name' => $formSession->metadata['raw']['firstName'] ?? $payment->first_name ?? null,
            'last_name' => $formSession->metadata['raw']['lastName'] ?? $payment->last_name ?? null,
            'email' => $formSession->metadata['raw']['email'] ?? $payment->email ?? null,
            'phone' => $formSession->metadata['raw']['phoneNumber'] ?? $payment->phone ?? null,
            'account_number' => $formSession->metadata['raw']['account_number'] ?? null,
            'location' => $formSession->metadata['raw']['location'] ?? null,
            'shipping_address' => $formSession->metadata['raw']['shipping_address'] ?? null,
            'additional_information' => $formSession->metadata['raw']['additional_information'] ?? null,
        ];

        $data = [
            'order_type' => $orderType,
            'reference' => $payment->reference,
            'status' => $payment->status,
            'products' => $products,
            'totals' => [
                'sub_total' => $subTotal,
                'shipping_fee' => $shippingFee,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'discount_code' => $discountCode,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'currency' => $currency,
            ],
            'customer' => $customer,
        ];

        return ApiHelper::validResponse('Checkout info retrieved', $data);
    }

    /**
     * Apply discount code to checkout
     */
    public function applyDiscount(Request $request)
    {
        try {
            $validated = $request->validate([
                'checkout_id' => 'required|string',
                'discount_code' => 'required|string',
            ]);

            $service = new DirectCheckoutService();
            $checkoutData = $service->applyDiscount(
                $validated['checkout_id'],
                $validated['discount_code']
            );

            return ApiHelper::validResponse(
                'Discount applied successfully',
                $checkoutData
            );
        } catch (ValidationException $e) {
            return ApiHelper::inputErrorResponse(
                $e->getMessage(),
                ApiConstants::VALIDATION_ERR_CODE,
                $request,
                $e
            );
        } catch (\Exception $e) {
            Log::error('Direct checkout discount application failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return ApiHelper::problemResponse(
                $e->getMessage(),
                ApiConstants::BAD_REQ_ERR_CODE,
                $request,
                $e
            );
        } catch (Throwable $e) {
            Log::error('Direct checkout discount application failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return ApiHelper::problemResponse(
                $e->getMessage(),
                ApiConstants::SERVER_ERR_CODE,
                $request,
                $e
            );
        }
    }

    /**
     * Process payment and redirect to Stripe
     */
    public function process(Request $request)
    {
        try {
            $validated = $request->validate([
                'checkout_id' => 'required|string',
                'recaptcha_token' => 'nullable|string',
            ]);

            // Determine checkout type from cache
            $cached = cache()->get("direct_checkout_{$validated['checkout_id']}");
            $isOrderSheet = $cached && ($cached['order_type'] ?? null) === 'order_sheet';

            $service = new DirectCheckoutService();
            $recaptchaToken = $validated['recaptcha_token'] ?? null;
            $result = $isOrderSheet
                ? $service->processOrderSheetPayment($validated['checkout_id'], $recaptchaToken)
                : $service->processPayment($validated['checkout_id'], $recaptchaToken);

            return ApiHelper::validResponse(
                'Payment processed successfully',
                $result
            );
        } catch (ValidationException $e) {
            return ApiHelper::inputErrorResponse(
                $e->getMessage(),
                ApiConstants::VALIDATION_ERR_CODE,
                $request,
                $e
            );
        } catch (GeneralException $e) {
            return ApiHelper::problemResponse(
                $e->getMessage(),
                ApiConstants::BAD_REQ_ERR_CODE
            );
        } catch (Throwable $e) {
            Log::error('Direct checkout payment processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return ApiHelper::problemResponse(
                'An error occurred while processing payment. Please try again later.',
                ApiConstants::SERVER_ERR_CODE,
                $request,
                $e
            );
        }
    }

    /**
     * Initialize order sheet checkout
     */
    public function initOrderSheet(Request $request)
    {
        try {
            $validated = $request->validate([
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|integer|exists:products,id',
                'products.*.price_id' => 'required|string',
                'products.*.quantity' => 'required|integer|min:1',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:255',
                'account_number' => 'nullable|string|max:255',
                'location' => 'nullable|string|max:255',
                'shipping_address' => 'nullable|string',
                'additional_information' => 'nullable|string',
                'discount_code' => 'nullable|string',
            ]);

            $service = new DirectCheckoutService();
            $checkoutData = $service->initializeOrderSheetCheckout(
                $validated['products'],
                $validated['first_name'],
                $validated['last_name'],
                $validated['email'],
                $validated['phone'],
                $validated['account_number'] ?? '',
                $validated['location'] ?? '',
                $validated['shipping_address'] ?? null,
                $validated['additional_information'] ?? null,
                $validated['discount_code'] ?? null
            );

            return ApiHelper::validResponse(
                'Order sheet checkout initialized successfully',
                $checkoutData
            );
        } catch (ValidationException $e) {
            return ApiHelper::inputErrorResponse(
                $e->getMessage(),
                ApiConstants::VALIDATION_ERR_CODE,
                $request,
                $e
            );
        } catch (GeneralException $e) {
            return ApiHelper::problemResponse(
                $e->getMessage(),
                ApiConstants::BAD_REQ_ERR_CODE
            );
        } catch (Throwable $e) {
            Log::error('Order sheet checkout initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return ApiHelper::problemResponse(
                'An error occurred while initializing checkout. Please try again later.',
                ApiConstants::SERVER_ERR_CODE,
                $request,
                $e
            );
        }
    }

    /**
     * Process order sheet payment and redirect to Stripe
     */
    public function processOrderSheet(Request $request)
    {
        try {
            $validated = $request->validate([
                'checkout_id' => 'required|string',
            ]);

            $service = new DirectCheckoutService();
            $result = $service->processOrderSheetPayment($validated['checkout_id']);

            return ApiHelper::validResponse(
                'Payment processed successfully',
                $result
            );
        } catch (ValidationException $e) {
            return ApiHelper::inputErrorResponse(
                $e->getMessage(),
                ApiConstants::VALIDATION_ERR_CODE,
                $request,
                $e
            );
        } catch (GeneralException $e) {
            return ApiHelper::problemResponse(
                $e->getMessage(),
                ApiConstants::BAD_REQ_ERR_CODE
            );
        } catch (Throwable $e) {
            Log::error('Order sheet payment processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return ApiHelper::problemResponse(
                'An error occurred while processing payment. Please try again later.',
                ApiConstants::SERVER_ERR_CODE,
                $request,
                $e
            );
        }
    }

    /**
     * Get product ID from payment reference
     */
    public function getProductFromPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'reference' => 'required|string',
            ]);

            $payment = \App\Models\Payment::where('reference', $validated['reference'])->first();

            if (!$payment) {
                return ApiHelper::problemResponse(
                    'Payment not found',
                    ApiConstants::NOT_FOUND_ERR_CODE
                );
            }

            // Get product from payment products relationship
            $product = $payment->products()->first();

            if (!$product) {
                // Try to get from form session metadata for direct checkout
                $formSession = $payment->formSession;
                if ($formSession && $formSession->isDirectCheckout()) {
                    $metadata = $formSession->metadata['raw'] ?? [];
                    $selectedProducts = $metadata['selectedProducts'] ?? [];
                    if (!empty($selectedProducts) && isset($selectedProducts[0]['product_id'])) {
                        $productId = $selectedProducts[0]['product_id'];
                        $product = \App\Models\Product::find($productId);
                    }
                }
            }

            if (!$product) {
                return ApiHelper::problemResponse(
                    'Product not found for this payment',
                    ApiConstants::NOT_FOUND_ERR_CODE
                );
            }

            return ApiHelper::validResponse(
                'Product retrieved successfully',
                [
                    'product_id' => $product->id,
                    'product_slug' => $product->slug,
                    'order_type' => $payment->order_type ?? 'regular',
                ]
            );
        } catch (ValidationException $e) {
            return ApiHelper::inputErrorResponse(
                $e->getMessage(),
                ApiConstants::VALIDATION_ERR_CODE,
                $request,
                $e
            );
        } catch (Throwable $e) {
            Log::error('Failed to get product from payment reference', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return ApiHelper::problemResponse(
                'An error occurred while retrieving product information.',
                ApiConstants::SERVER_ERR_CODE,
                $request,
                $e
            );
        }
    }

    /**
     * Get payment information including order type
     */
    public function getPaymentInfo(Request $request)
    {
        try {
            $validated = $request->validate([
                'reference' => 'required|string',
            ]);

            $payment = \App\Models\Payment::where('reference', $validated['reference'])->first();

            if (!$payment) {
                return ApiHelper::problemResponse(
                    'Payment not found',
                    ApiConstants::NOT_FOUND_ERR_CODE
                );
            }

            return ApiHelper::validResponse(
                'Payment information retrieved successfully',
                [
                    'order_type' => $payment->order_type ?? 'regular',
                    'reference' => $payment->reference,
                ]
            );
        } catch (ValidationException $e) {
            return ApiHelper::inputErrorResponse(
                $e->getMessage(),
                ApiConstants::VALIDATION_ERR_CODE,
                $request,
                $e
            );
        } catch (Throwable $e) {
            Log::error('Failed to get payment information', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return ApiHelper::problemResponse(
                'An error occurred while retrieving payment information.',
                ApiConstants::SERVER_ERR_CODE,
                $request,
                $e
            );
        }
    }

    /**
     * Calculate cart summary without creating a checkout
     */
    public function cartSummary(Request $request)
    {
        try {
            $validated = $request->validate([
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|integer|exists:products,id',
                'products.*.price_id' => 'required|string',
                'products.*.quantity' => 'required|integer|min:1',
                'discount_code' => 'nullable|string',
            ]);

            $service = new DirectCheckoutService();
            $summary = $service->calculateCartSummary(
                $validated['products'],
                $validated['discount_code'] ?? null
            );

            return ApiHelper::validResponse(
                'Cart summary calculated successfully',
                $summary
            );
        } catch (ValidationException $e) {
            return ApiHelper::inputErrorResponse(
                $e->getMessage(),
                ApiConstants::VALIDATION_ERR_CODE,
                $request,
                $e
            );
        } catch (\Exception $e) {
            Log::error('Cart summary calculation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return ApiHelper::problemResponse(
                $e->getMessage(),
                ApiConstants::BAD_REQ_ERR_CODE,
                $request,
                $e
            );
        } catch (Throwable $e) {
            Log::error('Cart summary calculation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return ApiHelper::problemResponse(
                'An error occurred while calculating cart summary. Please try again later.',
                ApiConstants::SERVER_ERR_CODE,
                $request,
                $e
            );
        }
    }
}
