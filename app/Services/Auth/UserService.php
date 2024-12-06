<?php

namespace App\Services\Auth;

use App\Helpers\AppConstants;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserService
{
    private function validate(array $data, $id = null): array
    {
        $validator = Validator::make($data, [
            "first_name" => "required|string",
            "last_name" => "required|string",
            "email" => "required|email|unique:users,email" . empty($id) ? '' : ",$id",
            "phone" => "nullable|string",
            "password" => "nullable|string",
            "team" => "nullable|string",
            "role" => "required|string|" . Rule::in(AppConstants::ROLES),
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
            $data["team"] = $data["team"] ?? AppConstants::TEAM_ZENOVATE;
            $user = User::create($data);
        } else {
            $user = User::find($id);
            $user->update($data);
            $user->refresh();
        }
        return $user;
    }
}
