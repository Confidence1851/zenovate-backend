<?php

namespace App\Services\Form\Payment;

use App\Helpers\StatusConstants;
use App\Models\FormSession;
use App\Models\Payment;
use App\Models\PaymentProduct;
use App\Services\Form\Session\UpdateService;
use Illuminate\Support\Collection;
use Stripe\PaymentIntent;

class StripeService
{
    private $stripeClient;

    public $amount, $currency = "USD", $source, $description, $receipt_email, $chargeResponse, $shippingFee, $country;
    public FormSession $formSession;
    public Payment $payment;
    public Collection $products;
    public function __construct()
    {
        $this->stripeClient = new \Stripe\StripeClient(
            env("STRIPE_SK")
        );
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

        $line_items = [];
        // Show products at their original prices
        foreach ($this->products as $product) {
            $originalAmount = $product->selected_price["value"];
            
            $line_items[] = [
                'price_data' => [
                    'currency' => $this->currency,
                    'unit_amount' => (int) round($originalAmount * 100),
                    'product_data' => [
                        'name' => $product->name,
                        // 'images' => [$image],
                    ]
                ],
                'quantity' => 1,
            ];
        }
        
        $checkout_data["line_items"] = $line_items;
        
        // Apply discount using Stripe's native coupon system
        $discountAmount = isset($this->payment->discount_amount) && $this->payment->discount_amount > 0 
            ? (float) $this->payment->discount_amount 
            : 0;
        
        $discountCode = $this->payment->discount_code ?? null;
        
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
                    \Log::error("Failed to create Stripe coupon for discount: " . $e->getMessage());
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


        $stripe_payment = ($check->amount_total / 100);
        if ($check->payment_status == "paid") {
            $check_amount = $this->payment->total <= $stripe_payment;
            if ($check_amount) {
                $this->payment->update([
                    "status" => StatusConstants::SUCCESSFUL,
                    "paid_at" => now(),
                ]);
                $this->payment->formSession->update([
                    "status" => StatusConstants::PROCESSING
                ]);

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
            } else {
                $this->payment->update([
                    "status" => StatusConstants::FAILED
                ]);
            }
        } elseif ($data["status"] == StatusConstants::CANCELLED) {
            $this->payment->update([
                "status" => StatusConstants::CANCELLED,
            ]);
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
}
