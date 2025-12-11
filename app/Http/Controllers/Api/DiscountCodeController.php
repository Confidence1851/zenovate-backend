<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiConstants;
use App\Helpers\ApiHelper;
use App\Http\Controllers\Controller;
use App\Services\General\DiscountCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class DiscountCodeController extends Controller
{
    /**
     * Validate discount code and calculate discount amount
     */
    public function validateCode(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string',
                'subtotal' => 'required|numeric|min:0',
            ]);

            $discountService = new DiscountCodeService();
            $discountCode = $discountService->validate($validated['code']);

            if (!$discountCode) {
                return ApiHelper::problemResponse(
                    'Invalid or expired discount code',
                    ApiConstants::BAD_REQ_ERR_CODE,
                    $request
                );
            }

            $discountAmount = $discountService->calculateDiscount(
                $validated['subtotal'],
                $discountCode
            );

            return ApiHelper::validResponse(
                'Discount code is valid',
                [
                    'code' => $discountCode->code,
                    'type' => $discountCode->type,
                    'value' => $discountCode->value,
                    'discount_amount' => $discountAmount,
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
            Log::error('Discount code validation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return ApiHelper::problemResponse(
                'An error occurred while validating discount code',
                ApiConstants::SERVER_ERR_CODE,
                $request,
                $e
            );
        }
    }
}

