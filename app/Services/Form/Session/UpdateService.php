<?php

namespace App\Services\Form\Session;

use App\Helpers\StatusConstants;
use App\Models\Customer;
use App\Models\FormSession;
use App\Models\Product;
use App\Models\User;
use App\Services\Auth\CustomerService;
use App\Services\Auth\UserService;
use App\Services\Form\Payment\ProcessorService;
use Illuminate\Support\Facades\DB;
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
        $this->validate($data);
        logger("Session data", $data);
        $this->formSession = FormSession::find($data["sessionId"]);

        if ($data["step"] != self::STEP_PRODUCT) {
            unset($data["formData"]);
        }
        if ($data["step"] != self::STEP_SIGN) {
            if (!empty($raw = $data["formData"] ?? null)) {
                $this->formSession->update([
                    "metadata" => [
                        "raw" => array_merge(
                            $this->formSession->metadata["raw"] ?? [],
                            $raw
                        )
                    ]
                ]);
                $this->formSession->refresh();
            }


            $formatted_data = $this->mapFields($data);

            foreach (self::STEPS as $step) {
                if ($data["step"] == $step) {
                    $method_name = "handle" . ucfirst($step);
                    if (method_exists($this, $method_name)) {
                        return call_user_func([$this, $method_name], $formatted_data);
                    }
                }
            }
            return [];
        }
        
        $this->formSession->update([
            "status" => StatusConstants::COMPLETED
        ]);
    }

    public function mapFields(array $data)
    {
        return [
            "session_id" => $data["sessionId"] ?? null,
            "first_name" => $data["firstName"] ?? null,
            "last_name" => $data["lastName"] ?? null,
            "email" => $data["email"] ?? null,
            "phone" => $data["phoneNumber"] ?? null,
            "dob" => $data["dateOfBirth"] ?? null,
            "preferred_contact_method" => $data["preferredContact"] ?? null,
            "selected_products" => $data["selectedProducts"] ?? null,
        ];
    }

    public function handleComplete(array $data)
    {
        // Send Notification to users and generate pdf
    }

    public function parseGeoData()
    {
        $country = trim($this->formSession->metadata["raw"]["country"]);
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

    // public function handleInfo(array $data)
    // {
    //     $validator = Validator::make($data, [
    //         "session_id" => "required|string",
    //         "email" => "required|email",
    //     ]);

    //     if ($validator->fails()) {
    //         throw new ValidationException($validator);
    //     }

    //     $user = User::where("email", $data["email"])->first();

    //     if(empty($user)){
    //         $user = (new UserService)->save($data);
    //     }

    //     (new CustomerService)->save($user, $data);
    // }

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
