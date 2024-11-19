<?php

namespace App\Services\Form\Session;

use App\Exceptions\GeneralException;
use App\Helpers\AppConstants;
use App\Helpers\Helper;
use App\Helpers\StatusConstants;
use App\Models\FormSession;
use App\Models\FormSessionActivity;
use App\Notifications\Form\Session\Customer\DeclinedNotification;
use App\Notifications\Form\Session\Customer\StatusNotification;
use App\Services\Form\Payment\StripeService;
use App\Services\General\Pdf\MpdfService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

class SignService
{
    public $client;

    function __construct(public FormSession $session)
    {
        $this->client = Http::withHeaders([
            "X-Auth-Token" => env('DOCUSEAL_API_KEY'),
            "Content-Type" => "application/json",
        ]);
    }


    function sendToSigners()
    {
        $url = "https://api.docuseal.com/submissions";


        $response = $this->client->post($url, [
            "template_id" => $this->session->docuseal_id,
            "send_email" => true,
            'submitters' => [
                [
                    'name' => 'zenovate_admin',
                    'email' => env("ZENOVATE_ADMIN_EMAIL")
                ],
                // [
                //     'name' => 'skycare_admin',
                //     'email' => env("SKYCARE_ADMIN_EMAIL")
                // ]
            ],
        ]);

        if ($response->successful()) {
            return $response->json(); // returns document ID or signing link
        } else {
            throw new GeneralException("Failed to send document for signing");
        }
    }

    function createTemplate()
    {
        $url = "https://api.docuseal.com/templates/pdf";

        $this->generatePdf(true);
        $content = file_get_contents(storage_path("app/" . $this->session->pdf_path));
        $base64String = base64_encode($content);
        $name = "Session-#" . $this->session->reference;
        $payload = [
            "name" => $name,
            "documents" => [
                [
                    "name" => $name,
                    "file" => $base64String,
                ]
            ]
        ];


        // Send the request
        $response = $this->client->post($url, $payload);

        // Check for errors
        if ($response->failed()) {
            throw new GeneralException("Failed to document template for signing");
        }
        // Return the response body
        return $response->json();
    }

    public function generatePdf(bool $save = false)
    {
        $file_path = null;
        try {
            $data = [
                "dto" => new DTOService($this->session),
                "payment" => $this->session->completedPayment,
            ];

            $service = (new MpdfService)->generate(view("templates.forms.pdf.summary", $data));
            if (!$save) {
                return $service->output();
            }

            $folder = storage_path("app/pdf/form_sessions");
            Helper::withDir($folder);
            $file_name = $this->session->id . ".pdf";
            $file_path = $folder . "/" . $file_name;
            $relative_file_path = "pdf/form_sessions/" . $file_name;
            $service->save($file_path);

            $this->session->update([
                "pdf_path" => $relative_file_path
            ]);
            $this->session->refresh();
        } catch (\Throwable $e) {
            logger($e->getMessage(), [$e->getTrace()]);
            try {
                if (!empty($file_path)) {
                    unlink($file_path);
                }
            } catch (\Throwable $th) {
                logger("Unable to delete generated session pdf");
            }
        }
    }

    function handleAdminReview(array $data)
    {

        if ($this->session->status != StatusConstants::AWAITING_REVIEW) {
            throw new GeneralException("This order has already been reviewed!");
        }

        return DB::transaction(function () use ($data) {
            $user = auth()->user();
            $dto = new DTOService($this->session);

            if ($data["status"] == StatusConstants::NO) {
                $this->session->update([
                    "status" => StatusConstants::DECLINED,
                    "comment" => $data["comment"]
                ]);

                FormSessionActivity::firstOrCreate([
                    "form_session_id" => $this->session->id,
                    "activity" => AppConstants::ACIVITY_REVIEWED,
                ], [
                    "user_id" => $user->id,
                    "message" => "Order reviewed and declined by {$user->full_name}. Comment: " . $data["comment"]
                ]);

                (new StripeService())->setPayment($this->session->completedPayment)
                    ->refund();

                Notification::route('mail', [
                    $dto->email() => $dto->fullName(),
                ])->notify(new DeclinedNotification($this->session));

                return [
                    "message" => "Order request declined successfully!"
                ];
            }

            $response = $this->createTemplate();


            $this->session->update([
                "docuseal_id" => $response["id"],
                "docuseal_url" => $response["documents"][0]["url"],
                "status" => StatusConstants::AWAITING_CONFIRMATION,
            ]);

            $this->session->refresh();

            $this->sendToSigners();

            FormSessionActivity::firstOrCreate([
                "form_session_id" => $this->session->id,
                "activity" => AppConstants::ACIVITY_REVIEWED,
            ], [
                "user_id" => $user->id,
                "message" => "Order reviewed and approved by {$user->full_name}."
            ]);


            return [
                "message" => "Order request approved and sent for signing successfully!"
            ];

        });

    }

}
