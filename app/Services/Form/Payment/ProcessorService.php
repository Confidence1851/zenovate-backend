<?php

namespace App\Services\Form\Payment;

use App\Helpers\StatusConstants;
use App\Models\FormSession;
use App\Models\Payment;

class ProcessorService
{
    function initiate(FormSession $session, array $data)
    {
        $service = new StripeService;

        $metadata = $session->metadata["raw"];
        $payment = Payment::create([
            "form_session_id" => $session->id,
            "reference" => uniqid(),
            "gateway" => "Stripe",
            "currency" => $data["currency"],
            "amount" => $data["total"],
            "fees" => null,
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

        $intent = $service->setCurrency($data["currency"])
            ->setCountry($data["country_code"])
            ->setDescription("Zenovate")
            ->setShippingFee($data["shipping_fee"])
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


     function callback( array $data){
        $payment = Payment::whereHas("formSession")->findOrFail($data["payment_id"]);
        $service = new StripeService;
        $service->setPayment($payment)
            ->verify($data);

        $redirect_url = env("FRONTEND_APP_URL") . "/" . $payment->form_session_id;
        return $redirect_url;
     }
}
