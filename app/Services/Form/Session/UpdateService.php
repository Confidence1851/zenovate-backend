<?php

namespace App\Services\Form\Session;

use App\Exceptions\GeneralException;
use App\Helpers\AppConstants;
use App\Helpers\EncryptionService;
use App\Helpers\Helper;
use App\Helpers\StatusConstants;
use App\Models\FormSession;
use App\Models\FormSessionActivity;
use App\Models\Product;
use App\Models\User;
use App\Notifications\Form\Session\Admin\AwaitingReviewNotification;
use App\Notifications\Form\Session\Admin\NewRequestNotification;
use App\Notifications\Form\Session\Customer\ReceivedNotification;
use App\Services\Auth\CustomerService;
use App\Services\Auth\UserService;
use App\Services\Form\Payment\ProcessorService;
use App\Services\General\DiscountCodeService;
use App\Services\General\IpAddressService;
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
            "formData.selectedProducts.*.product_id" => "required|exists:products,id",
            "formData.selectedProducts.*.price_id" => "nullable",
            "discount_code" => "nullable|string",
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
            // logger("Session data", $data);
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
            "discount_code" => $data["discount_code"] ?? $data["formData"]["discount_code"] ?? null,
        ];
    }


    public function parseGeoData()
    {
        $info = IpAddressService::info();

        $country = trim($this->formSession->metadata["raw"]["country"] ?? '');
        if (!empty($info)) {
            $country = $info["country"];
        }
        if (strtolower($country) == "canada") {
            return [
                "currency" => "CAD",
                "country_code" => "CA",
                "country" => "Canada"
            ];
        }
        if (strtolower($country) == "united states") {
            return [
                "currency" => "USD",
                "country_code" => "US",
                "country" => "United States"
            ];
        }
        
        // Default to USD if country is not recognized or info is empty (unless Canada)
        if (!empty($info) && isset($info["currency"]) && isset($info["countryCode"])) {
            return [
                "currency" => $info["currency"],
                "country_code" => $info["countryCode"],
                "country" => $info["country"] ?? "United States"
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
        $products = Product::whereIn("id", $selected_products->pluck("product_id"))->get([
            "id",
            "name",
            "subtitle",
            "description",
            "price"
        ]);

        foreach ($products as $key => $product) {
            $price_id = $selected_products->where(
                "product_id",
                $product->id
            )->first()["price_id"];
            $product->selected_price = json_decode(
                Helper::decrypt($price_id)
            ,
                true
            )["value"];
        }

        $sub_total = $products->sum("selected_price.value");
        $shipping_fee = ProcessorService::getShippingFee($this->formSession);

        // Get geo data for currency
        $geoData = $this->parseGeoData();

        // Calculate tax rate (use first product's tax rate, brand-specific, or global config)
        $taxRate = 0;
        $firstProduct = $products->first();
        if ($firstProduct) {
            $taxRate = $firstProduct->getTaxRateByBrand($geoData['currency'] === 'CAD' ? 'cccportal' : 'pinksky');
        }
        
        // Calculate tax amount
        $taxAmount = $sub_total * ($taxRate / 100);

        $checkoutData = array_merge($geoData, [
            "products" => $products,
            "shipping_fee" => floatval(number_format($shipping_fee, 2)),
            "sub_total" => floatval(number_format($sub_total, 2)),
            "tax_rate" => floatval($taxRate),
            "tax_amount" => floatval(number_format($taxAmount, 2)),
            "total" => floatval(number_format($sub_total + $shipping_fee + $taxAmount, 2))
        ]);

        // Apply discount code if provided
        if (!empty($data['discount_code'] ?? null)) {
            try {
                $discountService = new DiscountCodeService();
                $checkoutData = $discountService->applyDiscount($checkoutData, $data['discount_code']);
                
                // Recalculate tax on discounted subtotal
                $discountedSubtotal = $checkoutData['sub_total'] - ($checkoutData['discount_amount'] ?? 0);
                $taxAmount = $discountedSubtotal * ($checkoutData['tax_rate'] / 100);
                $checkoutData['tax_amount'] = floatval(number_format($taxAmount, 2));
                $checkoutData['total'] = floatval(number_format($discountedSubtotal + $checkoutData['shipping_fee'] + $taxAmount, 2));
            } catch (\Exception $e) {
                // Log the error for debugging
                \Log::error('Discount code application failed: ' . $e->getMessage(), [
                    'code' => $data['discount_code'] ?? null,
                    'trace' => $e->getTraceAsString()
                ]);
                // If discount code is invalid, continue without discount
                // Don't throw exception to allow checkout to proceed
            }
        }

        return $checkoutData;
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

            Notification::route('mail', [
                "info@zenovate.health",
            ])->notify(new NewRequestNotification($this->formSession));

            $admins = User::whereIn("role", AppConstants::ADMIN_ROLES)
                ->where("team", AppConstants::TEAM_ZENOVATE)
                ->get();

            Notification::send($admins, new AwaitingReviewNotification($this->formSession));

            $hash = base64_encode((new EncryptionService)->encrypt([
                "key" => "authenticate",
                "value" => $this->formSession->user_id,
                "expires_at" => now()->addMinute()
            ]));
            $redirect_url = rtrim(config('frontend.site_url'), '/') . "/auth/authenticate/$hash";

            return [
                "redirect_url" => $redirect_url
            ];

        });
    }

    public function handleInfo(array $data)
    {
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
