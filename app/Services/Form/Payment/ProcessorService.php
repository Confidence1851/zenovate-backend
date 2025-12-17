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
        $orderType = $data["order_type"] ?? "regular";
        $isOrderSheet = $orderType === "order_sheet";
        $isCart = $orderType === "cart";
        $isMultiProduct = $isOrderSheet || $isCart;
        $customerInfo = $data["customer_info"] ?? null;

        // Order sheet and cart checkouts always use USD
        $currency = $isMultiProduct ? 'USD' : ($data["currency"] ?? 'USD');

        $payment = Payment::create([
            "form_session_id" => $session->id,
            "reference" => self::generateReference(),
            "sub_total" => $data["sub_total"],
            "gateway" => "Stripe",
            "currency" => $currency,
            "total" => $data["total"],
            "shipping_fee" => $data["shipping_fee"],
            "address" => $isMultiProduct ? ($customerInfo["shipping_address"] ?? $customerInfo["location"] ?? null) : ($metadata["streetAddress"] ?? null),
            "postal_code" => $isMultiProduct ? null : ($metadata["postalZipCode"] ?? null),
            "city" => $isMultiProduct ? null : ($metadata["city"] ?? null),
            "country" => $isMultiProduct ? null : ($metadata["country"] ?? null),
            "province" => $isMultiProduct ? null : ($metadata["stateProvince"] ?? null),
            "phone" => $isMultiProduct ? ($customerInfo["phone"] ?? null) : ($metadata["phoneNumber"] ?? null),
            "status" => StatusConstants::PENDING,
            "discount_code" => $data["discount_code"] ?? null,
            "discount_amount" => $data["discount_amount"] ?? null,
            "order_type" => $orderType,
            "account_number" => $isMultiProduct ? ($customerInfo["account_number"] ?? null) : null,
            "location" => $isMultiProduct ? ($customerInfo["location"] ?? null) : null,
            "shipping_address" => $isMultiProduct ? ($customerInfo["shipping_address"] ?? null) : null,
            "additional_information" => $isMultiProduct ? ($customerInfo["additional_information"] ?? null) : null,
        ]);

        foreach ($data["products"] as $product) {
            $quantity = isset($product->quantity) ? (int) $product->quantity : 1;
            PaymentProduct::firstOrCreate([
                "payment_id" => $payment->id,
                "product_id" => $product->id,
            ], [
                "price" => $product->selected_price,
                "quantity" => $quantity,
            ]);
        }

        // Ensure payment is saved and refreshed before passing to StripeService
        $payment->refresh();

        // Order sheet and cart checkouts always use USD and US country code
        $countryCode = $isMultiProduct ? 'US' : ($data["country_code"] ?? 'US');

        $service->setCurrency($currency)
            ->setCountry($countryCode)
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
        return (float) config('checkout.shipping_fee', 60);
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
            $siteUrl = rtrim(config('frontend.site_url'), '/');
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
            $formUrl = rtrim(config('frontend.form_url'), '/');
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
