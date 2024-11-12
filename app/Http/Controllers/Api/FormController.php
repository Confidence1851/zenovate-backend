<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GeneralException;
use App\Helpers\ApiConstants;
use App\Helpers\ApiHelper;
use App\Helpers\StatusConstants;
use App\Http\Controllers\Controller;
use App\Models\FormSession;
use App\Models\Product;
use App\Services\Form\Session\StartService;
use App\Services\Form\Session\UpdateService;
use App\Services\Form\Payment\ProcessorService;
use App\Services\Form\Session\WebhookService;
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
        } catch (GeneralException $e) {
            return ApiHelper::problemResponse(
                $e->getMessage(),
                ApiConstants::BAD_REQ_ERR_CODE
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }

    function updateSession(Request $request)
    {
        try {
            $data = (new UpdateService)->handle($request->all());
            return ApiHelper::validResponse(
                'Session updated successfully',
                $data
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
                ApiConstants::BAD_REQ_ERR_CODE
            );
        } catch (Throwable $e) {
            // dd($e);
            return $this->throwableError($e);
        }
    }


    function paymentCallback(Request $request, $payment_id, $status)
    {
        try {
            $request["payment_id"] = $payment_id;
            $request["status"] = ucfirst($status);
            $url = (new ProcessorService)->callback($request->all());
            return redirect()->away($url);
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
                ApiConstants::BAD_REQ_ERR_CODE
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }

    function productIndex()
    {
        try {
            return ApiHelper::validResponse(
                'Products retrieved successfully',
                Product::get([
                    "id",
                    "name",
                    "subtitle",
                    "description",
                    "price"
                ])
            );
        } catch (GeneralException $e) {
            return ApiHelper::problemResponse(
                $e->getMessage(),
                ApiConstants::BAD_REQ_ERR_CODE
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }

    function info($id)
    {
        try {
            $form = FormSession::whereIn("status", [
                StatusConstants::PENDING,
                StatusConstants::PROCESSING,
            ])->find($id);
            if (empty($form)) {
                return ApiHelper::problemResponse(
                    "Invalid session",
                    ApiConstants::NOT_FOUND_ERR_CODE,
                );
            }

            $payments = $form->payments;
            $paid = false;
            $message = null;
            if (!empty($payments)) {
                $paid = $form->completedPayment()->exists();
                if ($paid) {
                    $message = "Your payment was successful, kindly proceed to the next step.";
                } else {
                    $last_payment = $payments[0] ?? null;
                    if (!empty($last_payment)) {
                        if ($last_payment->status == StatusConstants::FAILED) {
                            $message = "Failed to verify your payment attempt; Kindly try again!";
                        } elseif ($last_payment->status == StatusConstants::CANCELLED) {
                            $message = "It appears you cancelled  your payment attempt; Kindly try again!";
                        }
                    }
                }
            }
            return ApiHelper::validResponse(
                'Products retrieved successfully',
                [
                    "id" => $form->id,
                    "formData" => $form->metadata["raw"] ?? null,
                    "payment" => [
                        "success" => $paid,
                        "attempts" => $payments->count(),
                        "message" => $message ?? null
                    ]
                ]
            );
        } catch (GeneralException $e) {
            return ApiHelper::problemResponse(
                $e->getMessage(),
                ApiConstants::BAD_REQ_ERR_CODE
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }

    function webhookHandler(Request $request)
    {
        try {
            $process = (new WebhookService)->handle($request->all());
            return ApiHelper::validResponse(
                'Webhook processed successfully',
            );
        } catch (GeneralException $e) {
            return ApiHelper::problemResponse(
                $e->getMessage(),
                ApiConstants::BAD_REQ_ERR_CODE
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }
}
