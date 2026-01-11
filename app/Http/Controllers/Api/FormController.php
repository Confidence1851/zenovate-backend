<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GeneralException;
use App\Helpers\ApiConstants;
use App\Helpers\ApiHelper;
use App\Helpers\AppConstants;
use App\Helpers\StatusConstants;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\FormSession;
use App\Models\FormSessionActivity;
use App\Models\Product;
use App\Services\Form\Payment\ProcessorService;
use App\Services\Form\Session\StartService;
use App\Services\Form\Session\UpdateService;
use App\Services\Form\Session\WebhookService;
use App\Services\General\IpAddressService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class FormController extends Controller
{
    public function startSession(Request $request)
    {
        try {
            $session = (new StartService)->handle($request->all());

            return ApiHelper::validResponse(
                'Session started successfully',
                ['id' => $session->id]
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
            return $this->throwableError($e);
        }
    }

    public function updateSession(Request $request)
    {
        try {
            $data = (new UpdateService)->handle($request->all());
            if (! empty($p = $data['products'] ?? null)) {
                $data['products'] = ProductResource::collection($p);
            }

            return ApiHelper::validResponse(
                'Session updated successfully',
                $data
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
            return $this->throwableError($e);
        }
    }

    public function paymentCallback(Request $request, $payment_id, $status)
    {
        try {
            $request['payment_id'] = $payment_id;
            $request['status'] = ucfirst($status);

            // All payments now use form sessions (both form and direct checkouts)
            // The booking_type field on form session determines the redirect logic
            $url = (new ProcessorService)->callback($request->all());

            return redirect()->away($url);
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
            return $this->throwableError($e);
        }
    }

    public function productIndex()
    {
        try {

            return ApiHelper::validResponse(
                'Products retrieved successfully',
                ProductResource::collection(
                    Product::where('status', StatusConstants::ACTIVE)->with('category')->get()
                )
            );
        } catch (GeneralException $e) {
            return ApiHelper::problemResponse(
                $e->getMessage(),
                ApiConstants::BAD_REQ_ERR_CODE
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }

    public function orderSheetProducts(Request $request)
    {
        try {
            // Suppress any output that might interfere with JSON response
            // Clear output buffer to prevent PHP notices/warnings from corrupting JSON
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            ob_start();

            // Check if currency is passed as a query parameter (from URL-based routing)
            $requestedCurrency = $request->query('currency');
            if ($requestedCurrency && in_array(strtoupper($requestedCurrency), ['USD', 'CAD'])) {
                $currency = strtoupper($requestedCurrency);
            } else {
                // Fall back to config-based currency detection
                $useLocationPricing = config('order-sheet.use_location_pricing', false);
                $currency = config('order-sheet.currency', 'USD');

                if ($useLocationPricing) {
                    // Auto-detect currency from IP address: CAD for Canada, USD for others
                    $info = IpAddressService::info();
                    $countryCode = $info['countryCode'] ?? null;
                    $country = $info['country'] ?? null;

                    if (strtolower($countryCode ?? '') === 'ca' || strtolower($country ?? '') === 'canada') {
                        $currency = $info['currency'] ?? 'CAD';
                    } else {
                        $currency = $info['currency'] ?? 'USD';
                    }
                }
            }

            // Store currency in request for ProductResource to use
            $request->merge(['order_sheet_currency' => $currency]);

            $products = Product::where('status', StatusConstants::ACTIVE)
                ->where('enabled_for_order_sheet', true)
                ->with('category')
                ->orderBy('name', 'asc')
                ->get();

            // Clean output buffer before sending response
            ob_end_clean();

            $response = ApiHelper::validResponse(
                'Order sheet products retrieved successfully',
                ProductResource::collection($products)
            );

            return $response;
        } catch (GeneralException $e) {
            return ApiHelper::problemResponse(
                $e->getMessage(),
                ApiConstants::BAD_REQ_ERR_CODE
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }

    public function productsByCategories()
    {
        try {
            // Get all unique categories that have at least one active product
            $categories = \App\Models\ProductCategory::withCount(['products' => function ($query) {
                $query->where('status', StatusConstants::ACTIVE);
            }])
                ->having('products_count', '>', 0)
                ->orderBy('name', 'asc')
                ->get();

            $result = [];

            foreach ($categories as $category) {
                // Get first 4 ACTIVE products for this category
                $products = Product::where('status', StatusConstants::ACTIVE)
                    ->where('category_id', $category->id)
                    ->orderBy('id', 'asc')
                    ->limit(4)
                    ->with('category')
                    ->get();

                // Only add category if it has active products
                if ($products->count() > 0) {
                    $result[] = [
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'description' => $category->description,
                        'image_url' => $category->image_url,
                        'products_count' => $category->products_count,
                        'products' => ProductResource::collection($products),
                    ];
                }
            }

            return ApiHelper::validResponse(
                'Products by categories retrieved successfully',
                $result
            );
        } catch (GeneralException $e) {
            return ApiHelper::problemResponse(
                $e->getMessage(),
                ApiConstants::BAD_REQ_ERR_CODE
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }

    public function productInfo($id)
    {
        try {
            return ApiHelper::validResponse(
                'Product retrieved successfully',
                ProductResource::make(
                    Product::where('status', StatusConstants::ACTIVE)
                        ->where('slug', $id)
                        ->with('category')
                        ->firstOrFail()
                )
            );
        } catch (ModelNotFoundException $e) {
            return ApiHelper::problemResponse(
                "Sorry, we couldn't find the product you're looking for. It may have been removed or the link is incorrect.",
                ApiConstants::BAD_REQ_ERR_CODE
            );
        } catch (GeneralException $e) {
            return ApiHelper::problemResponse(
                $e->getMessage(),
                ApiConstants::BAD_REQ_ERR_CODE
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }

    public function info($id)
    {
        try {
            $form = FormSession::whereIn('status', [
                StatusConstants::PENDING,
                StatusConstants::PROCESSING,
            ])->find($id);
            if (empty($form)) {
                return ApiHelper::problemResponse(
                    'Invalid session',
                    ApiConstants::NOT_FOUND_ERR_CODE,
                );
            }

            $payments = $form->payments;
            $paid = false;
            $message = null;
            if (! empty($payments)) {
                $paid = $form->completedPayment()->exists();
                if ($paid) {
                    $message = 'Your payment was successful, kindly proceed to the next step.';
                } else {
                    $last_payment = $payments[0] ?? null;
                    if (! empty($last_payment)) {
                        if ($last_payment->status == StatusConstants::FAILED) {
                            $message = 'Failed to verify your payment attempt; Kindly try again!';
                        } elseif ($last_payment->status == StatusConstants::CANCELLED) {
                            $message = 'It appears you cancelled  your payment attempt; Kindly try again!';
                        }
                    }
                }
            }

            return ApiHelper::validResponse(
                'Products retrieved successfully',
                [
                    'id' => $form->id,
                    'formData' => $form->metadata['raw'] ?? null,
                    'payment' => [
                        'success' => $paid,
                        'attempts' => $payments->count(),
                        'message' => $message ?? null,
                    ],
                ]
            );
        } catch (GeneralException $e) {
            return ApiHelper::problemResponse(
                $e->getMessage(),
                ApiConstants::BAD_REQ_ERR_CODE
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }

    public function webhookHandler(Request $request)
    {
        try {
            $process = (new WebhookService)->handle($request->all());

            return ApiHelper::validResponse(
                'Webhook processed successfully',
            );
        } catch (GeneralException $e) {
            return ApiHelper::problemResponse(
                $e->getMessage(),
                ApiConstants::BAD_REQ_ERR_CODE
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }

    public function recreate(Request $request, $id)
    {
        try {
            $form = FormSession::where('user_id', $request->user()->id)->find($id);
            if (empty($form)) {
                return ApiHelper::problemResponse(
                    'Invalid session',
                    ApiConstants::NOT_FOUND_ERR_CODE,
                );
            }
            $session = FormSession::whereIn('status', [
                StatusConstants::PENDING,
                StatusConstants::PROCESSING,
            ])
                ->where('user_id', $request->user()->id)
                ->latest()
                ->first();

            if (empty($session)) {
                $session = (new StartService)->handle($request->all());
            }

            $meta = $session->metadata ?? [];
            $old_formdata = $form->metadata['raw'];
            // unset($old_formdata["selectedProducts"]);
            $meta['raw'] = $old_formdata;
            $session->update([
                'user_id' => $form->user_id,
                'metadata' => $meta,
            ]);

            FormSessionActivity::firstOrCreate([
                'form_session_id' => $session->id,
                'activity' => AppConstants::ACIVITY_RECREATE,
            ], [
                'user_id' => $session->user_id,
                'message' => 'Form session created from #'.$form->reference,
            ]);

            return ApiHelper::validResponse(
                'Session rereated successfully',
                [
                    'id' => $session->id,
                ]
            );
        } catch (GeneralException $e) {
            return ApiHelper::problemResponse(
                $e->getMessage(),
                ApiConstants::BAD_REQ_ERR_CODE
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }

    /**
     * Get checkout configuration (tax rates, shipping fees, etc.) for the order sheet
     */
    public function checkoutConfig(Request $request)
    {
        try {
            $requestedCurrency = $request->query('currency');
            if ($requestedCurrency && in_array(strtoupper($requestedCurrency), ['USD', 'CAD'])) {
                $currency = strtoupper($requestedCurrency);
            } else {
                $currency = config('order-sheet.currency', 'USD');
            }

            // Determine brand based on currency
            $brand = $currency === 'CAD' ? 'cccportal' : 'pinksky';

            // Get brand-specific tax rate or fall back to default
            $brandTaxRate = config("checkout.tax_rates_by_brand.{$brand}");
            $taxRate = $brandTaxRate !== null ? (float) $brandTaxRate : (float) config('checkout.tax_rate', 0);
            $shippingFee = (float) config('checkout.shipping_fee', 60);

            return ApiHelper::validResponse(
                'Checkout configuration retrieved',
                [
                    'currency' => $currency,
                    'brand' => $brand,
                    'tax_rate' => $taxRate,
                    'default_shipping_fee' => $shippingFee,
                    'free_shipping_threshold' => 1000,
                ]
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }
}
