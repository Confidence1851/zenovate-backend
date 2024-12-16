<?php

namespace App\Http\Resources;

use App\Helpers\Helper;
use App\Models\Product;
use App\Services\Form\Payment\ProcessorService;
use App\Services\Form\Session\UpdateService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

class FormSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $selected_products = collect($this->metadata['raw']['selectedProducts'] ?? [])
            ->whereNotNull("product_id");


        $payment = $this->whenLoaded("completedPayment");
        $products_count = $selected_products->count();

        if (!empty($payment)) {
            $total_cost = $payment->getAmount("total");
        } else {
            $currency = null;
            $prices = collect($selected_products->map(
                function ($product_price) use (&$currency) {
                    $price = 0;

                    if (!empty($product_price["price_id"] ?? null)) {
                        try {
                            $info = json_decode(
                                Helper::decrypt($product_price["price_id"]),
                                true
                            )["value"] ?? null;

                            $currency = $info["currency"] ?? null;
                            $price = $info["value"] ?? 0;
                        } catch (\Throwable $th) {
                            throw $th;
                        }
                    }


                    return $price;
                }
            ));

            if($prices->isEmpty()){
                $total_cost = "N/A";
            } else {
                $shipping_fee = ProcessorService::getShippingFee($this->resource);
                $product_cost = $prices->sum();
                $total_cost = $currency . "" . number_format($product_cost + $shipping_fee, 2);
            }
        }


        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'total_products' => $products_count,
            'total_cost' => $total_cost ?? 0,
            // 'metadata' => [
            //     'user_agent' => $this->metadata['user_agent'] ?? null,
            //     'location' => $this->metadata['location'] ?? null,
            //     'raw' => [
            //         'first_name' => $this->metadata['raw']['firstName'] ?? null,
            //         'last_name' => $this->metadata['raw']['lastName'] ?? null,
            //         'email' => $this->metadata['raw']['email'] ?? null,
            //         'phone_number' => $this->metadata['raw']['phoneNumber'] ?? null,
            //         'date_of_birth' => $this->metadata['raw']['dateOfBirth'] ?? null,
            //         'preferred_contact' => $this->metadata['raw']['preferredContact'] ?? null,
            //         'street_address' => $this->metadata['raw']['streetAddress'] ?? null,
            //         'city' => $this->metadata['raw']['city'] ?? null,
            //         'state_province' => $this->metadata['raw']['stateProvince'] ?? null,
            //         'postal_zip_code' => $this->metadata['raw']['postalZipCode'] ?? null,
            //         'country' => $this->metadata['raw']['country'] ?? null,
            //         'selected_products' => $products,
            //         'allergies' => $this->metadata['raw']['allergies'] ?? null,
            //         'current_medications' => $this->metadata['raw']['currentMedications'] ?? null,
            //         'allergies_details' => $this->metadata['raw']['allergiesDetails'] ?? null,
            //         'existing_conditions' => $this->metadata['raw']['existingConditions'] ?? null,
            //         'previous_surgeries' => $this->metadata['raw']['previousSurgeries'] ?? null,
            //         'heart_disease' => $this->metadata['raw']['heartDisease'] ?? null,
            //         'kidney_disease' => $this->metadata['raw']['kidneyDisease'] ?? null,
            //         'liver_disease' => $this->metadata['raw']['liverDisease'] ?? null,
            //         'autoimmune_disorders' => $this->metadata['raw']['autoimmuneDisorders'] ?? null,
            //         'other_conditions' => $this->metadata['raw']['otherConditions'] ?? null,
            //         'recent_health_changes' => $this->metadata['raw']['recentHealthChanges'] ?? null,
            //         'injectables_concerns' => $this->metadata['raw']['injectablesConcerns'] ?? null,
            //         'needle_fear' => $this->metadata['raw']['needleFear'] ?? null,
            //         'family_medical_history' => $this->metadata['raw']['familyMedicalHistory'] ?? null,
            //         'additional_info' => $this->metadata['raw']['additionalInfo'] ?? null,
            //     ],
            // ],
            'status' => $this->status,
            // 'pdf_path' => $this->pdf_path,
            // 'docuseal_id' => $this->docuseal_id,
            // 'docuseal_url' => $this->docuseal_url,
            'comment' => $this->comment,
            'created_at' => $this->created_at->format("Y-m-d h:i A"),
            'completedPayment' => PaymentResource::make($payment),
            // 'updated_at' => $this->updated_at,
            // 'deleted_at' => $this->deleted_at,
            // 'airtable_order_id' => $this->airtable_order_id,
            // 'airtable_status' => $this->airtable_status,
            // 'consent_pdf_path' => $this->consent_pdf_path,
        ];
    }
}
