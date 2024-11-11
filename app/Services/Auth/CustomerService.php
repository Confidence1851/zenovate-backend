<?php

namespace App\Services\Auth;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    private function validate(array $data): array
    {
        $validator = Validator::make($data, [
            "dob" => "required|date",
            "preferred_contact_method" => "required|string|in:email,phone",
            "address" => "nullable|string",
            "postal_code" => "nullable|string",
            "city" => "nullable|string",
            "province" => "nullable|string",
            "country" => "nullable|string",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public function save(User $user, array $data)
    {
        $data = $this->validate($data);
        return Customer::updateOrCreate([
            "user_id" => $user->id,
        ], $data);
    }
}
