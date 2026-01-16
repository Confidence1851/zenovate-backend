<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GeneralException;
use App\Helpers\ApiConstants;
use App\Helpers\ApiHelper;
use App\Http\Controllers\Controller;
use App\Models\FormSession;
use App\Services\DirectCheckout\DirectCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class DirectCheckoutController extends Controller
{
    /**
     * Initialize direct checkout (pure calculation, no form session creation)
     * Form session is created when user clicks "Proceed to Checkout"
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
                'source_path' => 'nullable|string|max:255',
            ]);

            $service = new DirectCheckoutService;
            
            // Calculate totals (pure calculation, no form session creation)
            $totals = $service->calculateDirectCheckoutTotals(
                $validated['product_id'],
                $validated['price_id'],
                null // no discount code on initial load
            );
            
            // Get product for display info
            $product = \App\Models\Product::findOrFail($validated['product_id']);
            $priceData = json_decode(\App\Helpers\Helper::decrypt($validated['price_id']), true);
            
            // Find or create user (for future form session creation)
            $user = $service->findOrCreateUserForCheckout(
                $validated['first_name'],
                $validated['last_name'],
                $validated['email']
            );
            
            // Get geo data
            $geoData = $service->getGeoDataForCheckout();
            
            // Return checkout info without form session
            $checkoutData = [
                'product_id' => $validated['product_id'],
                'price_id' => $validated['price_id'],
                'use_type' => $validated['use_type'] ?? null,
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                ],
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'selected_price' => $priceData['value'] ?? [],
                ],
                'sub_total' => $totals['sub_total'],
                'shipping_fee' => $totals['shipping_fee'],
                'tax_rate' => $totals['tax_rate'],
                'tax_amount' => $totals['tax_amount'],
                'discount_code' => null,
                'discount_amount' => 0,
                'total' => $totals['total'],
                'currency' => $geoData['currency'],
                'country_code' => $geoData['country_code'],
                'country' => $geoData['country'],
                'source_path' => $validated['source_path'] ?? '/products',
            ];

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
                'request' => $request->all(),
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
            $isPinksky = $request->get('source_path') && str_contains($request->get('source_path'), 'pinksky');

            $validated = $request->validate([
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|integer|exists:products,id',
                'products.*.price_id' => 'required|string',
                'products.*.quantity' => 'nullable|integer|min:1',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:255',
                'business_name' => ($isPinksky ? 'required' : 'nullable') . '|string|max:255',
                'medical_director_name' => ($isPinksky ? 'required' : 'nullable') . '|string|max:255',
                'account_number' => 'nullable|string|max:255',
                'location' => 'nullable|string|max:255',
                'shipping_address' => 'nullable|string',
                'additional_information' => 'nullable|string',
                'discount_code' => 'nullable|string',
                'currency' => 'nullable|string|in:USD,CAD',
                'source_path' => 'nullable|string|max:255',
                'ref' => 'nullable|string|max:255',
            ]);

            $service = new DirectCheckoutService;
            $checkoutData = $service->initializeOrderSheetCheckout(
                $validated['products'],
                $validated['first_name'],
                $validated['last_name'],
                $validated['email'],
                $validated['phone'],
                $validated['business_name'],
                $validated['medical_director_name'],
                $validated['account_number'] ?? '',
                $validated['location'] ?? '',
                $validated['shipping_address'] ?? null,
                $validated['additional_information'] ?? null,
                $validated['discount_code'] ?? null,
                $validated['currency'] ?? null,
                $validated['source_path'] ?? null,
                $validated['ref'] ?? null
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
                'request' => $request->all(),
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
                'request' => $request->all(),
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
     * Calculate order sheet totals with discount (no side effects)
     */
    public function calculateOrderSheetTotals(Request $request)
    {
        try {
            $validated = $request->validate([
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|integer|exists:products,id',
                'products.*.price_id' => 'required|string',
                'products.*.quantity' => 'nullable|integer|min:1',
                'discount_code' => 'nullable|string',
                'currency' => 'nullable|string|in:USD,CAD',
                'location' => 'nullable|string|max:255',
            ]);

            $service = new DirectCheckoutService;
            $totals = $service->calculateOrderSheetTotals(
                $validated['products'],
                $validated['discount_code'] ?? null,
                $validated['currency'] ?? null,
                $validated['location'] ?? null
            );

            return ApiHelper::validResponse(
                'Order sheet totals calculated successfully',
                $totals
            );
        } catch (ValidationException $e) {
            return ApiHelper::inputErrorResponse(
                $e->getMessage(),
                ApiConstants::VALIDATION_ERR_CODE,
                $request,
                $e
            );
        } catch (\Exception $e) {
            Log::error('Order sheet totals calculation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return ApiHelper::problemResponse(
                $e->getMessage(),
                ApiConstants::BAD_REQ_ERR_CODE,
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
        if (! $payment) {
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
        // Order sheet and cart checkouts always use USD
        $isMultiProduct = in_array($orderType, ['order_sheet', 'cart']);
        $currency = $isMultiProduct ? 'USD' : ($payment->currency ?? 'USD');

        // Products: for order_sheet and cart, use paymentProducts; for regular, get from formSession metadata
        $products = [];
        if ($isMultiProduct) {
            foreach ($payment->paymentProducts as $pp) {
                $product = $pp->product;
                if (! $product) {
                    continue;
                }
                // Price is stored as array in PaymentProduct
                $priceData = is_array($pp->price) ? $pp->price : ['value' => 0, 'currency' => $currency];
                $selectedPrice = [
                    'value' => (float) ($priceData['value'] ?? 0),
                    'currency' => $priceData['currency'] ?? $currency,
                    'frequency' => $priceData['frequency'] ?? null,
                    'unit' => $priceData['unit'] ?? null,
                    'display_name' => $priceData['display_name'] ?? null,
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
            // Regular direct checkout: get product from formSession metadata
            $metadata = $formSession->metadata ?? [];
            if (isset($metadata['product_id']) && isset($metadata['selected_price'])) {
                $product = \App\Models\Product::find($metadata['product_id']);
                if ($product) {
                    $selectedPrice = $metadata['selected_price'];
                    $products[] = [
                        'product_id' => $product->id,
                        'product_slug' => $product->slug,
                        'name' => $product->name,
                        'selected_price' => $selectedPrice,
                        'quantity' => 1,
                    ];
                }
            }
        }

        // Customer info (if available)
        $customer = [
            'first_name' => $formSession->metadata['raw']['firstName'] ?? $payment->first_name ?? null,
            'last_name' => $formSession->metadata['raw']['lastName'] ?? $payment->last_name ?? null,
            'email' => $formSession->metadata['raw']['email'] ?? $payment->email ?? null,
            'phone' => $formSession->metadata['raw']['phoneNumber'] ?? $payment->phone ?? null,
            'business_name' => $formSession->metadata['raw']['businessName'] ?? null,
            'medical_director_name' => $formSession->metadata['raw']['medicalDirectorName'] ?? null,
            'account_number' => $formSession->metadata['raw']['account_number'] ?? null,
            'location' => $formSession->metadata['raw']['location'] ?? null,
            'shipping_address' => $formSession->metadata['raw']['shipping_address'] ?? null,
            'additional_information' => $formSession->metadata['raw']['additional_information'] ?? null,
        ];

        // Get source path from formSession metadata for redirect purposes
        $sourcePath = $formSession->metadata['source_path'] ?? '/products';
        
        $data = [
            'order_type' => $orderType,
            'reference' => $payment->reference,
            'status' => $payment->status,
            'source_path' => $sourcePath,
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
     * Process payment and redirect to Stripe
     * Creates form session on-demand when user clicks "Proceed to Checkout"
     */
    public function process(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'price_id' => 'required|string',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'use_type' => 'nullable|string|in:patient,clinic',
                'discount_code' => 'nullable|string',
                'source_path' => 'nullable|string|max:255',
                'recaptcha_token' => 'nullable|string',
            ]);

            $service = new DirectCheckoutService;
            
            // Create form session NOW (when user clicks "Proceed to Checkout")
            $product = \App\Models\Product::findOrFail($validated['product_id']);
            $priceData = json_decode(\App\Helpers\Helper::decrypt($validated['price_id']), true);
            if (! $priceData || $priceData['product_id'] != $validated['product_id']) {
                throw new \Exception('Invalid price ID');
            }
            
            $user = $service->findOrCreateUserForCheckout(
                $validated['first_name'],
                $validated['last_name'],
                $validated['email']
            );
            
            // Use the existing initializeCheckout method which creates the form session
            $checkoutData = $service->initializeCheckout(
                $validated['product_id'],
                $validated['price_id'],
                $validated['first_name'],
                $validated['last_name'],
                $validated['email'],
                $validated['use_type'] ?? null,
                $validated['discount_code'] ?? null
            );
            
            $formSessionId = $checkoutData['form_session_id'];
            $recaptchaToken = $validated['recaptcha_token'] ?? null;

            // Process the payment
            $result = $service->processPayment($formSessionId, $recaptchaToken);

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
                'request' => $request->all(),
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

            $service = new DirectCheckoutService;
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
                'request' => $request->all(),
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
                'session_id' => 'required|string',
            ]);

            $service = new DirectCheckoutService;
            $result = $service->processOrderSheetPayment($validated['session_id']);

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
                'request' => $request->all(),
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

            if (! $payment) {
                return ApiHelper::problemResponse(
                    'Payment not found',
                    ApiConstants::NOT_FOUND_ERR_CODE
                );
            }

            // Get product from payment products relationship
            $product = $payment->products()->first();

            if (! $product) {
                // Try to get from form session metadata for direct checkout
                $formSession = $payment->formSession;
                if ($formSession && $formSession->isDirectCheckout()) {
                    $metadata = $formSession->metadata['raw'] ?? [];
                    $selectedProducts = $metadata['selectedProducts'] ?? [];
                    if (! empty($selectedProducts) && isset($selectedProducts[0]['product_id'])) {
                        $productId = $selectedProducts[0]['product_id'];
                        $product = \App\Models\Product::find($productId);
                    }
                }
            }

            if (! $product) {
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
                'request' => $request->all(),
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

            if (! $payment) {
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
                'request' => $request->all(),
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
     * Initialize cart checkout (multiple products from cart)
     */
    public function cartInit(Request $request)
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
                'ref' => 'nullable|string|max:255',
            ]);

            $service = new DirectCheckoutService;
            $checkoutData = $service->initializeCartCheckout(
                $validated['products'],
                $validated['first_name'],
                $validated['last_name'],
                $validated['email'],
                $validated['phone'],
                $validated['account_number'] ?? '',
                $validated['location'] ?? '',
                $validated['shipping_address'] ?? null,
                $validated['additional_information'] ?? null,
                $validated['discount_code'] ?? null,
                $validated['ref'] ?? null
            );

            // Remove internal flags before sending to frontend
            $checkoutData = $this->cleanCheckoutData($checkoutData);

            return ApiHelper::validResponse(
                'Cart checkout initialized successfully',
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
            Log::error('Cart checkout initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
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
     * Process cart payment and redirect to Stripe
     */
    public function processCart(Request $request)
    {
        try {
            $validated = $request->validate([
                'session_id' => 'required|string',
                'recaptcha_token' => 'nullable|string',
            ]);

            $service = new DirectCheckoutService;
            $recaptchaToken = $validated['recaptcha_token'] ?? null;
            $result = $service->processCartPayment($validated['session_id'], $recaptchaToken);

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
            Log::error('Cart payment processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
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

            $service = new DirectCheckoutService;
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
                'request' => $request->all(),
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
                'request' => $request->all(),
            ]);

            return ApiHelper::problemResponse(
                'An error occurred while calculating cart summary. Please try again later.',
                ApiConstants::SERVER_ERR_CODE,
                $request,
                $e
            );
        }
    }

    /**
     * Calculate totals for direct checkout with optional discount (no side effects)
     * Supports single product or multiple products
     */
    public function calculateTotals(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'nullable|integer|exists:products,id',
                'price_id' => 'nullable|string',
                'products' => 'nullable|array|min:1',
                'products.*.product_id' => 'required|integer|exists:products,id',
                'products.*.price_id' => 'required|string',
                'products.*.quantity' => 'nullable|integer|min:1',
                'discount_code' => 'nullable|string',
                'location' => 'nullable|string|max:255',
            ]);

            $service = new DirectCheckoutService;
            
            // Handle single product (direct checkout)
            if ($validated['product_id'] && $validated['price_id']) {
                $totals = $service->calculateDirectCheckoutTotals(
                    $validated['product_id'],
                    $validated['price_id'],
                    $validated['discount_code'] ?? null
                );
            } 
            // Handle multiple products (cart or order sheet)
            elseif ($validated['products']) {
                $totals = $service->calculateOrderSheetTotals(
                    $validated['products'],
                    $validated['discount_code'] ?? null,
                    null,
                    $validated['location'] ?? null
                );
            }
            else {
                throw new \Exception('Either product_id/price_id or products array is required');
            }

            return ApiHelper::validResponse(
                'Checkout totals calculated successfully',
                $totals
            );
        } catch (ValidationException $e) {
            return ApiHelper::inputErrorResponse(
                $e->getMessage(),
                ApiConstants::VALIDATION_ERR_CODE,
                $request,
                $e
            );
        } catch (\Exception $e) {
            // Check if it's a discount validation error (client-facing message)
            $message = $e->getMessage();
            if (strpos($message, 'Invalid or expired discount code') !== false) {
                return ApiHelper::problemResponse(
                    $message,
                    ApiConstants::VALIDATION_ERR_CODE
                );
            }

            Log::error('Checkout totals calculation failed', [
                'error' => $message,
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return ApiHelper::problemResponse(
                $message,
                ApiConstants::BAD_REQ_ERR_CODE,
                $request,
                $e
            );
        } catch (Throwable $e) {
            Log::error('Checkout totals calculation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return ApiHelper::problemResponse(
                'An error occurred while calculating totals. Please try again later.',
                ApiConstants::SERVER_ERR_CODE,
                $request,
                $e
            );
        }
    }

    /**
     * Remove internal flags from checkout data before sending to frontend
     */
    private function cleanCheckoutData($checkoutData)
    {
        // Remove any internal debugging or system fields that shouldn't be exposed
        if (is_array($checkoutData)) {
            unset($checkoutData['_internal']);
            unset($checkoutData['_debug']);
            unset($checkoutData['_system']);
        }
        return $checkoutData;
    }
}
