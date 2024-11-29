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
        // dd([
        //     "amount" => $this->amount * 100,
        //     "currency" => $this->currency,
        //     "source" => $this->source,
        //     "description" => $this->description,
        //     "receipt_email" => $this->receipt_email
        // ]);
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
        foreach ($this->products as $product) {
            $line_items[] = [
                'price_data' => [
                    'currency' => $this->currency,
                    'unit_amount' => $product->price * 100,
                    'product_data' => [
                        'name' => $product->name,
                        // 'images' => [$image],
                    ]
                ],
                'quantity' => 1,
            ];
        }
        $checkout_data["line_items"] = $line_items;
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
                    $this->payment->update([
                        "receipt_url" => $charge->receipt_url
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
}
