<?php

namespace App\Services\Form\Payment;

use App\Helpers\EncryptionService;
use App\Helpers\Helper;
use App\Helpers\StatusConstants;
use App\Models\FormSession;
use App\Models\Payment;
use App\Models\PaymentProduct;

class ProcessorService
{
    function initiate(FormSession $session, array $data)
    {
        $service = new StripeService;

        $metadata = $session->metadata["raw"];
        $payment = Payment::create([
            "form_session_id" => $session->id,
            "reference" => self::generateReference(),
            "sub_total" => $data["sub_total"],
            "gateway" => "Stripe",
            "currency" => $data["currency"],
            "total" => $data["total"],
            "shipping_fee" => $data["shipping_fee"],
            "address" => $metadata["streetAddress"] ?? null,
            "postal_code" => $metadata["postalZipCode"] ?? null,
            "city" => $metadata["city"] ?? null,
            "country" => $metadata["country"] ?? null,
            "province" => $metadata["stateProvince"] ?? null,
            "phone" => $metadata["phoneNumber"] ?? null,
            "status" => StatusConstants::PENDING,
            "discount_code" => $data["discount_code"] ?? null,
            "discount_amount" => $data["discount_amount"] ?? null,
        ]);

        foreach ($data["products"] as $product) {
            PaymentProduct::firstOrCreate([
                "payment_id" => $payment->id,
                "product_id" => $product->id,
            ], [
                "price" => $product->selected_price
            ]);
        }

        // Ensure payment is saved and refreshed before passing to StripeService
        $payment->refresh();

        $service->setCurrency($data["currency"])
            ->setCountry($data["country_code"])
            ->setDescription("Zenovate")
            ->setShippingFee($payment["shipping_fee"])
            ->setProducts($data["products"])
            ->setPayment($payment);

        // Set tax rate if provided
        if (isset($data["tax_rate"]) && $data["tax_rate"] > 0) {
            $service->setTaxRate($data["tax_rate"]);
        }
        if (isset($data["tax_amount"]) && $data["tax_amount"] > 0) {
            $service->setTaxAmount($data["tax_amount"]);
        }

        $intent = $service->checkout();

        $payment->update([
            "payment_reference" => $intent->id
        ]);

        return [
            "payment" => $payment,
            "redirect_url" => $intent->url
        ];
    }

    static function getShippingFee(?FormSession $formSession = null, ?\App\Models\Product $product = null)
    {
        // Check product-specific shipping fee first
        if ($product && $product->shipping_fee !== null) {
            return (float) $product->shipping_fee;
        }

        // Fallback to global config
        return (float) config('checkout.shipping_fee', env('CHECKOUT_SHIPPING_FEE', 60));
    }

    public static function generateReference()
    {
        $code = "PR-" . Helper::getRandomToken(6, true);
        $check = Payment::where("reference", $code)->exists();
        if ($check) {
            return self::generateReference();
        }
        return $code;
    }

    function callback(array $data)
    {
        $payment = Payment::whereHas("formSession")->findOrFail($data["payment_id"]);
        $service = new StripeService;
        $service->setPayment(value: $payment)
            ->verify($data);

        // Refresh payment to get updated status after verification
        $payment->refresh();

        // Determine redirect URL based on payment status and checkout type
        $formSession = $payment->formSession;
        $status = strtolower($payment->status);

        if ($formSession && $formSession->isDirectCheckout()) {
            // Direct checkout redirects - use SITE URL
            $siteUrl = env("FRONTEND_APP_SITE_URL", env("FRONTEND_APP_URL"));
            if ($payment->status === StatusConstants::SUCCESSFUL) {
                $redirect_url = $siteUrl . "/checkout/success?ref={$payment->reference}";
            } elseif ($payment->status === StatusConstants::CANCELLED) {
                $redirect_url = $siteUrl . "/checkout/cancelled?ref={$payment->reference}";
            } else {
                // Failed or other status
                $redirect_url = $siteUrl . "/checkout/error?ref={$payment->reference}&status={$status}";
            }
        } else {
            // Form-based checkout - redirect to result page with encrypted hash - use FORM URL
            $formUrl = env("FRONTEND_APP_URL");
            $hash = base64_encode((new EncryptionService)->encrypt([
                "key" => "payment",
                "value" => $payment->form_session_id
            ]));

            // Add status parameter if not successful
            if ($payment->status === StatusConstants::SUCCESSFUL) {
                $redirect_url = $formUrl . "/r/$hash";
            } else {
                $redirect_url = $formUrl . "/r/$hash?status={$status}";
            }
        }

        return $redirect_url;
    }
}
