<?php

namespace App\Services\Form\Session;

use App\Exceptions\GeneralException;
use App\Helpers\AppConstants;
use App\Helpers\EncryptionService;
use App\Helpers\StatusConstants;
use App\Models\FormSession;
use App\Models\FormSessionActivity;
use App\Models\Product;
use App\Models\User;
use App\Notifications\Form\Session\Admin\AwaitingReviewNotification;
use App\Notifications\Form\Session\Customer\ReceivedNotification;
use App\Services\Auth\CustomerService;
use App\Services\Auth\UserService;
use App\Services\Form\Payment\ProcessorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateService
{
    const STEP_QUESTIONS = "questions";
    const STEP_INFO = "info";
    const STEP_SIGN = "sign";
    const STEP_PAYMENT = "payment";
    const STEP_PRODUCT = "product";
    const STEP_CHECKOUT = "checkout";
    const STEP_COMPLETE = "complete";
    const STEPS = [
        self::STEP_INFO,
        self::STEP_PRODUCT,
        self::STEP_PAYMENT,
        self::STEP_QUESTIONS,
        self::STEP_SIGN,
        self::STEP_CHECKOUT,
        self::STEP_COMPLETE,
    ];

    public FormSession $formSession;

    function setFormSession(FormSession $formSession)
    {
        $this->formSession = $formSession;
        return $this;
    }

    private function validate(array $data): array
    {
        $validator = Validator::make($data, [
            "sessionId" => "required|exists:form_sessions,id",
            "step" => "required|string|" . Rule::in(self::STEPS),
            "formData" => "nullable|array",
            "formData.selectedProducts" => "nullable|array",
            "formData.selectedProducts.*.id" => "required|exists:products,id"
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public function handle(array $data)
    {
        return DB::transaction(function () use ($data) {
            $this->validate($data);
            logger("Session data", $data);
            $form = FormSession::whereIn("status", [
                StatusConstants::PENDING,
                StatusConstants::PROCESSING,
            ])->find($data["sessionId"]);

            if (empty($form)) {
                throw new GeneralException("Action not permitted");
            }

            $this->formSession = $form;
            if ($data["step"] != self::STEP_PRODUCT) {
                unset($data["formData"]["selectedProducts"]);
            }

            $meta = $this->formSession->metadata ?? [];
            if ($data["step"] != self::STEP_SIGN) {
                if (!empty($raw = $data["formData"] ?? null)) {
                    $meta["raw"] = array_merge($meta["raw"] ?? [], $raw);
                    $this->formSession->update([
                        "metadata" => $meta
                    ]);
                    $this->formSession->refresh();
                }


                $formatted_data = $this->mapFields($data);

                $response = [];
                foreach (self::STEPS as $step) {
                    if ($data["step"] == $step) {
                        $method_name = "handle" . ucfirst($step);
                        if (method_exists($this, $method_name)) {
                            $response = call_user_func([$this, $method_name], $formatted_data);
                        }
                    }
                }
                return array_merge($response, [
                    "paid" => $this->formSession->completedPayment()->exists(),
                ]);
            }

            return $this->handleComplete($data);
        });
    }

    public function mapFields(array $data)
    {
        return [
            "session_id" => $data["sessionId"] ?? null,
            "first_name" => $data["formData"]["firstName"] ?? null,
            "last_name" => $data["formData"]["lastName"] ?? null,
            "email" => $data["formData"]["email"] ?? null,
            "phone" => $data["formData"]["phoneNumber"] ?? null,
            "dob" => $data["formData"]["dateOfBirth"] ?? null,
            "preferred_contact_method" => $data["formData"]["preferredContact"] ?? null,
            "selected_products" => $data["formData"]["selectedProducts"] ?? null,
        ];
    }


    public function parseGeoData()
    {
        $country = trim($this->formSession->metadata["raw"]["country"] ?? '');
        if (strtolower($country) == "Canada") {
            return [
                "currency" => "CAD",
                "country_code" => "CA",
                "country" => "Canada"
            ];
        }
        return [
            "currency" => "USD",
            "country_code" => "US",
            "country" => "United States"
        ];
    }


    public function handleCheckout(array $data)
    {
        $selected_products = collect($this->formSession->metadata["raw"]["selectedProducts"]);
        $products = Product::whereIn("id", $selected_products->pluck("id"))->get([
            "id",
            "name",
            "subtitle",
            "description",
            "price"
        ]);

        $sub_total = $products->sum("price");
        $shipping_fee = ProcessorService::getShippingFee($this->formSession);


        return array_merge($this->parseGeoData(), [
            "products" => $products,
            "shipping_fee" => floatval(number_format($shipping_fee, 2)),
            "sub_total" => floatval(number_format($sub_total, 2)),
            "total" => floatval(number_format($sub_total + $shipping_fee, 2))
        ]);
    }

    public function handleComplete(array $data)
    {
        return DB::transaction(function () use ($data) {
            $dto = new DTOService($this->formSession);
            $dto->validate();

            $this->formSession->update([
                "status" => StatusConstants::AWAITING_REVIEW
            ]);

            FormSessionActivity::firstOrCreate([
                "form_session_id" => $this->formSession->id,
                "activity" => AppConstants::ACIVITY_SUBMITTED,
            ], [
                "message" => "Order was submitted by customer"
            ]);

            Notification::route('mail', [
                $dto->email() => $dto->fullName(),
            ])->notify(new ReceivedNotification($this->formSession));

            $admins = User::whereIn("role", AppConstants::ADMIN_ROLES)
                ->where("team", AppConstants::TEAM_ZENOVATE)
                ->get();

            Notification::send($admins, new AwaitingReviewNotification($this->formSession));

            $hash = base64_encode((new EncryptionService)->encrypt([
                "key" => "authenticate",
                "value" => $this->formSession->user_id,
                "expires_at" => now()->addMinute()
            ]));
            $redirect_url = env("FRONTEND_APP_SITE_URL") . "/auth/authenticate/$hash";

            logger("COmplete", [$redirect_url]);
            return [
                "redirect_url" => $redirect_url
            ];

        });
    }

    public function handleInfo(array $data)
    {
        logger("Handling data", $data);
        $email = $data["email"];
        if (empty($email)) {
            return [];
        }

        $user = User::where("email", $email)->first();

        if (empty($user)) {
            $user = (new UserService)->save(array_merge($data, ["role" => AppConstants::ROLE_CUSTOMER]));
        }
        (new CustomerService)->save($user, $data);

        $this->formSession->update([
            "user_id" => $user->id
        ]);

        return [];

    }

    public function handlePayment(array $data)
    {
        $check = $this->formSession->completedPayment()->exists();
        if ($check) {
            return [
                "paid" => true,
            ];
        }
        return DB::transaction(function () use ($data) {
            $checkout = $this->handleCheckout($data);
            $process = (new ProcessorService)->initiate(
                $this->formSession,
                $checkout
            );
            unset($process["payment"]);
            return $process;
        });
    }
}
