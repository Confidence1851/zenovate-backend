<?php

namespace App\Service\Form\Session;

use App\Models\FormSession;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateService
{
    private function validate(array $data): array
    {
        $validator = Validator::make($data, [

        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public function handle(array $data)
    {
        logger("Session data", $data);
        return FormSession::create([
            "metadata" => [
                "user_agent" => $data["userAgent"] ?? null,
                "location" => $data["location"] ?? null,
            ]
        ]);
    }
}
