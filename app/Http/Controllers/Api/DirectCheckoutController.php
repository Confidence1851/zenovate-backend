<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GeneralException;
use App\Helpers\ApiConstants;
use App\Helpers\ApiHelper;
use App\Http\Controllers\Controller;
use App\Services\DirectCheckout\DirectCheckoutService;
use Illuminate\Http\Request;
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
            return ApiHelper::problemResponse(
                $e->getMessage() ?: 'An error occurred while initializing checkout',
                ApiConstants::SERVER_ERR_CODE
            );
        }
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
            return ApiHelper::problemResponse(
                $e->getMessage(),
                ApiConstants::BAD_REQ_ERR_CODE
            );
        } catch (Throwable $e) {
            return ApiHelper::problemResponse(
                'An error occurred while applying discount',
                ApiConstants::SERVER_ERR_CODE
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
            ]);
            
            $service = new DirectCheckoutService();
            $result = $service->processPayment($validated['checkout_id']);
            
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
            return ApiHelper::problemResponse(
                $e->getMessage() ?: 'An error occurred while processing payment',
                ApiConstants::SERVER_ERR_CODE
            );
        }
    }
}

