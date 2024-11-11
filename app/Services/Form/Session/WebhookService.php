<?php

namespace App\Services\Form\Session;

use App\Helpers\AppConstants;
use App\Helpers\StatusConstants;
use App\Models\FormSession;
use App\Models\FormSessionActivity;
use App\Models\User;
use App\Notifications\Form\Session\Admin\ConfirmedNotification as AdminConfirmedNotification;
use App\Notifications\Form\Session\Customer\ConfirmedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class WebhookService
{
    public function handle(array $data)
    {

        return DB::transaction(function () use ($data) {
            $event = $data["event_type"];
            if (!in_array($event, ["form.completed"])) {
                return;
            }

            $session = FormSession::where([
                "status" => StatusConstants::AWAITING_CONFIRMATION,
                "docuseal_id" => $data["data"]["template"]["id"]
            ])->first();

            if (empty($session)) {
                return;
            }


            $signer_role = ucwords(str_replace("_", " ", $data["data"]["role"]));
            $signer_email = $data["data"]["email"];

            if ($event == "form.completed") {
                FormSessionActivity::create([
                    "form_session_id" => $session->id,
                    "activity" => AppConstants::ACIVITY_SIGNED,
                    "message" => "Order signed by {$signer_role} ($signer_email)."
                ]);

                if ($signer_email == env("SKYCARE_ADMIN_EMAIL")) {
                    $session->update([
                        "status" => StatusConstants::COMPLETED,
                    ]);

                    FormSessionActivity::firstOrCreate([
                        "form_session_id" => $session->id,
                        "activity" => AppConstants::ACIVITY_CONFIRMED,
                    ], [
                        "message" => "Order signing completed."
                    ]);

                    $dto = new DTOService($session);
                    Notification::route('mail', [
                        $dto->email() => $dto->fullName(),
                    ])->notify(new ConfirmedNotification($session));

                    $admins = User::whereIn("role", AppConstants::ADMIN_ROLES)
                        ->where("team", AppConstants::TEAM_ZENOVATE)
                        ->get();

                    Notification::send($admins, new AdminConfirmedNotification($session));
                }
            }

        });

    }


}
