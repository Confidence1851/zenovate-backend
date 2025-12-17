<?php

namespace App\Services\Form\Payment;

use App\Helpers\StatusConstants;
use App\Models\FormSession;
use App\Models\Payment;
use App\Models\PaymentProduct;
use App\Services\Form\Session\UpdateService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;

class StripeService
{
    private $stripeClient;

    public $amount, $currency = "CAD", $source, $description, $receipt_email, $chargeResponse, $shippingFee, $country;
    public $taxRate, $taxAmount;
    public FormSession $formSession;
    public Payment $payment;
    public Collection $products;
    public function __construct()
    {
        $stripeKey = config('services.stripe.secret');

        if (empty($stripeKey)) {
            throw new \Exception('Stripe secret key is not configured. Please set STRIPE_SK in your .env file.');
        }

        $this->stripeClient = new \Stripe\StripeClient($stripeKey);
    }

    public function setAmount(float $value)
    {
        $this->amount = $value;
        return $this;
    }

    public function setCurrency(string $value)
    {
        $this->currency = $value;
        return $this;
    }

    public function setCountry(string $value)
    {
        $this->country = $value;
        return $this;
    }

    public function setSource(string $value)
    {
        $this->source = $value;
        return $this;
    }

    public function setDescription(string $value)
    {
        $this->description = $value;
        return $this;
    }

    public function setShippingFee(float $value)
    {
        $this->shippingFee = $value;
        return $this;
    }

    public function setTaxRate(float $value)
    {
        $this->taxRate = $value;
        return $this;
    }

    public function setTaxAmount(float $value)
    {
        $this->taxAmount = $value;
        return $this;
    }

    public function setReceiptEmail(string $value)
    {
        $this->receipt_email = $value;
        return $this;
    }

    public function setFormSession(FormSession $value)
    {
        $this->formSession = $value;
        return $this;
    }

    public function setPayment(Payment $value)
    {
        $this->payment = $value;
        return $this;
    }
    public function setProducts(Collection $value)
    {
        $this->products = $value;
        return $this;
    }

    // public function setDeliveryAddress(DeliveryAddress $value)
    // {
    //     $this->deliveryAddress = $value;
    //     return $this;
    // }

    public function charge()
    {
        $this->chargeResponse = PaymentIntent::create([
            "amount" => $this->amount * 100,
            "currency" => $this->currency,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
            "description" => $this->description,
            "receipt_email" => $this->receipt_email
        ]);
        return $this;
    }

    function checkout()
    {
        $checkout_data = [
            'success_url' => route("api.form.session.payment.callback", [
                "payment_id" => $this->payment->id,
                "status" => strtolower(StatusConstants::SUCCESSFUL),
            ]),
            'cancel_url' => route("api.form.session.payment.callback", [
                "payment_id" => $this->payment->id,
                "status" => strtolower(StatusConstants::CANCELLED),
            ]),
            'payment_method_types' => ['card'],
            'mode' => 'payment',
        ];

        // Default shipping info (will be modified for order sheets with discounts)
        $shipping_info = [
            'shipping_address_collection' => ['allowed_countries' => ["CA", "US"]],
            'shipping_options' => [
                [
                    'shipping_rate_data' => [
                        'type' => 'fixed_amount',
                        'fixed_amount' => ['amount' => $this->shippingFee * 100, 'currency' => $this->currency],
                        'display_name' => 'Shipping',
                        'delivery_estimate' => [
                            'minimum' => ['unit' => 'business_day', 'value' => 1],
                            'maximum' => ['unit' => 'business_day', 'value' => 7],
                        ],
                    ],
                ],
            ],
        ];

        // Create Stripe Tax Rate if tax is applicable (standard Stripe approach)
        $taxRateId = null;
        if (isset($this->taxRate) && $this->taxRate > 0) {
            try {
                $isMultiProduct = in_array($this->payment->order_type, ['order_sheet', 'cart']);
                $taxLabel = $isMultiProduct ? 'Tax' : 'HST';
                $tax = $this->stripeClient->taxRates->create([
                    'display_name' => $taxLabel,
                    'inclusive' => false, // Tax is added on top, not included in price
                    'percentage' => $this->taxRate, // Tax rate as percentage (e.g., 13 for 13%)
                    'country' => $this->country ?? 'US',
                    'description' => $taxLabel,
                ]);
                $taxRateId = $tax->id;
            } catch (\Exception $e) {
                // Log error but don't fail checkout if tax rate creation fails
                Log::error("Failed to create Stripe tax rate: " . $e->getMessage());
            }
        }

        $line_items = [];
        // Show products at their original prices
        foreach ($this->products as $product) {
            $originalAmount = $product->selected_price["value"];
            $quantity = isset($product->quantity) ? (int) $product->quantity : 1;

            $line_item = [
                'price_data' => [
                    'currency' => $this->currency,
                    'unit_amount' => (int) round($originalAmount * 100),
                    'product_data' => [
                        'name' => $product->name,
                        // 'images' => [$image],
                    ]
                ],
                'quantity' => $quantity,
            ];

            // Apply tax rate to line item (Stripe will calculate tax on discounted amount)
            if ($taxRateId) {
                $line_item['tax_rates'] = [$taxRateId];
            }

            $line_items[] = $line_item;
        }

        // Apply discount using Stripe's native coupon system
        $discountAmount = isset($this->payment->discount_amount) && $this->payment->discount_amount > 0
            ? (float) $this->payment->discount_amount
            : 0;

        $discountCode = $this->payment->discount_code ?? null;
        $isMultiProduct = in_array($this->payment->order_type, ['order_sheet', 'cart']);

        // For order sheets and cart checkouts with discounts, add shipping as a line item so discount applies to it
        if ($isMultiProduct && $discountAmount > 0 && $discountCode && $this->shippingFee > 0) {
            // Add shipping as a line item so the coupon applies to it
            $shipping_line_item = [
                'price_data' => [
                    'currency' => $this->currency,
                    'unit_amount' => (int) round($this->shippingFee * 100),
                    'product_data' => [
                        'name' => 'Shipping (1-7 business days)',
                    ]
                ],
                'quantity' => 1,
            ];

            // Apply tax rate to shipping line item
            if ($taxRateId) {
                $shipping_line_item['tax_rates'] = [$taxRateId];
            }

            $line_items[] = $shipping_line_item;

            // Don't use shipping_options when shipping is a line item
            $shipping_info = [
                'shipping_address_collection' => ['allowed_countries' => ["CA", "US"]],
            ];
        }

        $checkout_data["line_items"] = $line_items;

        if ($discountAmount > 0 && $discountCode) {
            // Get the discount code model to create Stripe coupon
            $discountModel = \App\Models\DiscountCode::where('code', $discountCode)->first();

            if ($discountModel) {
                try {
                    $couponId = $this->getOrCreateStripeCoupon($discountModel, $discountAmount, strtolower($this->currency));
                    if ($couponId) {
                        $checkout_data["discounts"] = [["coupon" => $couponId]];
                    }
                } catch (\Exception $e) {
                    // Log error but don't fail checkout if coupon creation fails
                    Log::error("Failed to create Stripe coupon for discount: " . $e->getMessage());
                }
            }
        }

        $checkout_data = array_merge($shipping_info, $checkout_data);
        return $this->stripeClient->checkout->sessions->create($checkout_data);
    }

    function verify(array $data)
    {
        $check = $this->stripeClient->checkout->sessions
            ->retrieve($this->payment->payment_reference);

        // Check Stripe session status first
        $stripeStatus = $check->status ?? null; // 'complete', 'expired', 'open'
        $paymentStatus = $check->payment_status ?? null; // 'paid', 'unpaid', 'no_payment_required'

        // Handle cancelled status from callback URL or Stripe session
        if (isset($data["status"]) && $data["status"] == StatusConstants::CANCELLED) {
            $this->payment->update([
                "status" => StatusConstants::CANCELLED,
            ]);
            return;
        }

        // Check if session was expired or cancelled
        if ($stripeStatus === 'expired' || ($stripeStatus === 'complete' && $paymentStatus === 'unpaid')) {
            $this->payment->update([
                "status" => StatusConstants::CANCELLED,
            ]);
            return;
        }

        // Handle successful payment
        if ($paymentStatus == "paid") {
            $stripe_payment = ($check->amount_total / 100);
            $check_amount = $this->payment->total <= $stripe_payment;

            if ($check_amount) {
                $this->payment->update([
                    "status" => StatusConstants::SUCCESSFUL,
                    "paid_at" => now(),
                ]);
                // Only update form session if it exists (not for direct checkout)
                if ($this->payment->formSession) {
                    $this->payment->formSession->update([
                        "status" => StatusConstants::PROCESSING
                    ]);
                }

                try {
                    $paymentIntent = $this->stripeClient->paymentIntents->retrieve($check->payment_intent);
                    $charge = $this->stripeClient->charges->retrieve($paymentIntent->latest_charge);
                    $method = $this->stripeClient->paymentMethods->retrieve($paymentIntent->payment_method);

                    $method_info = [];
                    if ($method->type == "card") {
                        $method_info = [
                            "brand" => $method->card->brand,
                            "last_digits" => $method->card->last4,
                            "exp_month" => $method->card->exp_month,
                            "exp_year" => $method->card->exp_year,
                        ];
                    }

                    $this->payment->update([
                        "receipt_url" => $charge->receipt_url,
                        "metadata" => [
                            "shipping_address" => $method->billing_details->address,
                            "email" => $method->billing_details->email,
                            "phone" => $method->billing_details->phone,
                        ],
                        "method" => $method->type,
                        "method_info" => $method_info

                    ]);
                } catch (\Throwable $th) {
                    //throw $th;
                }

                // Send emails for successful order sheet and cart payments
                if (in_array($this->payment->order_type, ['order_sheet', 'cart'])) {
                    $this->sendOrderSheetEmails();
                }
            } else {
                $this->payment->update([
                    "status" => StatusConstants::FAILED
                ]);
            }
        } elseif ($paymentStatus == "unpaid" && $stripeStatus !== 'expired') {
            // Payment was not completed but session is still open (user might have closed the window)
            // Keep as pending or mark as cancelled based on context
            if (!isset($data["status"])) {
                // If no explicit status from callback, check if session expired
                $this->payment->update([
                    "status" => StatusConstants::CANCELLED,
                ]);
            }
        }
    }

    function refund()
    {
        $check = $this->stripeClient->checkout->sessions
            ->retrieve($this->payment->payment_reference);
        $this->stripeClient->refunds->create(['payment_intent' => $check->payment_intent]);
        $this->payment->update([
            "status" => StatusConstants::REFUNDED,
        ]);
    }

    /**
     * Get or create a Stripe coupon for the discount code
     */
    private function getOrCreateStripeCoupon(\App\Models\DiscountCode $discount, $discountAmount, $currency = 'usd')
    {
        // Create a unique coupon ID based on discount code, type, value, and currency
        // For fixed discounts, include the amount to ensure uniqueness
        $couponIdSuffix = $discount->type == 'fixed'
            ? $discount->code . '_' . $discount->type . '_' . $discountAmount . '_' . $currency
            : $discount->code . '_' . $discount->type . '_' . $discount->value . '_' . $currency;

        // Stripe coupon IDs must be lowercase and alphanumeric with underscores/dashes, max 40 chars
        $couponId = strtolower(preg_replace('/[^a-z0-9_-]/i', '_', $couponIdSuffix));
        // Truncate if too long
        if (strlen($couponId) > 40) {
            $couponId = substr($couponId, 0, 40);
        }

        try {
            // Try to retrieve existing coupon
            $coupon = $this->stripeClient->coupons->retrieve($couponId);
            return $coupon->id;
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Coupon doesn't exist, create it
            $couponData = [
                'id' => $couponId,
                'name' => $discount->code,
            ];

            if ($discount->type == 'percentage') {
                $couponData['percent_off'] = $discount->value;
            } else if ($discount->type == 'fixed') {
                // For fixed discounts, use amount_off with the actual discount amount in cents
                $couponData['amount_off'] = round($discountAmount * 100); // Convert to cents
                $couponData['currency'] = $currency;
            }

            $coupon = $this->stripeClient->coupons->create($couponData);
            return $coupon->id;
        }
    }

    /**
     * Send emails for successful order sheet payments
     */
    private function sendOrderSheetEmails(): void
    {
        try {
            $payment = $this->payment->load(['paymentProducts.product', 'formSession']);
            $formSession = $payment->formSession;

            if (!$formSession) {
                return;
            }

            $metadata = $formSession->metadata['raw'] ?? [];
            $customerEmail = $metadata['email'] ?? $payment->metadata['email'] ?? null;
            $customerName = ($metadata['firstName'] ?? '') . ' ' . ($metadata['lastName'] ?? '');

            if ($customerEmail) {
                // Send customer confirmation email
                \Illuminate\Support\Facades\Notification::route('mail', $customerEmail)
                    ->notify(new \App\Notifications\OrderSheet\Customer\OrderConfirmedNotification($payment));
            }

            // Send admin notification to configured email
            $orderSheetEmail = config('app.order_sheet_email');
            if ($orderSheetEmail) {
                \Illuminate\Support\Facades\Notification::route('mail', $orderSheetEmail)
                    ->notify(new \App\Notifications\OrderSheet\Admin\NewOrderNotification($payment));
            }

            // Also send to admin users (for backward compatibility)
            $admins = \App\Models\User::whereIn('role', \App\Helpers\AppConstants::ADMIN_ROLES)
                ->where('team', \App\Helpers\AppConstants::TEAM_ZENOVATE)
                ->get();

            if ($admins->isNotEmpty()) {
                \Illuminate\Support\Facades\Notification::send(
                    $admins,
                    new \App\Notifications\OrderSheet\Admin\NewOrderNotification($payment)
                );
            }
        } catch (\Throwable $th) {
            // Log error but don't fail payment processing
            Log::error('Failed to send order sheet emails', [
                'payment_id' => $this->payment->id,
                'error' => $th->getMessage(),
            ]);
        }
    }
}
