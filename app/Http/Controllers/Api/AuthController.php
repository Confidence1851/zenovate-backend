<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GeneralException;
use App\Helpers\ApiConstants;
use App\Helpers\ApiHelper;
use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    function __construct(public AuthService $authService) {

    }
    function login(Request $request)
    {
        try {
            $process = $this->authService->login($request->all());
            return ApiHelper::validResponse(
                'Logged in successfully',
                $process
            );
        } catch (ValidationException $e) {
            return ApiHelper::inputErrorResponse(
                $e->getMessage(),
                ApiConstants::VALIDATION_ERR_CODE,
                $request,
                $e
            );
        } catch (GeneralException $e) {
            return ApiHelper::problemResponse(
                $e->getMessage(),
                $e->getCode()
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }

    function register(Request $request)
    {
        try {
            $process = $this->authService->register($request->all());
            return ApiHelper::validResponse(
                'Registered successfully',
                $process
            );
        } catch (ValidationException $e) {
            return ApiHelper::inputErrorResponse(
                $e->getMessage(),
                ApiConstants::VALIDATION_ERR_CODE,
                $request,
                $e
            );
        } catch (GeneralException $e) {
            return ApiHelper::problemResponse(
                $e->getMessage(),
                $e->getCode()
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }

    function forgotPassword(Request $request)
    {
        try {
            $this->authService->forgotPassword($request->all());
            return ApiHelper::validResponse(
                'Password reset link sent successfully',
            );
        } catch (ValidationException $e) {
            return ApiHelper::inputErrorResponse(
                $e->getMessage(),
                ApiConstants::VALIDATION_ERR_CODE,
                $request,
                $e
            );
        } catch (GeneralException $e) {
            return ApiHelper::problemResponse(
                $e->getMessage(),
                $e->getCode()
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }

    function resetPassword(Request $request)
    {
        try {
            $this->authService->resetPassword($request->all());
            return ApiHelper::validResponse(
                'Password reset successfully',
            );
        } catch (ValidationException $e) {
            return ApiHelper::inputErrorResponse(
                $e->getMessage(),
                ApiConstants::VALIDATION_ERR_CODE,
                $request,
                $e
            );
        } catch (GeneralException $e) {
            return ApiHelper::problemResponse(
                $e->getMessage(),
                $e->getCode()
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }

}
