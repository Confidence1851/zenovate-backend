<?php

namespace App\Services\General;

use App\Helpers\AppConstants;
use App\Models\ContactUsMessage;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Notifications\NewContactMessageNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
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
            "recaptcha_token" => "required|string",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Verify reCAPTCHA token
        $recaptchaSecret = config('services.recaptcha.secret');
        if (!$recaptchaSecret) {
            Log::warning('reCAPTCHA secret key not configured');
            throw new \Exception('reCAPTCHA verification is not configured');
        }

        $recaptchaResponse = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $recaptchaSecret,
            'response' => $data['recaptcha_token'],
            'remoteip' => request()->ip(),
        ]);

        $recaptchaResult = $recaptchaResponse->json();

        if (!$recaptchaResult['success'] || ($recaptchaResult['score'] ?? 0) < 0.5) {
            Log::warning('reCAPTCHA verification failed', [
                'result' => $recaptchaResult,
                'ip' => request()->ip(),
            ]);
            throw new ValidationException(
                Validator::make([], []),
                ['recaptcha_token' => ['reCAPTCHA verification failed. Please try again.']]
            );
        }

        // Remove recaptcha_token from data before saving
        unset($data['recaptcha_token']);
        $message = ContactUsMessage::create($data);

        // Send notification to admin users
        $admins = User::whereIn("role", AppConstants::ADMIN_ROLES)
            ->where("team", AppConstants::TEAM_ZENOVATE)
            ->get();
        Log::info('Admins:', $admins->toArray());
        Notification::send($admins, new NewContactMessageNotification($message));

        // Also send notification to contact email from config
        $contactEmail = config('emails.contact');
        if ($contactEmail) {
            Notification::route('mail', $contactEmail)
                ->notify(new NewContactMessageNotification($message));
        }
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
