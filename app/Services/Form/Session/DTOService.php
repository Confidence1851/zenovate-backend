<?php

namespace App\Services\Form\Session;

use App\Exceptions\GeneralException;
use App\Helpers\Helper;
use App\Helpers\StatusConstants;
use App\Models\FormSession;
use App\Models\Payment;
use App\Models\PaymentProduct;
use Carbon\Carbon;
use Exception;

class DTOService
{
    public ?Payment $payment;
    function __construct(public FormSession $session)
    {
        $this->payment = $session->completedPayment;
    }

    function id()
    {
        return $this->session->id;
    }

    function reference()
    {
        return $this->session->reference;
    }

    function status()
    {
        return $this->session->getStatus();
    }

    // Personal Information
    function firstName()
    {
        return $this->session->metadata["raw"]["firstName"] ?? null;
    }

    function lastName()
    {
        return $this->session->metadata["raw"]["lastName"] ?? null;
    }

    function fullName()
    {
        return $this->firstName() . " " . $this->lastName();
    }

    function dob()
    {
        $v = $this->session->metadata["raw"]["dateOfBirth"] ?? null;
        if (!empty($v)) {
            $v = Carbon::parse($v)->format("Y-m-d");
        }
        return $v;
    }

    function dob2()
    {
        $v = $this->dob();
        if (empty($v)) {
            return;
        }
        return Carbon::parse($v)->format("m/d/Y");
    }


    // Contact Information
    function email()
    {
        return $this->session->metadata["raw"]["email"] ?? null;
    }

    function phone()
    {
        return $this->session->metadata["raw"]["phoneNumber"] ?? null;
    }

    function preferredContact()
    {
        return ucfirst($this->session->metadata["raw"]["preferredContact"] ?? null);
    }

    // Address Information
    function streetAddress()
    {
        return $this->session->metadata["raw"]["streetAddress"] ?? null;
    }

    function city()
    {
        return $this->session->metadata["raw"]["city"] ?? null;
    }

    function stateProvince()
    {
        return $this->session->metadata["raw"]["stateProvince"] ?? null;
    }

    function postalZipCode()
    {
        return $this->session->metadata["raw"]["postalZipCode"] ?? null;
    }

    function country()
    {
        return $this->session->metadata["raw"]["country"] ?? null;
    }


    function payment()
    {
        return $this->payment;
    }

    function signatureDate()
    {
        return now()->format("m/d/Y");
    }

    function selectedProducts()
    {
        return $this->payment?->products ?? [];
    }

    function paymentProducts()
    {
        $selected_products = collect($this->session->metadata['raw']['selectedProducts'] ?? [])
            ->whereNotNull("price_id");


        $payment = $this->payment;
        if(!empty($payment)){
            return $payment->paymentProducts;
        }

        $currency = null;
        $list = [];
        
        // Load all products at once to avoid N+1 queries
        $productIds = $selected_products->pluck('product_id')->unique()->filter()->toArray();
        $products = \App\Models\Product::whereIn('id', $productIds)->get()->keyBy('id');
        
        $selected_products->map(
            function ($product_price) use (&$currency , &$list, $products) {
                $info = null;
                if (!empty($product_price["price_id"] ?? null)) {
                    try {
                        $info = json_decode(
                            Helper::decrypt($product_price["price_id"]),
                            true
                        )["value"] ?? null;

                    } catch (\Throwable $th) {
                        throw $th;
                    }
                }

                $item = new PaymentProduct([
                    "payment_id" => null,
                    "product_id" =>  $product_price["product_id"],
                    "price" => $info,
                    "quantity" => $product_price["quantity"] ?? 1,
                ]);

                // Load product relationship so it's available in the view
                $product = $products->get($product_price["product_id"]);
                if ($product) {
                    $item->setRelation('product', $product);
                }

                $list[] = $item;
                return $item;
            }
        );

        return collect($list);
    }

    function validate()
    {
        $required_fields = [
            "first_name" => $this->firstName(),
            "last_name" => $this->lastName(),
            "email" => $this->email(),
            "dob" => $this->dob(),
            "selected_products" => $this->selectedProducts(),
            "payment" => $this->payment(),
            "street_address" => $this->streetAddress(),
            "country" => $this->country(),
            "current_medications" => $this->session->metadata["raw"]["currentMedications"] ?? null,
            "previous_surgeries" => $this->session->metadata["raw"]["previousSurgeries"] ?? null,
            "other_conditions" => $this->session->metadata["raw"]["otherConditions"] ?? null,
            "injectable_concerns" => $this->session->metadata["raw"]["injectablesConcerns"] ?? null,
        ];

        foreach ($required_fields as $key => $value) {
            if (empty($value)) {
                logger("Empty value", [$key, $value]);
                throw new GeneralException("Fields are yet to be complete.");
            }
        }
    }

    function questions()
    {
        $list = array_merge(
            $this->questionGroupBuilder(
                "Allergies and Medications",
                "Please provide information about your allergies and current medications",
                "allergies_medications"
            ),
            $this->questionBuilder(
                "Allergies?",
                "allergies",
                "select",
                [
                    "options" => StatusConstants::BOOL_OPTIONS,
                    "placeholder" => "Please specify your allergies",
                    "key" => "allergiesDetails",
                ]
            ),

            $this->questionBuilder(
                "Current Medications?",
                "currentMedications",
                "select",
                [
                    "options" => StatusConstants::BOOL_OPTIONS,
                    "placeholder" => "Please list your current medications",
                    "key" => "medicationsDetails",
                ]
            ),


            $this->questionGroupBuilder(
                "Medical History",
                "Please provide details about your medical history",
                "medical_history"
            ),
            $this->questionBuilder(
                "Do you have existing medical conditions?",
                "existingConditions",
                "select",
                [
                    "options" => StatusConstants::BOOL_OPTIONS,
                    "placeholder" => "Please provide more details on the condition",
                    "key" => "conditionsDetails",
                ]
            ),

            $this->questionBuilder(
                "Have you had previous surgeries?",
                "previousSurgeries",
                "select",
                [
                    "options" => StatusConstants::BOOL_OPTIONS,
                    "placeholder" => "Please provide more details on the surgeries",
                    "key" => "surgeriesDetails",
                ]
            ),


            // Page 2
            $this->questionBuilder(
                "Have you previously had heart disease?",
                "heartDisease",
                "select",
                [
                    "options" => StatusConstants::BOOL_OPTIONS,
                    "placeholder" => "Please provide more details on the condition",
                    "key" => "heartDiseaseDetails",
                ]
            ),

            $this->questionBuilder(
                "Have you previously had kidney disease?",
                "kidneyDisease",
                "select",
                [
                    "options" => StatusConstants::BOOL_OPTIONS,
                    "placeholder" => "Please provide more details on the condition",
                    "key" => "kidneyDiseaseDetails",
                ]
            ),

            $this->questionBuilder(
                "Have you previously had liver disease?",
                "liverDisease",
                "select",
                [
                    "options" => StatusConstants::BOOL_OPTIONS,
                    "placeholder" => "Please provide more details on the condition",
                    "key" => "liverDiseaseDetails",
                ]
            ),


            // Page 3
            $this->questionBuilder(
                "Have you ever had any autoimmune disorders?",
                "autoimmuneDisorders",
                "select",
                [
                    "options" => StatusConstants::BOOL_OPTIONS,
                    "placeholder" => "Specify which and please provide more details on the conditions?",
                    "key" => "autoimmuneDisordersDetails",
                ]
            ),
            $this->questionBuilder(
                "Do you have any other conditions?",
                "otherConditions",
                "select",
                [
                    "options" => StatusConstants::BOOL_OPTIONS,
                    "placeholder" => "Please specify what are they and details on their condition",
                    "key" => "otherConditionsDetails",
                ]
            ),



            $this->questionGroupBuilder(
                "Health Update",
                "Update us on your recent health check-up and any changes",
                "health_update"
            ),
            $this->questionBuilder(
                "When was the last date of your medical check-up?",
                "lastCheckupDate",
                "input",
            ),
            $this->questionBuilder(
                "Have you had any recent health changes?",
                "recentHealthChanges",
                "select",
                [
                    "options" => StatusConstants::BOOL_OPTIONS,
                    "placeholder" => "Tell us more",
                    "key" => "healthChangesDetails",
                ]
            ),


            $this->questionGroupBuilder(
                "Additional Information",
                "Please provide information about your concerns with injectables and any additional details",
                "additional_information"
            ),
            $this->questionBuilder(
                "Are you afraid or concerned with injectables?",
                "injectablesConcerns",
                "select",
                [
                    "options" => StatusConstants::BOOL_OPTIONS,
                    "placeholder" => "Tell us about your concerns with injectables",
                    "key" => "injectablesConcernsDetails",
                ]
            ),
            $this->questionBuilder(
                "Are you afraid or concerned with needles?",
                "needleFear",
                "select",
                [
                    "options" => StatusConstants::BOOL_OPTIONS,
                    "placeholder" => "Tell us about your concerns with needles",
                    "key" => "needleConcernsDetails",
                ]
            ),
            $this->questionBuilder(
                "Please tell us about your family medical history",
                "familyMedicalHistory",
                "textarea",
            ),
            $this->questionBuilder(
                "Additional Information",
                "additionalInfo",
                "textarea",
            ),
        );

        return $list;
    }
    function questionGroupBuilder($title, $subtitle, $key)
    {
        return [
            $key => [
                "type" => "group",
                "title" => $title,
                "subtitle" => $subtitle,
            ]
        ];
    }
    function questionBuilder($question, $key, $type, $more = [])
    {
        $allowed_types = ["select", "textarea", "input"];
        if (!in_array($type, $allowed_types)) {
            throw new Exception("Invalid question type provided");
        }

        $value = ucfirst($this->session->metadata["raw"][$key] ?? null);

        if ($key == "lastCheckupDate" && !empty($value)) {
            $value = Carbon::parse($value)->format("Y-m-d");
        }
        $data = [
            "question" => $question,
            "type" => $type,
            "options" => $more["options"] ?? null,
            "value" => $value,

        ];

        if ($type == "select") {
            $data["sub"] = [
                "placeholder" => $more["placeholder"] ?? null,
                "value" => $value == "Yes" ? $this->session->metadata["raw"][$more["key"]] ?? null : null
            ];
        }
        return [
            $key => $data
        ];
    }
}
