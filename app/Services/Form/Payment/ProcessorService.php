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
            // "receipt_url" => $response->receipt_url,
            "address" => $metadata["streetAddress"] ?? null,
            "postal_code" => $metadata["postalZipCode"] ?? null,
            "city" => $metadata["city"] ?? null,
            "country" => $metadata["country"] ?? null,
            "province" => $metadata["stateProvince"] ?? null,
            "phone" => $metadata["phoneNumber"] ?? null,
            "status" => StatusConstants::PENDING,
            "discount_code" => $data["discount_code"] ?? null,
            "discount_amount" => $data["discount_amount"] ?? null,
            // "metadata" => json_encode($data)
        ]);

        foreach ($data["products"] as $product) {
            PaymentProduct::firstOrCreate([
                "payment_id" => $payment->id,
                "product_id" => $product->id,
            ], [
                "price" => $product->selected_price
            ]);
        }

        $intent = $service->setCurrency($data["currency"])
            ->setCountry($data["country_code"])
            ->setDescription("Zenovate")
            ->setShippingFee($payment["shipping_fee"])
            ->setProducts($data["products"])
            ->setPayment($payment)
            ->checkout();

        $payment->update([
            "payment_reference" => $intent->id
        ]);

        // if ($notify_admin_of_payment) {
        //     ApplicationFormService::notifyPayment($payment);
        // }
        return [
            "payment" => $payment,
            "redirect_url" => $intent->url
        ];
    }

    static function getShippingFee(FormSession $formSession)
    {
        return 60;
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
        $service->setPayment($payment)
            ->verify($data);

        $hash = base64_encode((new EncryptionService)->encrypt([
            "key" => "payment",
            "value" => $payment->form_session_id
        ]));
        $redirect_url = env("FRONTEND_APP_URL") . "/redirect/$hash";
        return $redirect_url;
    }
}
