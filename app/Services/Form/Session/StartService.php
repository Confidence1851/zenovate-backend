<?php

namespace App\Services\Form\Session;

use App\Helpers\StatusConstants;
use App\Models\FormSession;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StartService
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
        return FormSession::create([
            "status" => StatusConstants::PENDING,
            "metadata" => [
                "user_agent" => $data["userAgent"] ?? null,
                "location" => $data["location"] ?? null,
            ]
        ]);
    }
}
