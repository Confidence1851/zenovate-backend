<?php

namespace App\Services\General;

use App\Helpers\AppConstants;
use App\Models\ContactUsMessage;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Notifications\NewContactMessageNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WebsiteService
{
    public function contactUs(array $data)
    {
        $validator = Validator::make($data, [
            "email" => "required|email",
            "name" => "required|string",
            "phone" => "required|string",
            "subject" => "required|string",
            "message" => "required|string",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $message = ContactUsMessage::create($validator->validated());

        $admins = User::whereIn("role", AppConstants::ADMIN_ROLES)
            ->where("team", AppConstants::TEAM_ZENOVATE)
            ->get();

        Notification::send($admins, new NewContactMessageNotification($message));

    }


    public function newsletterSubscriber(array $data)
    {
        $validator = Validator::make($data, [
            "email" => "required|email|unique:newsletter_subscribers,email",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        NewsletterSubscriber::create($validator->validated());
    }
}
