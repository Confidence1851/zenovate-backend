<?php

namespace App\Services\General;

use App\Models\DiscountCode;
use Illuminate\Support\Facades\DB;

class DiscountCodeService
{
    /**
     * Validate discount code and return the model if valid
     * 
     * @param string $code
     * @return DiscountCode|null
     */
    public function validate(string $code): ?DiscountCode
    {
        $discountCode = DiscountCode::where('code', strtoupper(trim($code)))->first();

        if (!$discountCode) {
            return null;
        }

        if (!$discountCode->isValid()) {
            return null;
        }

        return $discountCode;
    }

    /**
     * Calculate discount amount based on subtotal and discount code
     * 
     * @param float $subtotal
     * @param DiscountCode $discountCode
     * @return float
     */
    public function calculateDiscount(float $subtotal, DiscountCode $discountCode): float
    {
        if ($discountCode->type === 'percentage') {
            // Percentage discount
            return round($subtotal * ($discountCode->value / 100), 2);
        } else {
            // Fixed amount discount
            return min($discountCode->value, $subtotal); // Don't discount more than subtotal
        }
    }

    /**
     * Apply discount to checkout data
     * 
     * @param array $checkoutData
     * @param string $code
     * @return array
     * @throws \Exception if code is invalid
     */
    public function applyDiscount(array $checkoutData, string $code): array
    {
        $discountCode = $this->validate($code);

        if (!$discountCode) {
            throw new \Exception('Invalid or expired discount code');
        }

        $subtotal = $checkoutData['sub_total'] ?? 0;
        $discountAmount = $this->calculateDiscount($subtotal, $discountCode);
        $shippingFee = $checkoutData['shipping_fee'] ?? 0;
        $total = max(0, $subtotal - $discountAmount + $shippingFee); // Ensure total doesn't go negative

        // Increment usage count (no need for nested transaction, increment() is atomic)
        $discountCode->incrementUsage();

        return array_merge($checkoutData, [
            'discount_code' => $discountCode->code,
            'discount_amount' => $discountAmount,
            'sub_total' => $subtotal,
            'total' => round($total, 2),
        ]);
    }
}

