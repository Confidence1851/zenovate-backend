<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GeneralException;
use App\Helpers\ApiConstants;
use App\Helpers\ApiHelper;
use App\Helpers\AppConstants;
use App\Helpers\StatusConstants;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\FormSession;
use App\Models\FormSessionActivity;
use App\Models\Product;
use App\Services\Form\Session\StartService;
use App\Services\Form\Session\UpdateService;
use App\Services\Form\Payment\ProcessorService;
use App\Services\Form\Session\WebhookService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
            if (!empty($p = $data["products"] ?? null)) {
                $data["products"] = ProductResource::collection($p);
            }
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
                ProductResource::collection(
                    Product::where('status', StatusConstants::ACTIVE)->get()
                )
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

    function productInfo($id)
    {
        try {
            return ApiHelper::validResponse(
                'Product retrieved successfully',
                ProductResource::make(
                    Product::where('status', StatusConstants::ACTIVE)
                        ->where("slug", $id)
                        ->firstOrFail()
                )
            );
        } catch (ModelNotFoundException $e) {
            return ApiHelper::problemResponse(
                "No product dound with the id provided.",
                ApiConstants::BAD_REQ_ERR_CODE
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

    function recreate(Request $request, $id)
    {
        try {
            $form = FormSession::where("user_id", $request->user()->id)->find($id);
            if (empty($form)) {
                return ApiHelper::problemResponse(
                    "Invalid session",
                    ApiConstants::NOT_FOUND_ERR_CODE,
                );
            }
            $session = FormSession::whereIn("status", [
                StatusConstants::PENDING,
                StatusConstants::PROCESSING,
            ])
                ->where("user_id", $request->user()->id)
                ->latest()
                ->first();

            if (empty($session)) {
                $session = (new StartService)->handle($request->all());
            }

            $meta = $session->metadata ?? [];
            $old_formdata = $form->metadata["raw"];
            // unset($old_formdata["selectedProducts"]);
            $meta["raw"] = $old_formdata;
            $session->update([
                "user_id" => $form->user_id,
                "metadata" => $meta
            ]);


            FormSessionActivity::firstOrCreate([
                "form_session_id" => $session->id,
                "activity" => AppConstants::ACIVITY_RECREATE,
            ], [
                "user_id" => $session->user_id,
                "message" => "Form session created from #" . $form->reference
            ]);

            return ApiHelper::validResponse(
                'Session rereated successfully',
                [
                    "id" => $session->id
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
}
