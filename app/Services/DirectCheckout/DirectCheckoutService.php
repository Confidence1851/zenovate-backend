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
        ?string $useType = null,
        ?string $discountCode = null
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
        $formSession = $this->createFormSession($user, $product, $priceId, $selectedPrice, $useType, $discountCode);

        // Get location-based currency and country
        $geoData = $this->getGeoData();

        // Calculate totals
        $subTotal = $selectedPrice['value'];
        $shippingFee = $this->getShippingFee($product);
        $taxRate = $this->getTaxRate($product, $geoData['currency']);
        $taxAmount = $subTotal * ($taxRate / 100);

        // Apply discount if provided
        $discountAmount = 0;
        $appliedDiscountCode = null;
        if ($discountCode) {
            try {
                $discountService = new DiscountCodeService();
                $validatedDiscount = $discountService->validate($discountCode);
                
                if ($validatedDiscount) {
                    // Calculate discount amount based on the discount type
                    $discountAmount = $discountService->calculateDiscount($subTotal, $validatedDiscount);
                    $appliedDiscountCode = $validatedDiscount->code;
                } else {
                    // Invalid discount code - log and continue without discount
                    Log::warning('Invalid or expired discount code in direct checkout', ['code' => $discountCode]);
                }
            } catch (\Exception $e) {
                // Error validating discount code - continue without discount
                Log::warning('Error validating discount code in direct checkout', ['code' => $discountCode, 'error' => $e->getMessage()]);
            }
        }

        // Apply discount to subtotal only
        $discountedSubtotal = max(0, $subTotal - $discountAmount);
        
        // If discount is 100% (discounted subtotal is 0), set shipping fee to 0
        if ($discountedSubtotal == 0 && $discountAmount > 0) {
            $shippingFee = 0;
        }

        // Recalculate tax on discounted subtotal
        $taxAmount = $discountedSubtotal * ($taxRate / 100);

        // Calculate total
        $total = $discountedSubtotal + $shippingFee + $taxAmount;

        // Create checkout data
        $checkoutData = [
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
            'discount_code' => $appliedDiscountCode,
            'discount_amount' => $discountAmount,
            'total' => round($total, 2),
            'currency' => $geoData['currency'],
            'country_code' => $geoData['country_code'],
            'country' => $geoData['country'],
        ];

        return $checkoutData;
    }

    /**
     * Find or create user by email (public wrapper for controller use)
     */
    public function findOrCreateUserForCheckout(string $firstName, string $lastName, string $email): User
    {
        return $this->findOrCreateUser($firstName, $lastName, $email);
    }

    /**
     * Get geo data for checkout (public wrapper for controller use)
     */
    public function getGeoDataForCheckout(): array
    {
        return $this->getGeoData();
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
        ?string $useType,
        ?string $discountCode = null
    ): FormSession {
        return FormSession::create([
            'status' => StatusConstants::PENDING,
            'booking_type' => 'direct', // Identify as direct checkout
            'reference' => $this->generateReference(),
            'user_id' => $user->id,
            'metadata' => [
                'order_type' => 'regular',
                'product_id' => $product->id,
                'selected_price' => $selectedPrice,
                'source_path' => request()->input('source_path', '/products'),
                'user_agent' => request()->userAgent(),
                'location' => null,
                'discount_code' => $discountCode, // Store discount code for later use in processPayment()
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
    public function processPayment($formSessionId, ?string $recaptchaToken = null): array
    {
        // Verify reCAPTCHA if token is provided
        if ($recaptchaToken) {
            $this->verifyRecaptcha($recaptchaToken);
        }

        // Get form session
        $formSession = FormSession::findOrFail($formSessionId);
        $metadata = $formSession->metadata;
        $rawData = $metadata['raw'] ?? [];
        $selectedProducts = $rawData['selectedProducts'] ?? [];

        if (empty($selectedProducts)) {
            throw new \Exception('No products found in session');
        }

        // Get first product
        $firstProductData = $selectedProducts[0];
        $product = Product::findOrFail($firstProductData['product_id']);

        // Decrypt price_id to get price information
        $priceData = json_decode(Helper::decrypt($firstProductData['price_id']), true);
        if (! $priceData) {
            throw new \Exception('Invalid price data');
        }

        $selectedPrice = $priceData['value'];
        $product->selected_price = $selectedPrice;

        // Get currency from metadata
        $currency = $metadata['currency'] ?? null;

        // Calculate totals from product
        $subTotal = $selectedPrice['value'];
        $shippingFee = $this->getShippingFee($product);
        $taxRate = $this->getTaxRate($product, $currency);
        $taxAmount = $subTotal * ($taxRate / 100);

        // Apply discount if stored in metadata
        $discountCode = $metadata['discount_code'] ?? null;
        $discountAmount = 0;
        if ($discountCode) {
            try {
                $discountService = new DiscountCodeService();
                $validatedDiscount = $discountService->validate($discountCode);
                if ($validatedDiscount) {
                    $discountAmount = $discountService->calculateDiscount($subTotal, $validatedDiscount);
                }
            } catch (\Exception $e) {
                Log::warning('Error applying discount in processPayment', [
                    'discount_code' => $discountCode,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Calculate final totals with discount
        $discountedSubtotal = max(0, $subTotal - $discountAmount);
        
        // If discount is 100% (discounted subtotal is 0 or nearly 0), set shipping fee to 0
        if ($discountedSubtotal <= 0.01 && $discountAmount > 0) {
            $shippingFee = 0;
        }
        
        $finalTaxAmount = $discountedSubtotal * ($taxRate / 100);
        $finalTotal = $discountedSubtotal + $shippingFee + $finalTaxAmount;

        // Prepare data for ProcessorService
        $paymentData = [
            'sub_total' => round($subTotal, 2),
            'currency' => $metadata['currency'] ?? 'USD',
            'total' => round($finalTotal, 2),
            'shipping_fee' => round($shippingFee, 2),
            'tax_rate' => $taxRate,
            'tax_amount' => round($finalTaxAmount, 2),
            'country_code' => $metadata['country_code'] ?? 'US',
            'discount_code' => $discountCode,
            'discount_amount' => round($discountAmount, 2),
            'products' => collect([$product]),
        ];

        // Use existing ProcessorService to create payment
        $result = (new ProcessorService)->initiate($formSession, $paymentData);

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
     * Get brand from currency (CAD = cccportal, others = pinksky)
     */
    private function getBrandFromCurrency(?string $currency = null): ?string
    {
        if ($currency === 'CAD') {
            return 'cccportal';
        } elseif ($currency === 'USD') {
            return 'pinksky';
        }
        return null;
    }

    /**
     * Get tax rate (product-specific, brand-specific, or global)
     */
    private function getTaxRate(Product $product, ?string $currency = null): float
    {
        // Check product-specific tax rate first
        if ($product->tax_rate !== null) {
            return (float) $product->tax_rate;
        }

        // Get brand from currency if available
        $brand = $this->getBrandFromCurrency($currency);
        
        // Check brand-specific tax rate
        if ($brand) {
            $brandRate = config("checkout.tax_rates_by_brand.{$brand}");
            if ($brandRate !== null) {
                return (float) $brandRate;
            }
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

        // Get geo data for currency early so we can use it in tax calculation
        $geoData = $this->getGeoDataFromLocation($location);
        if ($currency && in_array(strtoupper($currency), ['USD', 'CAD'])) {
            $geoData = [
                'currency' => strtoupper($currency),
                'country_code' => strtoupper($currency) === 'CAD' ? 'CA' : 'US',
                'country' => strtoupper($currency) === 'CAD' ? 'Canada' : 'United States',
            ];
        }

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
            $taxRate = $this->getTaxRate($product, $geoData['currency']);
            $totalTax += $lineTotal * ($taxRate / 100);

            $productModels[] = [
                'product' => $product,
                'price_id' => $productData['price_id'],
                'quantity' => $quantity,
                'selected_price' => $selectedPrice,
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

        // If discount is 100% (discounted subtotal is 0 or nearly 0), set shipping fee to 0
        if ($discountedSubtotal <= 0.01 && $discountAmount > 0) {
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
     * If ref is provided and payment exists and is not paid, reuses existing form session
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
        // Check if ref is provided and payment exists
        $existingFormSession = null;
        if ($ref) {
            $payment = \App\Models\Payment::where('reference', $ref)->first();
            if ($payment) {
                if ($payment->status === 'paid') {
                    throw new \Exception('This form session has been paid for');
                }
                $existingFormSession = $payment->formSession;
            }
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
            $taxRate = $this->getTaxRate($product, $geoData['currency']);
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

        // If discount is 100% (discounted subtotal is 0 or nearly 0), set shipping fee to 0
        if ($discountedSubtotal <= 0.01 && $discountAmount > 0) {
            $shippingFee = 0;
        }

        // Calculate tax on discounted subtotal only (not including shipping)
        $averageTaxRate = $subTotal > 0 ? ($totalTax / $subTotal) * 100 : 0;
        $taxAmount = $discountedSubtotal * ($averageTaxRate / 100);

        $total = $discountedSubtotal + $shippingFee + $taxAmount;

        // Use existing form session if provided and available, otherwise create a new one
        if ($existingFormSession) {
            $formSession = $existingFormSession;
            // Update the form session metadata with latest data
            $selectedProducts = array_map(function ($item) {
                return [
                    'product_id' => $item['product']->id,
                    'price_id' => $item['price_id'],
                    'quantity' => $item['quantity'],
                ];
            }, $productModels);
            
            $redirectPath = $sourcePath ?? ($geoData['currency'] === 'CAD' ? '/cccportal/order' : '/pinksky/order');
            
            $formSession->metadata = [
                'user_agent' => request()->userAgent(),
                'location' => null,
                'order_type' => 'order_sheet',
                'source_path' => $redirectPath,
                'currency' => $geoData['currency'],
                'country_code' => $geoData['country_code'],
                'raw' => [
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'email' => $email,
                    'phoneNumber' => $phone,
                    'account_number' => $accountNumber,
                    'location' => $location,
                    'shipping_address' => $shippingAddress,
                    'additional_information' => $additionalInformation,
                    'discount_code' => $discountCode,
                    'selectedProducts' => $selectedProducts,
                ],
            ];
            $formSession->save();
        } else {
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
                $geoData['currency'],
                $discountCode
            );
        }

        // Determine redirect path: use source_path if provided, otherwise default based on currency
        $redirectPath = $sourcePath ?? ($geoData['currency'] === 'CAD' ? '/cccportal/order' : '/pinksky/order');

        return [
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
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
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
        ?string $currency = null,
        ?string $discountCode = null
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

        // Set country code based on currency
        $countryCode = ($currency === 'CAD') ? 'CA' : 'US';

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
                'currency' => $currency,
                'country_code' => $countryCode,
                'raw' => [
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'email' => $email,
                    'phoneNumber' => $phone,
                    'account_number' => $accountNumber,
                    'location' => $location,
                    'shipping_address' => $shippingAddress,
                    'additional_information' => $additionalInformation,
                    'discount_code' => $discountCode,
                    'selectedProducts' => $selectedProducts,
                ],
            ],
        ]);
    }

    /**
     * Process order sheet payment
     */
    public function processOrderSheetPayment($formSessionId, ?string $recaptchaToken = null): array
    {
        // Verify reCAPTCHA if token is provided
        // if ($recaptchaToken) {
        //     $this->verifyRecaptcha($recaptchaToken);
        // }

        // Get form session
        $formSession = FormSession::findOrFail($formSessionId);

        $metadata = $formSession->metadata;

        
        if (($metadata['order_type'] ?? null) !== 'order_sheet') {
            throw new \Exception('Invalid checkout type');
        }

        // Get selected products from metadata
        $rawData = $metadata['raw'] ?? [];
        $selectedProducts = $rawData['selectedProducts'] ?? [];

        if (empty($selectedProducts)) {
            throw new \Exception('No products found in session');
        }

        // Load products with quantities and prices from metadata
        $products = collect();
        foreach ($selectedProducts as $productData) {
            $product = Product::findOrFail($productData['product_id']);
            
            // Decrypt price_id to get price information
            $priceData = json_decode(Helper::decrypt($productData['price_id']), true);
            if (! $priceData) {
                throw new \Exception('Invalid price data for product');
            }
            
            $product->selected_price = $priceData['value'];
            $product->quantity = (int) ($productData['quantity'] ?? 1);
            $products->push($product);
        }

        // Recalculate all totals to validate prices
        $discountCode = $rawData['discount_code'] ?? null;
        $currency = $rawData['currency'] ?? $metadata['currency'] ?? 'USD';
        $totals = $this->recalculateOrderSheetTotals(
            $products,
            $discountCode,
            $currency
        );

        // Prepare data for ProcessorService
        $paymentData = [
            'sub_total' => $totals['sub_total'],
            'currency' => $currency,
            'total' => $totals['total'],
            'shipping_fee' => $totals['shipping_fee'],
            'tax_rate' => $totals['tax_rate'],
            'tax_amount' => $totals['tax_amount'],
            'country_code' => $rawData['country_code'] ?? 'US',
            'discount_code' => $discountCode,
            'discount_amount' => $totals['discount_amount'],
            'products' => $products,
            'order_type' => 'order_sheet',
            'customer_info' => [
                'first_name' => $rawData['firstName'] ?? null,
                'last_name' => $rawData['lastName'] ?? null,
                'email' => $rawData['email'] ?? null,
                'phone' => $rawData['phoneNumber'] ?? null,
                'account_number' => $rawData['account_number'] ?? null,
                'location' => $rawData['location'] ?? null,
                'shipping_address' => $rawData['shipping_address'] ?? null,
                'additional_information' => $rawData['additional_information'] ?? null,
            ],
        ];

        // Create a new payment/order
        $result = (new ProcessorService)->initiate($formSession, $paymentData);

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
        // If ref is provided, recalculate with new params
        // This ensures product/discount changes are applied
        if ($ref) {
            $checkoutId = 'ref_' . $ref;
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
            $taxRate = $this->getTaxRate($product, $geoData['currency']);
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

        // If discount is 100% (discounted subtotal is 0 or nearly 0), set shipping fee to 0
        if ($discountedSubtotal <= 0.01 && $discountAmount > 0) {
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
            $additionalInformation,
            $discountCode,
            $geoData['currency'] ?? 'USD'
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
        ?string $additionalInformation,
        ?string $discountCode = null,
        ?string $currency = null
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
                'currency' => $currency ?? 'USD',
                'country_code' => 'US',
                'raw' => [
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'email' => $email,
                    'phoneNumber' => $phone,
                    'account_number' => $accountNumber,
                    'location' => $location,
                    'shipping_address' => $shippingAddress,
                    'additional_information' => $additionalInformation,
                    'discount_code' => $discountCode,
                    'selectedProducts' => $selectedProducts,
                ],
            ],
        ]);
    }

    /**
     * Process cart payment
     */
    public function processCartPayment($formSessionId, ?string $recaptchaToken = null): array
    {
        // Verify reCAPTCHA if token is provided
        if ($recaptchaToken) {
            $this->verifyRecaptcha($recaptchaToken);
        }

        // Get form session
        $formSession = FormSession::findOrFail($formSessionId);
        $metadata = $formSession->metadata;

        if (($metadata['order_type'] ?? null) !== 'cart') {
            throw new \Exception('Invalid checkout type');
        }

        // Get selected products from metadata
        $rawData = $metadata['raw'] ?? [];
        $selectedProducts = $rawData['selectedProducts'] ?? [];

        if (empty($selectedProducts)) {
            throw new \Exception('No products found in session');
        }

        // Load products with quantities and prices from metadata
        $products = collect();
        foreach ($selectedProducts as $productData) {
            $product = Product::findOrFail($productData['product_id']);
            
            // Decrypt price_id to get price information
            $priceData = json_decode(Helper::decrypt($productData['price_id']), true);
            if (! $priceData) {
                throw new \Exception('Invalid price data for product');
            }
            
            $product->selected_price = $priceData['value'];
            $product->quantity = (int) ($productData['quantity'] ?? 1);
            $products->push($product);
        }

        // Recalculate all totals to validate prices (for cart, use default tax rate only)
        $discountCode = $rawData['discount_code'] ?? null;
        $totals = $this->recalculateOrderSheetTotals(
            $products,
            $discountCode,
            $rawData['currency'] ?? 'USD',
            true // use default tax rate only for cart
        );

        // Prepare data for ProcessorService
        $paymentData = [
            'sub_total' => $totals['sub_total'],
            'currency' => $rawData['currency'] ?? 'USD',
            'total' => $totals['total'],
            'shipping_fee' => $totals['shipping_fee'],
            'tax_rate' => $totals['tax_rate'],
            'tax_amount' => $totals['tax_amount'],
            'country_code' => $rawData['country_code'] ?? 'US',
            'discount_code' => $discountCode,
            'discount_amount' => $totals['discount_amount'],
            'products' => $products,
            'order_type' => 'cart',
            'customer_info' => [
                'first_name' => $rawData['firstName'] ?? null,
                'last_name' => $rawData['lastName'] ?? null,
                'email' => $rawData['email'] ?? null,
                'phone' => $rawData['phoneNumber'] ?? null,
                'account_number' => $rawData['account_number'] ?? null,
                'location' => $rawData['location'] ?? null,
                'shipping_address' => $rawData['shipping_address'] ?? null,
                'additional_information' => $rawData['additional_information'] ?? null,
            ],
        ];

        // Create a new payment/order
        $result = (new ProcessorService)->initiate($formSession, $paymentData);

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

            // Calculate tax for this product line (cart uses default tax rate, not brand-specific)
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

        // If discount is 100% (discounted subtotal is 0 or nearly 0), set shipping fee to 0
        if ($discountedSubtotal <= 0.01 && $discountAmount > 0) {
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
     * @param bool $useDefaultTaxRateOnly If true, use default tax rate (for cart). If false, use brand-specific rates (for order sheet).
     */
    private function recalculateOrderSheetTotals(
        $products,
        ?string $discountCode = null,
        string $currency = 'USD',
        bool $useDefaultTaxRateOnly = false
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
            // For cart: use default tax rate only
            // For order sheet: use brand-specific rates
            $taxRate = $useDefaultTaxRateOnly ? $this->getTaxRate($product) : $this->getTaxRate($product, $currency);
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

    /**
     * Calculate direct checkout totals with optional discount
     * Pure calculation endpoint - no form session creation
     * 
     * @param int $productId
     * @param string $priceId
     * @param string|null $discountCode
     * @param string|null $currency
     * @return array Totals including discount
     * @throws \Exception
     */
    public function calculateDirectCheckoutTotals(
        int $productId,
        string $priceId,
        ?string $discountCode = null,
        ?string $currency = null
    ): array {
        $product = Product::findOrFail($productId);

        // Decrypt price_id to get price information
        $priceData = json_decode(Helper::decrypt($priceId), true);
        if (! $priceData || $priceData['product_id'] != $productId) {
            throw new \Exception('Invalid price ID');
        }

        $selectedPrice = $priceData['value'];

        // If currency not provided, get it from geo data
        if (!$currency) {
            $geoData = $this->getGeoData();
            $currency = $geoData['currency'];
        }

        // Calculate base totals
        $subTotal = $selectedPrice['value'];
        $shippingFee = $this->getShippingFee($product);
        $taxRate = $this->getTaxRate($product, $currency);

        // Apply discount if provided
        $discountAmount = 0;
        $appliedDiscountCode = null;
        if ($discountCode) {
            try {
                $discountService = new DiscountCodeService();
                $validatedDiscount = $discountService->validate($discountCode);
                
                if ($validatedDiscount) {
                    $discountAmount = $discountService->calculateDiscount($subTotal, $validatedDiscount);
                    $appliedDiscountCode = $validatedDiscount->code;
                } else {
                    throw new \Exception('Invalid or expired discount code. Please check and try again.');
                }
            } catch (\Exception $e) {
                throw $e;
            }
        }

        // Calculate tax on discounted subtotal only
        $discountedSubtotal = max(0, $subTotal - $discountAmount);
        
        // If discount is 100% (discounted subtotal is 0 or nearly 0), set shipping to 0
        if ($discountedSubtotal <= 0.01 && $discountAmount > 0) {
            $shippingFee = 0;
        }
        
        $taxAmount = $discountedSubtotal * ($taxRate / 100);

        // Calculate total
        $total = $discountedSubtotal + $shippingFee + $taxAmount;

        return [
            'sub_total' => round($subTotal, 2),
            'shipping_fee' => round($shippingFee, 2),
            'tax_rate' => $taxRate,
            'tax_amount' => round($taxAmount, 2),
            'discount_code' => $appliedDiscountCode,
            'discount_amount' => round($discountAmount, 2),
            'total' => round($total, 2),
        ];
    }

}
