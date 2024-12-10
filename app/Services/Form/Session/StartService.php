<?php

namespace App\Services\Form\Session;

use App\Helpers\Helper;
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
            "reference" => self::generateReference(),
            "metadata" => [
                "user_agent" => $data["userAgent"] ?? null,
                "location" => $data["location"] ?? null,
            ]
        ]);
    }

    public function generateReference()
    {
        $code = "FS-" . Helper::getRandomToken(6, true);
        $check =  FormSession::where("reference" , $code)->exists();
        if($check){
            return self::generateReference();
        }
        return $code;
    }
}
