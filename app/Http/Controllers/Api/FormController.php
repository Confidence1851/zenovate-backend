<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiConstants;
use App\Helpers\ApiHelper;
use App\Http\Controllers\Controller;
use App\Service\Form\Session\StartService;
use App\Service\Form\Session\UpdateService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class FormController extends Controller
{
    function startSession(Request $request)
    {
        try {
            $session = (new StartService)->handle($request->all());
            return ApiHelper::validResponse(
                'Session started successfully',
                ["id" => $session->id]
            );
        } catch (ValidationException $e) {
            return ApiHelper::inputErrorResponse(
                $e->getMessage(),
                ApiConstants::VALIDATION_ERR_CODE,
                $request,
                $e
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }

    function updateSession(Request $request)
    {
        try {
            $session = (new UpdateService)->handle($request->all());
            return ApiHelper::validResponse(
                'Session updated successfully',
            );
        } catch (ValidationException $e) {
            return ApiHelper::inputErrorResponse(
                $e->getMessage(),
                ApiConstants::VALIDATION_ERR_CODE,
                $request,
                $e
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }

    function completeSession(Request $request)
    {
        try {
            $session = (new StartService)->handle();
            return ApiHelper::validResponse(
                'Session completed successfully',
            );
        } catch (ValidationException $e) {
            return ApiHelper::inputErrorResponse(
                $e->getMessage(),
                ApiConstants::VALIDATION_ERR_CODE,
                $request,
                $e
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }
}
