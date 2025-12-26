<?php

namespace App\Services\DirectCheckout;

use App\Helpers\AppConstants;
use App\Helpers\Helper;
use App\Helpers\StatusConstants;
use App\Models\FormSession;
use App\Models\Product;
use App\Models\User;
use App\Services\Form\Payment\ProcessorService;
use App\Services\General\DiscountCodeService;
use App\Services\General\IpAddressService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
        if (! $priceData || $priceData['product_id'] != $productId) {
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
        cache()->put("direct_checkout_{$checkoutId}", $checkoutData, now()->addHours(2));

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
                        ],
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
        $code = 'DC-' . Helper::getRandomToken(6, true); // DC = Direct Checkout
        $check = FormSession::where('reference', $code)->exists();
        if ($check) {
            return $this->generateReference();
        }

        return $code;
    }

    /**
     * Verify reCAPTCHA token
     */
    private function verifyRecaptcha(?string $recaptchaToken): void
    {
        if (! $recaptchaToken) {
            throw ValidationException::withMessages([
                'recaptcha_token' => ['reCAPTCHA token is required'],
            ]);
        }

        $recaptchaSecret = config('services.recaptcha.secret');
        if (! $recaptchaSecret) {
            Log::warning('reCAPTCHA secret key not configured');
            throw new \Exception('reCAPTCHA verification is not configured');
        }

        $recaptchaResponse = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $recaptchaSecret,
            'response' => $recaptchaToken,
            'remoteip' => request()->ip(),
        ]);

        $recaptchaResult = $recaptchaResponse->json();

        if (! $recaptchaResult['success'] || ($recaptchaResult['score'] ?? 0) < 0.5) {
            Log::warning('reCAPTCHA verification failed', [
                'result' => $recaptchaResult,
                'ip' => request()->ip(),
            ]);
            throw ValidationException::withMessages([
                'recaptcha_token' => ['reCAPTCHA verification failed. Please try again.'],
            ]);
        }
    }

    /**
     * Process payment using form session flow
     */
    public function processPayment(string $checkoutId, ?string $recaptchaToken = null): array
    {
        // Verify reCAPTCHA if token is provided
        if ($recaptchaToken) {
            $this->verifyRecaptcha($recaptchaToken);
        }

        $checkoutData = cache()->get("direct_checkout_{$checkoutId}");

        // If not in cache and this is a resumed payment (ref_*), rebuild from database
        if (! $checkoutData && str_starts_with($checkoutId, 'ref_')) {
            $ref = substr($checkoutId, 4); // Remove 'ref_' prefix
            $existingPayment = \App\Models\Payment::where('reference', $ref)->first();
            if (! $existingPayment) {
                throw new \Exception('Checkout session expired or not found');
            }

            // Rebuild checkout data from payment record
            $paymentProducts = $existingPayment->paymentProducts()->with('product')->get();
            $productsData = $paymentProducts->map(function ($paymentProduct) {
                $rawPrice = $paymentProduct->getOriginal('price');
                if (is_array($rawPrice)) {
                    $price = $rawPrice;
                } elseif (is_string($rawPrice)) {
                    $decoded = json_decode($rawPrice, true);
                    if (is_array($decoded)) {
                        $price = $decoded;
                    } else {
                        $price = [];
                    }
                } else {
                    $price = [];
                }
                return [
                     'product_id' => $paymentProduct->product_id,
                     'id' => $paymentProduct->product_id,
                     'name' => $paymentProduct->product->name ?? 'Unknown Product',
                     'quantity' => $paymentProduct->quantity,
                     'selected_price' => $price,
                 ];
            });

            $firstProduct = $productsData->first();
             $checkoutData = [
                 'checkout_id' => $checkoutId,
                 'form_session_id' => $existingPayment->form_session_id,
                 'order_type' => 'regular',
                 'product_id' => $firstProduct['id'] ?? null,
                 'product' => [
                     'id' => $firstProduct['id'] ?? null,
                     'name' => $firstProduct['name'] ?? null,
                     'selected_price' => $firstProduct['selected_price'] ?? null,
                 ],
                'sub_total' => $existingPayment->sub_total,
                'discount_code' => $existingPayment->discount_code,
                'discount_amount' => $existingPayment->discount_amount ?? 0,
                'tax_rate' => $existingPayment->tax_rate ?? 0,
                'tax_amount' => $existingPayment->tax_amount ?? 0,
                'shipping_fee' => $existingPayment->shipping_fee,
                'total' => $existingPayment->total,
                'currency' => $existingPayment->currency,
                'country_code' => 'US',
                'country' => 'United States',
            ];
        }

        if (! $checkoutData) {
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
        $result = (new ProcessorService)->initiate($formSession, $paymentData);

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
        return (float) config('checkout.shipping_fee', 60);
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
        return (float) config('checkout.tax_rate', 0);
    }

    /**
     * Get geo data (currency, country)
     */
    private function getGeoData(): array
    {
        $info = IpAddressService::info();
        $countryCode = $info['countryCode'] ?? null;
        $country = $info['country'] ?? null;

        // Default to USD if location is not Canada
        if (strtolower($countryCode ?? '') === 'ca' || strtolower($country ?? '') === 'canada') {
            $currency = $info['currency'] ?? 'CAD';
            $countryCode = $countryCode ?? 'CA';
            $country = $country ?? 'Canada';
        } else {
            $currency = $info['currency'] ?? 'USD';
            $countryCode = $countryCode ?? 'US';
            $country = $country ?? 'United States';
        }

        return [
            'currency' => $currency,
            'country_code' => $countryCode,
            'country' => $country,
        ];
    }

    /**
     * Get geo data from location field or IP address
     * For order sheets, use location field to determine currency (CAD for Canada, USD otherwise)
     */
    private function getGeoDataFromLocation(?string $location = null): array
    {
        // If location field is provided, check if it contains "canada" or "ca"
        if ($location) {
            $locationLower = strtolower($location);
            if (strpos($locationLower, 'canada') !== false || strpos($locationLower, ', ca') !== false || strpos($locationLower, ' ca') !== false) {
                return [
                    'currency' => 'CAD',
                    'country_code' => 'CA',
                    'country' => 'Canada',
                ];
            }
        }

        // Fall back to IP-based detection
        return $this->getGeoData();
    }

    /**
     * Calculate shipping fee for order sheet (single fee for the entire order)
     * Rules:
     * - Orders >= $1000: free shipping
     * - Orders < $1000: flat $60 (or configured default) in USD
     * Note: matches frontend order sheet logic.
     */
    private function calculateOrderSheetShippingFee(float $subTotal, float $defaultShippingFee): float
    {
        $freeShippingThreshold = 1000.0;
        if ($subTotal >= $freeShippingThreshold) {
            return 0;
        }

        return $defaultShippingFee;
    }

    /**
     * Calculate order sheet totals with discount (no form session creation)
     * Used for discount preview before checkout
     */
    public function calculateOrderSheetTotals(
        array $products,
        ?string $discountCode = null,
        ?string $currency = null,
        ?string $location = null
    ): array {
        // Load all products and calculate totals
        $productModels = [];
        $subTotal = 0;
        $totalTax = 0;
        $defaultShippingFee = (float) config('checkout.shipping_fee', 60);

        foreach ($products as $productData) {
            $product = Product::findOrFail($productData['product_id']);

            // Decrypt price_id to get price information
            $priceData = json_decode(Helper::decrypt($productData['price_id']), true);
            if (! $priceData || $priceData['product_id'] != $product->id) {
                throw new \Exception("Invalid price ID for product {$product->id}");
            }

            $selectedPrice = $priceData['value'];
            $product->selected_price = $selectedPrice;
            $quantity = (int) ($productData['quantity'] ?? 1);

            $lineTotal = $selectedPrice['value'] * $quantity;
            $subTotal += $lineTotal;

            // Calculate tax for this product line
            $taxRate = $this->getTaxRate($product);
            $totalTax += $lineTotal * ($taxRate / 100);

            $productModels[] = [
                'product' => $product,
                'price_id' => $productData['price_id'],
                'quantity' => $quantity,
                'selected_price' => $selectedPrice,
            ];
        }

        // Get geo data for currency
        $geoData = $this->getGeoDataFromLocation($location);
        if ($currency && in_array(strtoupper($currency), ['USD', 'CAD'])) {
            $geoData = [
                'currency' => strtoupper($currency),
                'country_code' => strtoupper($currency) === 'CAD' ? 'CA' : 'US',
                'country' => strtoupper($currency) === 'CAD' ? 'Canada' : 'United States',
            ];
        }

        // Calculate shipping
        $shippingFee = $this->calculateOrderSheetShippingFee($subTotal, $defaultShippingFee);

        // Apply discount if provided
        $discountAmount = 0;
        if ($discountCode) {
            $discountService = new DiscountCodeService;
            $discountModel = $discountService->validate($discountCode);
            if ($discountModel) {
                $discountAmount = $discountService->calculateDiscount($subTotal, $discountModel);
            }
        }

        // Apply discount to subtotal only
        $discountedSubtotal = max(0, $subTotal - $discountAmount);

        // If discount is 100% (discounted subtotal is 0), set shipping fee to 0
        if ($discountedSubtotal == 0 && $discountAmount > 0) {
            $shippingFee = 0;
        }

        // Calculate tax on discounted subtotal only
        $averageTaxRate = $subTotal > 0 ? ($totalTax / $subTotal) * 100 : 0;
        $taxAmount = $discountedSubtotal * ($averageTaxRate / 100);

        $total = $discountedSubtotal + $shippingFee + $taxAmount;

        return [
            'products' => array_map(function ($item) {
                return [
                    'product_id' => $item['product']->id,
                    'price_id' => $item['price_id'],
                    'quantity' => $item['quantity'],
                    'selected_price' => $item['selected_price'],
                ];
            }, $productModels),
            'sub_total' => round($subTotal, 2),
            'discount_code' => $discountCode,
            'discount_amount' => round($discountAmount, 2),
            'tax_rate' => round($averageTaxRate, 2),
            'tax_amount' => round($taxAmount, 2),
            'shipping_fee' => round($shippingFee, 2),
            'total' => round($total, 2),
            'currency' => $geoData['currency'],
            'country_code' => $geoData['country_code'],
            'country' => $geoData['country'],
        ];
    }

    /**
     * Initialize order sheet checkout with multiple products
     * Creates form session when proceeding to actual checkout
     * If ref is provided, it's cleared and new checkout is recalculated with new parameters
     */
    public function initializeOrderSheetCheckout(
        array $products, // [{product_id, price_id, quantity}, ...]
        string $firstName,
        string $lastName,
        string $email,
        string $phone,
        string $accountNumber,
        string $location,
        ?string $shippingAddress = null,
        ?string $additionalInformation = null,
        ?string $discountCode = null,
        ?string $currency = null,
        ?string $sourcePath = null,
        ?string $ref = null
    ): array {
        // If ref is provided, clear the old cached checkout and recalculate with new params
        // This ensures product/discount changes are applied
        if ($ref) {
            $checkoutId = 'ref_' . $ref;
            cache()->forget("direct_checkout_{$checkoutId}");
        }
        // Create idempotency key to prevent duplicate orders from double-submissions
        $idempotencyKey = 'order_sheet_' . md5(json_encode([
            'email' => $email,
            'products' => $products,
            'discount_code' => $discountCode,
        ]));

        // Check if we already processed this exact request in the last 5 minutes
        $existingCheckout = cache()->get($idempotencyKey);
        if ($existingCheckout) {
            return $existingCheckout;
        }

        // Find or create user by email
        $user = $this->findOrCreateUser($firstName, $lastName, $email);

        // Use passed currency if provided, otherwise use location field for currency detection
        if ($currency && in_array(strtoupper($currency), ['USD', 'CAD'])) {
            $geoData = [
                'currency' => strtoupper($currency),
                'country_code' => strtoupper($currency) === 'CAD' ? 'CA' : 'US',
                'country' => strtoupper($currency) === 'CAD' ? 'Canada' : 'United States',
            ];
        } else {
            $geoData = $this->getGeoDataFromLocation($location);
        }

        // Load all products and calculate totals
        $productModels = [];
        $subTotal = 0;
        $totalTax = 0;
        $defaultShippingFee = (float) config('checkout.shipping_fee', 60);

        foreach ($products as $productData) {
            $product = Product::findOrFail($productData['product_id']);

            // Decrypt price_id to get price information
            $priceData = json_decode(Helper::decrypt($productData['price_id']), true);
            if (! $priceData || $priceData['product_id'] != $product->id) {
                throw new \Exception("Invalid price ID for product {$product->id}");
            }

            $selectedPrice = $priceData['value'];
            $product->selected_price = $selectedPrice;
            $quantity = (int) ($productData['quantity'] ?? 1);

            $lineTotal = $selectedPrice['value'] * $quantity;
            $subTotal += $lineTotal;

            // Calculate tax for this product line
            $taxRate = $this->getTaxRate($product);
            $totalTax += $lineTotal * ($taxRate / 100);

            $productModels[] = [
                'product' => $product,
                'price_id' => $productData['price_id'],
                'quantity' => $quantity,
                'selected_price' => $selectedPrice,
            ];
        }

        // Calculate shipping using order-sheet rules
        $shippingFee = $this->calculateOrderSheetShippingFee(
            $subTotal,
            $defaultShippingFee
        );

        // Apply discount if provided (to subtotal only, not shipping)
        $discountAmount = 0;
        if ($discountCode) {
            $discountService = new DiscountCodeService;
            $discountModel = $discountService->validate($discountCode);
            if (! $discountModel) {
                throw new \Exception('Invalid discount code');
            }
            // Calculate discount on subtotal only
            $discountAmount = $discountService->calculateDiscount($subTotal, $discountModel);
        }

        // Apply discount to subtotal only
        $discountedSubtotal = max(0, $subTotal - $discountAmount);

        // If discount is 100% (discounted subtotal is 0), set shipping fee to 0
        if ($discountedSubtotal == 0 && $discountAmount > 0) {
            $shippingFee = 0;
        }

        // Calculate tax on discounted subtotal only (not including shipping)
        $averageTaxRate = $subTotal > 0 ? ($totalTax / $subTotal) * 100 : 0;
        $taxAmount = $discountedSubtotal * ($averageTaxRate / 100);

        $total = $discountedSubtotal + $shippingFee + $taxAmount;

        // Create a new form session for this checkout (creates a new order each time)
        $formSession = $this->createOrderSheetFormSession(
            $user,
            $productModels,
            $firstName,
            $lastName,
            $email,
            $phone,
            $accountNumber,
            $location,
            $shippingAddress,
            $additionalInformation,
            $sourcePath,
            $geoData['currency']
        );

        // Create checkout data
        $checkoutId = 'order_sheet_' . uniqid();

        // Determine redirect path: use source_path if provided, otherwise default based on currency
        $redirectPath = $sourcePath ?? ($geoData['currency'] === 'CAD' ? '/cccportal/order' : '/pinksky/order');

        $checkoutData = [
            'checkout_id' => $checkoutId,
            'form_session_id' => $formSession->id,
            'order_type' => 'order_sheet',
            'source_path' => $redirectPath,
            'products' => array_map(function ($item) {
                return [
                    'product_id' => $item['product']->id,
                    'price_id' => $item['price_id'],
                    'quantity' => $item['quantity'],
                    'selected_price' => $item['selected_price'],
                ];
            }, $productModels),
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
            ],
            'customer_info' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'account_number' => $accountNumber,
                'location' => $location,
                'shipping_address' => $shippingAddress,
                'additional_information' => $additionalInformation,
            ],
            'sub_total' => round($subTotal, 2),
            'discount_code' => $discountCode,
            'discount_amount' => round($discountAmount, 2),
            'tax_rate' => round($averageTaxRate, 2),
            'tax_amount' => round($taxAmount, 2),
            'shipping_fee' => round($shippingFee, 2),
            'total' => round($total, 2),
            'currency' => $geoData['currency'],
            'country_code' => $geoData['country_code'],
            'country' => $geoData['country'],
        ];

        // Store in cache for 30 minutes
        cache()->put("direct_checkout_{$checkoutId}", $checkoutData, now()->addHours(2));

        // Cache the checkout result to prevent duplicate orders from double-submissions (5 minutes)
        cache()->put($idempotencyKey, $checkoutData, now()->addHours(1));

        return $checkoutData;
    }

    /**
     * Create form session for order sheet checkout
     */
    private function createOrderSheetFormSession(
        User $user,
        array $productModels,
        string $firstName,
        string $lastName,
        string $email,
        string $phone,
        string $accountNumber,
        string $location,
        ?string $shippingAddress,
        ?string $additionalInformation,
        ?string $sourcePath = null,
        ?string $currency = null
    ): FormSession {
        $selectedProducts = array_map(function ($item) {
            return [
                'product_id' => $item['product']->id,
                'price_id' => $item['price_id'],
                'quantity' => $item['quantity'],
            ];
        }, $productModels);

        // Determine redirect path: use source_path if provided, otherwise default based on currency
        $redirectPath = $sourcePath ?? ($currency === 'CAD' ? '/cccportal/order' : '/pinksky/order');

        return FormSession::create([
            'status' => StatusConstants::PENDING,
            'booking_type' => 'direct', // Identify as direct checkout
            'reference' => $this->generateReference(),
            'user_id' => $user->id,
            'metadata' => [
                'user_agent' => request()->userAgent(),
                'location' => null,
                'order_type' => 'order_sheet',
                'source_path' => $redirectPath,
                'raw' => [
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'email' => $email,
                    'phoneNumber' => $phone,
                    'account_number' => $accountNumber,
                    'location' => $location,
                    'shipping_address' => $shippingAddress,
                    'additional_information' => $additionalInformation,
                    'selectedProducts' => $selectedProducts,
                ],
            ],
        ]);
    }

    /**
     * Process order sheet payment
     */
    public function processOrderSheetPayment(string $checkoutId, ?string $recaptchaToken = null): array
    {
        // Verify reCAPTCHA if token is provided
        if ($recaptchaToken) {
            $this->verifyRecaptcha($recaptchaToken);
        }

        $checkoutData = cache()->get("direct_checkout_{$checkoutId}");

        // If not in cache and this is a resumed payment (ref_*), rebuild from database
        if (! $checkoutData && str_starts_with($checkoutId, 'ref_')) {
            $ref = substr($checkoutId, 4); // Remove 'ref_' prefix
            $existingPayment = \App\Models\Payment::where('reference', $ref)->first();
            if (! $existingPayment) {
                throw new \Exception('Checkout session expired or not found');
            }

            // Rebuild checkout data from payment record
            $paymentProducts = $existingPayment->paymentProducts()->with('product')->get();
            $productsData = $paymentProducts->map(function ($paymentProduct) {
                $rawPrice = $paymentProduct->getOriginal('price');
                if (is_array($rawPrice)) {
                    $price = $rawPrice;
                } elseif (is_string($rawPrice)) {
                    $price = json_decode($rawPrice, true);
                    if (is_object($price)) {
                        $price = (array) $price;
                    } elseif (!is_array($price)) {
                        $price = [];
                    }
                } else {
                    $price = [];
                }
                return [
                     'id' => $paymentProduct->product_id,
                     'product_id' => $paymentProduct->product_id,
                     'name' => $paymentProduct->product->name ?? 'Unknown Product',
                     'quantity' => $paymentProduct->quantity,
                     'selected_price' => $price,
                 ];
            });

            $checkoutData = [
                'checkout_id' => $checkoutId,
                'form_session_id' => $existingPayment->form_session_id,
                'order_type' => 'order_sheet',
                'products' => $productsData,
                'sub_total' => $existingPayment->sub_total,
                'discount_code' => $existingPayment->discount_code,
                'discount_amount' => $existingPayment->discount_amount ?? 0,
                'tax_rate' => $existingPayment->tax_rate ?? 0,
                'tax_amount' => $existingPayment->tax_amount ?? 0,
                'shipping_fee' => $existingPayment->shipping_fee,
                'total' => $existingPayment->total,
                'currency' => $existingPayment->currency,
                'country_code' => 'US',
                'country' => 'United States',
            ];
        }

        if (! $checkoutData) {
            throw new \Exception('Checkout session expired or not found');
        }

        if ($checkoutData['order_type'] !== 'order_sheet') {
            throw new \Exception('Invalid checkout type');
        }

        // Check if payment already exists for this checkout to prevent duplicate orders
        $existingPaymentId = cache()->get("checkout_payment_{$checkoutId}");
        if ($existingPaymentId) {
            $existingPayment = Payment::findOrFail($existingPaymentId);

            return [
                'payment' => $existingPayment,
                'redirect_url' => $existingPayment->payment_reference,
            ];
        }

        // Get form session
        $formSession = FormSession::findOrFail($checkoutData['form_session_id']);

        // Update form session metadata with discount info if applied
        $metadata = $formSession->metadata;
        $metadata['raw']['discount_code'] = $checkoutData['discount_code'];
        $metadata['raw']['discount_amount'] = $checkoutData['discount_amount'];
        $formSession->update(['metadata' => $metadata]);

        // Load products with quantities
         $products = collect();
         foreach ($checkoutData['products'] as $productData) {
             $productId = is_array($productData) ? ($productData['product_id'] ?? null) : ($productData->product_id ?? null);
             if (!$productId) {
                 throw new \Exception('Invalid product data in checkout session');
             }
             $product = Product::findOrFail($productId);
             $product->selected_price = $productData['selected_price'] ?? null;
             $product->quantity = $productData['quantity'] ?? 1; // Store quantity on product object
             $products->push($product);
         }

        // Recalculate all totals to prevent price tampering
        $recalculated = $this->recalculateOrderSheetTotals(
            $products,
            $checkoutData['discount_code'] ?? null,
            $checkoutData['currency'] ?? 'USD'
        );

        // Validate that recalculated totals match (allow small rounding differences)
        $tolerance = 0.01; // Allow 1 cent tolerance for rounding
        if (abs($recalculated['sub_total'] - $checkoutData['sub_total']) > $tolerance ||
            abs($recalculated['tax_amount'] - $checkoutData['tax_amount']) > $tolerance ||
            abs($recalculated['shipping_fee'] - $checkoutData['shipping_fee']) > $tolerance ||
            abs($recalculated['total'] - $checkoutData['total']) > $tolerance) {
            
            Log::warning('Checkout price mismatch detected', [
                'checkout_id' => $checkoutId,
                'original' => $checkoutData,
                'recalculated' => $recalculated,
            ]);
            
            // Use recalculated values (conservative: use the recalculated total)
            $checkoutData = array_merge($checkoutData, $recalculated);
        }

        // Prepare data for ProcessorService
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
            'products' => $products,
            'order_type' => 'order_sheet',
            'customer_info' => $checkoutData['customer_info'],
        ];

        // Create a new payment/order (ProcessorService::initiate always creates a new Payment record)
        $result = (new ProcessorService)->initiate($formSession, $paymentData);

        // Cache payment ID to prevent duplicate orders on retry (5 minutes)
        cache()->put("checkout_payment_{$checkoutId}", $result['payment']->id, now()->addHours(1));

        // Clear checkout cache
        cache()->forget("direct_checkout_{$checkoutId}");

        return $result;
    }

    /**
     * Initialize cart checkout with multiple products
     * Similar to order sheet but with order_type 'cart'
     * If ref is provided, fetches existing payment session instead of creating new one
     */
    public function initializeCartCheckout(
        array $products, // [{product_id, price_id, quantity}, ...]
        string $firstName,
        string $lastName,
        string $email,
        string $phone,
        string $accountNumber,
        string $location,
        ?string $shippingAddress = null,
        ?string $additionalInformation = null,
        ?string $discountCode = null,
        ?string $ref = null
    ): array {
        // If ref is provided, clear the old cached checkout and recalculate with new params
        // This ensures product/discount changes are applied
        if ($ref) {
            $checkoutId = 'ref_' . $ref;
            cache()->forget("direct_checkout_{$checkoutId}");
        }
                    // Find or create user by email
                    $user = $this->findOrCreateUser($firstName, $lastName, $email);

                    // Use location field for currency detection: CAD for Canada, USD for others
                    $geoData = $this->getGeoDataFromLocation($location);

        // Load all products and calculate totals
        $productModels = [];
        $subTotal = 0;
        $totalTax = 0;
        $defaultShippingFee = (float) config('checkout.shipping_fee', 60);

        foreach ($products as $productData) {
            $product = Product::findOrFail($productData['product_id']);

            // Decrypt price_id to get price information
            $priceData = json_decode(Helper::decrypt($productData['price_id']), true);
            if (! $priceData || $priceData['product_id'] != $product->id) {
                throw new \Exception("Invalid price ID for product {$product->id}");
            }

            $selectedPrice = $priceData['value'];
            $product->selected_price = $selectedPrice;
            $quantity = (int) ($productData['quantity'] ?? 1);

            $lineTotal = $selectedPrice['value'] * $quantity;
            $subTotal += $lineTotal;

            // Calculate tax for this product line
            $taxRate = $this->getTaxRate($product);
            $totalTax += $lineTotal * ($taxRate / 100);

            $productModels[] = [
                'product' => $product,
                'price_id' => $productData['price_id'],
                'quantity' => $quantity,
                'selected_price' => $selectedPrice,
            ];
        }

        // Calculate shipping using order-sheet rules (same as cart)
        $shippingFee = $this->calculateOrderSheetShippingFee(
            $subTotal,
            $defaultShippingFee
        );

        // Apply discount if provided (to subtotal only, not shipping)
        $discountAmount = 0;
        if ($discountCode) {
            $discountService = new DiscountCodeService;
            $discountModel = $discountService->validate($discountCode);
            if (! $discountModel) {
                throw new \Exception('Invalid discount code');
            }
            // Calculate discount on subtotal only
            $discountAmount = $discountService->calculateDiscount($subTotal, $discountModel);
        }

        // Apply discount to subtotal only
        $discountedSubtotal = max(0, $subTotal - $discountAmount);

        // If discount is 100% (discounted subtotal is 0), set shipping fee to 0
        if ($discountedSubtotal == 0 && $discountAmount > 0) {
            $shippingFee = 0;
        }

        // Calculate tax on discounted subtotal only (not including shipping)
        $averageTaxRate = $subTotal > 0 ? ($totalTax / $subTotal) * 100 : 0;
        $taxAmount = $discountedSubtotal * ($averageTaxRate / 100);

        $total = $discountedSubtotal + $shippingFee + $taxAmount;

        // Create a new form session for this checkout
        $formSession = $this->createCartFormSession(
            $user,
            $productModels,
            $firstName,
            $lastName,
            $email,
            $phone,
            $accountNumber,
            $location,
            $shippingAddress,
            $additionalInformation
        );

        // Create checkout data
        $checkoutId = 'cart_' . uniqid();
        $checkoutData = [
            'checkout_id' => $checkoutId,
            'form_session_id' => $formSession->id,
            'order_type' => 'cart',
            'products' => array_map(function ($item) {
                return [
                    'product_id' => $item['product']->id,
                    'price_id' => $item['price_id'],
                    'quantity' => $item['quantity'],
                    'selected_price' => $item['selected_price'],
                ];
            }, $productModels),
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
            ],
            'customer_info' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'account_number' => $accountNumber,
                'location' => $location,
                'shipping_address' => $shippingAddress,
                'additional_information' => $additionalInformation,
            ],
            'sub_total' => round($subTotal, 2),
            'discount_code' => $discountCode,
            'discount_amount' => round($discountAmount, 2),
            'tax_rate' => round($averageTaxRate, 2),
            'tax_amount' => round($taxAmount, 2),
            'shipping_fee' => round($shippingFee, 2),
            'total' => round($total, 2),
            'currency' => $geoData['currency'],
            'country_code' => $geoData['country_code'],
            'country' => $geoData['country'],
        ];

        // Store in cache for 30 minutes
        cache()->put("direct_checkout_{$checkoutId}", $checkoutData, now()->addHours(2));

        return $checkoutData;
    }

    /**
     * Create form session for cart checkout
     */
    private function createCartFormSession(
        User $user,
        array $productModels,
        string $firstName,
        string $lastName,
        string $email,
        string $phone,
        string $accountNumber,
        string $location,
        ?string $shippingAddress,
        ?string $additionalInformation
    ): FormSession {
        $selectedProducts = array_map(function ($item) {
            return [
                'product_id' => $item['product']->id,
                'price_id' => $item['price_id'],
                'quantity' => $item['quantity'],
            ];
        }, $productModels);

        return FormSession::create([
            'status' => StatusConstants::PENDING,
            'booking_type' => 'direct', // Identify as direct checkout
            'reference' => $this->generateReference(),
            'user_id' => $user->id,
            'metadata' => [
                'user_agent' => request()->userAgent(),
                'location' => null,
                'order_type' => 'cart',
                'raw' => [
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'email' => $email,
                    'phoneNumber' => $phone,
                    'account_number' => $accountNumber,
                    'location' => $location,
                    'shipping_address' => $shippingAddress,
                    'additional_information' => $additionalInformation,
                    'selectedProducts' => $selectedProducts,
                ],
            ],
        ]);
    }

    /**
     * Process cart payment
     */
    public function processCartPayment(string $checkoutId, ?string $recaptchaToken = null): array
    {
        // Verify reCAPTCHA if token is provided
        if ($recaptchaToken) {
            $this->verifyRecaptcha($recaptchaToken);
        }

        $checkoutData = cache()->get("direct_checkout_{$checkoutId}");

        // If not in cache and this is a resumed payment (ref_*), rebuild from database
        if (! $checkoutData && str_starts_with($checkoutId, 'ref_')) {
            $ref = substr($checkoutId, 4); // Remove 'ref_' prefix
            $existingPayment = \App\Models\Payment::where('reference', $ref)->first();
            if (! $existingPayment) {
                throw new \Exception('Checkout session expired or not found');
            }

            // Rebuild checkout data from payment record
            $paymentProducts = $existingPayment->paymentProducts()->with('product')->get();
            $productsData = $paymentProducts->map(function ($paymentProduct) {
                $rawPrice = $paymentProduct->getOriginal('price');
                if (is_array($rawPrice)) {
                    $price = $rawPrice;
                } elseif (is_string($rawPrice)) {
                    $decoded = json_decode($rawPrice, true);
                    if (is_array($decoded)) {
                        $price = $decoded;
                    } else {
                        $price = [];
                    }
                } else {
                    $price = [];
                }
                return [
                     'product_id' => $paymentProduct->product_id,
                     'id' => $paymentProduct->product_id,
                     'name' => $paymentProduct->product->name ?? 'Unknown Product',
                     'quantity' => $paymentProduct->quantity,
                     'selected_price' => $price,
                 ];
            });

            $checkoutData = [
                'checkout_id' => $checkoutId,
                'form_session_id' => $existingPayment->form_session_id,
                'order_type' => 'cart',
                'products' => $productsData,
                'sub_total' => $existingPayment->sub_total,
                'discount_code' => $existingPayment->discount_code,
                'discount_amount' => $existingPayment->discount_amount ?? 0,
                'tax_rate' => $existingPayment->tax_rate ?? 0,
                'tax_amount' => $existingPayment->tax_amount ?? 0,
                'shipping_fee' => $existingPayment->shipping_fee,
                'total' => $existingPayment->total,
                'currency' => $existingPayment->currency,
                'country_code' => 'US',
                'country' => 'United States',
                'customer_info' => [],
            ];
        }

        if (! $checkoutData) {
            throw new \Exception('Checkout session expired or not found');
        }

        if ($checkoutData['order_type'] !== 'cart') {
            throw new \Exception('Invalid checkout type');
        }

        // Get form session
        $formSession = FormSession::findOrFail($checkoutData['form_session_id']);

        // Update form session metadata with discount info if applied
        $metadata = $formSession->metadata;
        $metadata['raw']['discount_code'] = $checkoutData['discount_code'];
        $metadata['raw']['discount_amount'] = $checkoutData['discount_amount'];
        $formSession->update(['metadata' => $metadata]);

        // Load products with quantities
        $products = collect();
        foreach ($checkoutData['products'] as $productData) {
            $product = Product::findOrFail($productData['product_id']);
            $product->selected_price = $productData['selected_price'];
            $product->quantity = $productData['quantity']; // Store quantity on product object
            $products->push($product);
        }

        // Prepare data for ProcessorService
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
            'products' => $products,
            'order_type' => 'cart',
            'customer_info' => $checkoutData['customer_info'],
        ];

        // Create a new payment/order
        $result = (new ProcessorService)->initiate($formSession, $paymentData);

        // Clear checkout cache
        cache()->forget("direct_checkout_{$checkoutId}");

        return $result;
    }

    /**
     * Calculate cart summary without creating a checkout
     * Used for displaying cart totals in the sidebar
     */
    public function calculateCartSummary(
        array $products, // [{product_id, price_id, quantity}, ...]
        ?string $discountCode = null,
        ?string $location = null
    ): array {
        // Use location-based pricing: CAD for Canada, USD for others
        $geoData = $this->getGeoDataFromLocation($location);

        // Load all products and calculate totals
        $subTotal = 0;
        $totalTax = 0;
        $defaultShippingFee = (float) config('checkout.shipping_fee', 60);

        foreach ($products as $productData) {
            $product = Product::findOrFail($productData['product_id']);

            // Decrypt price_id to get price information
            $priceData = json_decode(Helper::decrypt($productData['price_id']), true);
            if (! $priceData || $priceData['product_id'] != $product->id) {
                throw new \Exception("Invalid price ID for product {$product->id}");
            }

            $selectedPrice = $priceData['value'];
            $quantity = (int) ($productData['quantity'] ?? 1);

            $lineTotal = $selectedPrice['value'] * $quantity;
            $subTotal += $lineTotal;

            // Calculate tax for this product line
            $taxRate = $this->getTaxRate($product);
            $totalTax += $lineTotal * ($taxRate / 100);
        }

        // Calculate shipping using order-sheet rules
        $shippingFee = $this->calculateOrderSheetShippingFee(
            $subTotal,
            $defaultShippingFee
        );

        // Apply discount if provided (to subtotal only, not shipping)
        $discountAmount = 0;
        if ($discountCode) {
            $discountService = new DiscountCodeService;
            $discountModel = $discountService->validate($discountCode);
            if ($discountModel) {
                // Calculate discount on subtotal only
                $discountAmount = $discountService->calculateDiscount($subTotal, $discountModel);
            }
        }

        // Apply discount to subtotal only
        $discountedSubtotal = max(0, $subTotal - $discountAmount);

        // If discount is 100% (discounted subtotal is 0), set shipping fee to 0
        if ($discountedSubtotal == 0 && $discountAmount > 0) {
            $shippingFee = 0;
        }

        // Calculate tax on discounted subtotal only (not including shipping)
        $averageTaxRate = $subTotal > 0 ? ($totalTax / $subTotal) * 100 : 0;
        $taxAmount = $discountedSubtotal * ($averageTaxRate / 100);

        $total = $discountedSubtotal + $shippingFee + $taxAmount;

        return [
            'sub_total' => round($subTotal, 2),
            'discount_code' => $discountCode,
            'discount_amount' => round($discountAmount, 2),
            'tax_rate' => round($averageTaxRate, 2),
            'tax_amount' => round($taxAmount, 2),
            'shipping_fee' => round($shippingFee, 2),
            'total' => round($total, 2),
            'currency' => $geoData['currency'],
        ];
    }

    /**
     * Recalculate order sheet totals from products to validate against tampering
     */
    private function recalculateOrderSheetTotals(
        $products,
        ?string $discountCode = null,
        string $currency = 'USD'
    ): array {
        $subTotal = 0;
        $totalTax = 0;
        $defaultShippingFee = (float) config('checkout.shipping_fee', 60);

        // Calculate subtotal and total tax from products
        foreach ($products as $product) {
            $quantity = $product->quantity ?? 1;
            $price = $product->selected_price;
            
            if (is_array($price)) {
                $lineTotal = ($price['value'] ?? 0) * $quantity;
            } else {
                $lineTotal = 0;
            }
            
            $subTotal += $lineTotal;
            
            // Calculate tax for this product line
            $taxRate = $this->getTaxRate($product);
            $totalTax += $lineTotal * ($taxRate / 100);
        }

        // Calculate shipping
        $shippingFee = $this->calculateOrderSheetShippingFee($subTotal, $defaultShippingFee);

        // Apply discount if provided
        $discountAmount = 0;
        if ($discountCode) {
            $discountService = new DiscountCodeService;
            $discountModel = $discountService->validate($discountCode);
            if ($discountModel) {
                $discountAmount = $discountService->calculateDiscount($subTotal, $discountModel);
            }
        }

        // Apply discount to subtotal only
        $discountedSubtotal = max(0, $subTotal - $discountAmount);

        // If discount is 100%, set shipping fee to 0
        if ($discountedSubtotal == 0 && $discountAmount > 0) {
            $shippingFee = 0;
        }

        // Calculate tax on discounted subtotal only
        $averageTaxRate = $subTotal > 0 ? ($totalTax / $subTotal) * 100 : 0;
        $taxAmount = $discountedSubtotal * ($averageTaxRate / 100);

        $total = $discountedSubtotal + $shippingFee + $taxAmount;

        return [
            'sub_total' => round($subTotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'tax_rate' => round($averageTaxRate, 2),
            'tax_amount' => round($taxAmount, 2),
            'shipping_fee' => round($shippingFee, 2),
            'total' => round($total, 2),
        ];
    }
}
