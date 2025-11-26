<?php

namespace App\Services\DirectCheckout;

use App\Helpers\AppConstants;
use App\Helpers\Helper;
use App\Helpers\StatusConstants;
use App\Models\FormSession;
use App\Models\Product;
use App\Models\User;
use App\Services\General\DiscountCodeService;
use App\Services\General\IpAddressService;
use App\Services\Form\Payment\ProcessorService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DirectCheckoutService
{
    /**
     * Initialize checkout for a product
     * Creates or finds user by email and creates a form session
     */
    public function initializeCheckout(
        int $productId,
        string $priceId,
        string $firstName,
        string $lastName,
        string $email,
        ?string $useType = null
    ): array {
        $product = Product::findOrFail($productId);

        // Decrypt price_id to get price information
        $priceData = json_decode(Helper::decrypt($priceId), true);
        if (!$priceData || $priceData['product_id'] != $productId) {
            throw new \Exception('Invalid price ID');
        }

        $selectedPrice = $priceData['value'];
        $product->selected_price = $selectedPrice;

        // Find or create user by email
        $user = $this->findOrCreateUser($firstName, $lastName, $email);

        // Create form session for direct checkout
        $formSession = $this->createFormSession($user, $product, $priceId, $selectedPrice, $useType);

        // Get location-based currency and country
        $geoData = $this->getGeoData();

        // Calculate totals
        $subTotal = $selectedPrice['value'];
        $shippingFee = $this->getShippingFee($product);
        $taxRate = $this->getTaxRate($product);
        $taxAmount = $subTotal * ($taxRate / 100);

        // Initial total (will be recalculated after discount if applied)
        $total = $subTotal + $shippingFee + $taxAmount;

        // Create checkout data
        $checkoutId = 'checkout_' . uniqid();
        $checkoutData = [
            'checkout_id' => $checkoutId,
            'form_session_id' => $formSession->id,
            'product_id' => $productId,
            'price_id' => $priceId,
            'use_type' => $useType, // 'patient' or 'clinic'
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
            ],
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'selected_price' => $selectedPrice,
            ],
            'sub_total' => round($subTotal, 2),
            'shipping_fee' => round($shippingFee, 2),
            'tax_rate' => $taxRate,
            'tax_amount' => round($taxAmount, 2),
            'discount_code' => null,
            'discount_amount' => 0,
            'total' => round($total, 2),
            'currency' => $geoData['currency'],
            'country_code' => $geoData['country_code'],
            'country' => $geoData['country'],
        ];

        // Store in cache for 30 minutes
        cache()->put("direct_checkout_{$checkoutId}", $checkoutData, now()->addMinutes(30));

        return $checkoutData;
    }

    /**
     * Find or create user by email
     */
    private function findOrCreateUser(string $firstName, string $lastName, string $email): User
    {
        $user = User::where('email', strtolower(trim($email)))->first();

        if ($user) {
            // Optionally update name if user exists
            // $user->update(['first_name' => $firstName, 'last_name' => $lastName]);
            return $user;
        }

        // Create new user with random password (they can reset if needed)
        return User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => strtolower(trim($email)),
            'password' => Hash::make(Str::random(16)), // Random password
            'role' => 'user',
            'team' => AppConstants::ROLE_CUSTOMER,
        ]);
    }

    /**
     * Create form session for direct checkout
     */
    private function createFormSession(
        User $user,
        Product $product,
        string $priceId,
        array $selectedPrice,
        ?string $useType
    ): FormSession {
        return FormSession::create([
            'status' => StatusConstants::PENDING,
            'booking_type' => 'direct', // Identify as direct checkout
            'reference' => $this->generateReference(),
            'user_id' => $user->id,
            'metadata' => [
                'user_agent' => request()->userAgent(),
                'location' => null,
                'raw' => [
                    'firstName' => $user->first_name,
                    'lastName' => $user->last_name,
                    'email' => $user->email,
                    'phoneNumber' => $user->phone ?? null,
                    'useType' => $useType,
                    'selectedProducts' => [
                        [
                            'product_id' => $product->id,
                            'price_id' => $priceId,
                        ]
                    ],
                ],
            ],
        ]);
    }

    /**
     * Generate unique reference for form session
     */
    private function generateReference(): string
    {
        $code = "DC-" . Helper::getRandomToken(6, true); // DC = Direct Checkout
        $check = FormSession::where("reference", $code)->exists();
        if ($check) {
            return $this->generateReference();
        }
        return $code;
    }

    /**
     * Apply discount code to checkout
     */
    public function applyDiscount(string $checkoutId, string $discountCode): array
    {
        $checkoutData = cache()->get("direct_checkout_{$checkoutId}");

        if (!$checkoutData) {
            throw new \Exception('Checkout session expired or not found');
        }

        // Apply discount using existing DiscountCodeService
        $discountService = new DiscountCodeService();
        $checkoutData = $discountService->applyDiscount($checkoutData, $discountCode);

        // Recalculate tax on discounted subtotal and update total
        $discountedSubtotal = $checkoutData['sub_total'] - $checkoutData['discount_amount'];
        $taxAmount = $discountedSubtotal * ($checkoutData['tax_rate'] / 100);
        $checkoutData['tax_amount'] = round($taxAmount, 2);
        $checkoutData['total'] = round($discountedSubtotal + $checkoutData['shipping_fee'] + $taxAmount, 2);

        // Update cache
        cache()->put("direct_checkout_{$checkoutId}", $checkoutData, now()->addMinutes(30));

        return $checkoutData;
    }

    /**
     * Process payment using form session flow
     */
    public function processPayment(string $checkoutId): array
    {
        $checkoutData = cache()->get("direct_checkout_{$checkoutId}");

        if (!$checkoutData) {
            throw new \Exception('Checkout session expired or not found');
        }

        // Get form session
        $formSession = FormSession::findOrFail($checkoutData['form_session_id']);

        // Update form session metadata with discount info if applied
        $metadata = $formSession->metadata;
        $metadata['raw']['discount_code'] = $checkoutData['discount_code'];
        $metadata['raw']['discount_amount'] = $checkoutData['discount_amount'];
        $formSession->update(['metadata' => $metadata]);

        // Get product with selected price
        $product = Product::findOrFail($checkoutData['product_id']);
        $product->selected_price = $checkoutData['product']['selected_price'];

        // Prepare data for ProcessorService (same format as form checkout)
        $paymentData = [
            'sub_total' => $checkoutData['sub_total'],
            'currency' => $checkoutData['currency'],
            'total' => $checkoutData['total'],
            'shipping_fee' => $checkoutData['shipping_fee'],
            'tax_rate' => $checkoutData['tax_rate'],
            'tax_amount' => $checkoutData['tax_amount'],
            'country_code' => $checkoutData['country_code'],
            'discount_code' => $checkoutData['discount_code'],
            'discount_amount' => $checkoutData['discount_amount'],
            'products' => collect([$product]),
        ];

        // Use existing ProcessorService to create payment
        $result = (new ProcessorService())->initiate($formSession, $paymentData);

        // Clear checkout cache
        cache()->forget("direct_checkout_{$checkoutId}");

        return $result;
    }

    /**
     * Get shipping fee (product-specific or global)
     */
    private function getShippingFee(Product $product): float
    {
        // Check product-specific shipping fee first
        if ($product->shipping_fee !== null) {
            return (float) $product->shipping_fee;
        }

        // Fallback to global config
        return (float) config('checkout.shipping_fee', env('CHECKOUT_SHIPPING_FEE', 60));
    }

    /**
     * Get tax rate (product-specific or global)
     */
    private function getTaxRate(Product $product): float
    {
        // Check product-specific tax rate first
        if ($product->tax_rate !== null) {
            return (float) $product->tax_rate;
        }

        // Fallback to global config
        return (float) config('checkout.tax_rate', env('CHECKOUT_TAX_RATE', 0));
    }

    /**
     * Get geo data (currency, country)
     */
    private function getGeoData(): array
    {
        $info = IpAddressService::info();
        $currency = $info["currency"] ?? "CAD";
        $countryCode = $info["countryCode"] ?? "CA";
        $country = $info["country"] ?? "Canada";

        return [
            'currency' => $currency,
            'country_code' => $countryCode,
            'country' => $country,
        ];
    }
}
