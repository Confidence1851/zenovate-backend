<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserService
{
    private function validate(array $data, $id = null): array
    {
        $validator = Validator::make($data, [
            "first_name" => "required|string",
            "last_name" => "required|string",
            "email" => "required|email|unique:users,email,$id",
            "phone" => "required|string",
            "role" => "required|string|in",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public function save(array $data, $id = null)
    {
        $data = $this->validate($data, $id);
        if (empty($id)) {
            $data["password"] = Hash::make($data["password"] ?? uniqid());
            $user = User::create($data);
        } else {
            $user = User::find($id);
            $user->update($data);
            $user->refresh();
        }
        return $user;
    }
}
