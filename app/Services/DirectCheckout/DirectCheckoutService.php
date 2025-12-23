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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

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

        // Apply discount using DiscountCodeService (on subtotal only, not shipping)
        $discountService = new DiscountCodeService();
        $discountModel = $discountService->validate($discountCode);
        if (!$discountModel) {
            throw new \Exception('Invalid or expired discount code');
        }

        // Calculate discount on subtotal only
        $discountAmount = $discountService->calculateDiscount($checkoutData['sub_total'], $discountModel);

        // Increment usage count
        $discountModel->incrementUsage();

        // Apply discount to subtotal only
        $discountedSubtotal = max(0, $checkoutData['sub_total'] - $discountAmount);
        
        // Calculate tax on discounted subtotal only (not including shipping)
        $taxAmount = $discountedSubtotal * ($checkoutData['tax_rate'] / 100);
        $checkoutData['tax_amount'] = round($taxAmount, 2);
        $checkoutData['discount_code'] = $discountModel->code;
        $checkoutData['discount_amount'] = round($discountAmount, 2);
        $checkoutData['total'] = round($discountedSubtotal + $checkoutData['shipping_fee'] + $taxAmount, 2);

        // Update cache
        cache()->put("direct_checkout_{$checkoutId}", $checkoutData, now()->addMinutes(30));

        return $checkoutData;
    }

    /**
     * Verify reCAPTCHA token
     */
    private function verifyRecaptcha(?string $recaptchaToken): void
    {
        if (!$recaptchaToken) {
            throw ValidationException::withMessages([
                'recaptcha_token' => ['reCAPTCHA token is required']
            ]);
        }

        $recaptchaSecret = config('services.recaptcha.secret');
        if (!$recaptchaSecret) {
            Log::warning('reCAPTCHA secret key not configured');
            throw new \Exception('reCAPTCHA verification is not configured');
        }

        $recaptchaResponse = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $recaptchaSecret,
            'response' => $recaptchaToken,
            'remoteip' => request()->ip(),
        ]);

        $recaptchaResult = $recaptchaResponse->json();

        if (!$recaptchaResult['success'] || ($recaptchaResult['score'] ?? 0) < 0.5) {
            Log::warning('reCAPTCHA verification failed', [
                'result' => $recaptchaResult,
                'ip' => request()->ip(),
            ]);
            throw ValidationException::withMessages([
                'recaptcha_token' => ['reCAPTCHA verification failed. Please try again.']
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
        $countryCode = $info["countryCode"] ?? null;
        $country = $info["country"] ?? null;
        
        // Default to USD if location is not Canada
        if (strtolower($countryCode ?? '') === 'ca' || strtolower($country ?? '') === 'canada') {
            $currency = $info["currency"] ?? "CAD";
            $countryCode = $countryCode ?? "CA";
            $country = $country ?? "Canada";
        } else {
            $currency = $info["currency"] ?? "USD";
            $countryCode = $countryCode ?? "US";
            $country = $country ?? "United States";
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
     * Initialize order sheet checkout with multiple products
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
        ?string $discountCode = null
    ): array {
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
            if (!$priceData || $priceData['product_id'] != $product->id) {
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
            $discountService = new DiscountCodeService();
            $discountModel = $discountService->validate($discountCode);
            if (!$discountModel) {
                throw new \Exception('Invalid discount code');
            }
            // Calculate discount on subtotal only
            $discountAmount = $discountService->calculateDiscount($subTotal, $discountModel);
        }

        // Apply discount to subtotal only
        $discountedSubtotal = max(0, $subTotal - $discountAmount);
        
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
            $additionalInformation
        );

        // Create checkout data
        $checkoutId = 'order_sheet_' . uniqid();
        $checkoutData = [
            'checkout_id' => $checkoutId,
            'form_session_id' => $formSession->id,
            'order_type' => 'order_sheet',
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
        cache()->put("direct_checkout_{$checkoutId}", $checkoutData, now()->addMinutes(30));

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
                'order_type' => 'order_sheet',
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

        if (!$checkoutData) {
            throw new \Exception('Checkout session expired or not found');
        }

        if ($checkoutData['order_type'] !== 'order_sheet') {
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
            'order_type' => 'order_sheet',
            'customer_info' => $checkoutData['customer_info'],
        ];

        // Create a new payment/order (ProcessorService::initiate always creates a new Payment record)
        // Each time processOrderSheetPayment is called, a new order is created
        $result = (new ProcessorService())->initiate($formSession, $paymentData);

        // Clear checkout cache
        cache()->forget("direct_checkout_{$checkoutId}");

        return $result;
    }

    /**
     * Initialize cart checkout with multiple products
     * Similar to order sheet but with order_type 'cart'
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
        ?string $discountCode = null
    ): array {
        // Find or create user by email
        $user = $this->findOrCreateUser($firstName, $lastName, $email);

        // Use location-based pricing: CAD for Canada, USD for others
        $geoData = $this->getGeoData();

        // Load all products and calculate totals
        $productModels = [];
        $subTotal = 0;
        $totalTax = 0;
        $defaultShippingFee = (float) config('checkout.shipping_fee', 60);

        foreach ($products as $productData) {
            $product = Product::findOrFail($productData['product_id']);

            // Decrypt price_id to get price information
            $priceData = json_decode(Helper::decrypt($productData['price_id']), true);
            if (!$priceData || $priceData['product_id'] != $product->id) {
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
            $discountService = new DiscountCodeService();
            $discountModel = $discountService->validate($discountCode);
            if (!$discountModel) {
                throw new \Exception('Invalid discount code');
            }
            // Calculate discount on subtotal only
            $discountAmount = $discountService->calculateDiscount($subTotal, $discountModel);
        }

        // Apply discount to subtotal only
        $discountedSubtotal = max(0, $subTotal - $discountAmount);
        
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
        cache()->put("direct_checkout_{$checkoutId}", $checkoutData, now()->addMinutes(30));

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

        if (!$checkoutData) {
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
        $result = (new ProcessorService())->initiate($formSession, $paymentData);

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
            if (!$priceData || $priceData['product_id'] != $product->id) {
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
            $discountService = new DiscountCodeService();
            $discountModel = $discountService->validate($discountCode);
            if ($discountModel) {
                // Calculate discount on subtotal only
                $discountAmount = $discountService->calculateDiscount($subTotal, $discountModel);
            }
        }

        // Apply discount to subtotal only
        $discountedSubtotal = max(0, $subTotal - $discountAmount);
        
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
}
