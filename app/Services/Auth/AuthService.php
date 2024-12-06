<?php

namespace App\Services\Auth;

use App\Exceptions\GeneralException;
use App\Helpers\AppConstants;
use App\Helpers\Helper;
use App\Models\User;
use App\Notifications\CustomerResetPasswordNotification;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function login(array $data)
    {
        return DB::transaction(function () use ($data) {
            $validator = Validator::make($data, [
                "email" => "required|email|exists:users,email",
                "password" => "required|string",
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            // Attempt to authenticate the user
            $user = User::where('email', $data["email"])->first();

            if (!$user || !Hash::check($data["password"], $user->password)) {
                throw new GeneralException("Invalid credentials", 401);
            }

            // Generate a token for the user
            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];
        });
    }


    public function register(array $data)
    {
        return DB::transaction(function () use ($data) {
            $validator = Validator::make($data, [
                "first_name" => "required|string|max:50",
                "last_name" => "required|string|max:50",
                "email" => "required|email|unique:users,email",
                "password" => "required|string",
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $data = $validator->validated();
            $data["role"] = AppConstants::ROLE_CUSTOMER;
            $user = (new UserService)->save($data);
            (new CustomerService)->save($user, []);
            // Generate a token for the user
            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];
        });
    }

    public function forgotPassword(array $data)
    {
        $validator = Validator::make($data, [
            "email" => "required|email|exists:users,email",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Attempt to authenticate the user
        $user = User::where('email', $data["email"])->first();

        $hash = Helper::encrypt(json_encode(["user_id" => $user->id, "expires_at" => now()->addHour()]));
        Notification::send($user, new CustomerResetPasswordNotification($hash));
    }

    public function resetPassword(array $data)
    {
        $validator = Validator::make($data, [
            "hash" => "required|string",
            "password" => "required|string",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $hash_data = json_decode(Helper::decrypt($data["hash"]), true);

        if (empty($hash_data) || !Carbon::parse($hash_data["expires_at"])->isFuture()) {
            throw new GeneralException("This link has expired. Kindly resend your request.");
        }
        // Attempt to authenticate the user
        $user = User::findOrFail($hash_data["user_id"]);

        $user->update(["password" => Hash::make($data["password"])]);
    }

}
