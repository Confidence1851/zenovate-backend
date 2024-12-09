<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // 'user_id' => $this->user_id,
            'reference' => $this->reference,
            'metadata' => [
                'user_agent' => $this->metadata['user_agent'] ?? null,
                'location' => $this->metadata['location'] ?? null,
                'raw' => [
                    'first_name' => $this->metadata['raw']['firstName'] ?? null,
                    'last_name' => $this->metadata['raw']['lastName'] ?? null,
                    'email' => $this->metadata['raw']['email'] ?? null,
                    'phone_number' => $this->metadata['raw']['phoneNumber'] ?? null,
                    'date_of_birth' => $this->metadata['raw']['dateOfBirth'] ?? null,
                    'preferred_contact' => $this->metadata['raw']['preferredContact'] ?? null,
                    'street_address' => $this->metadata['raw']['streetAddress'] ?? null,
                    'city' => $this->metadata['raw']['city'] ?? null,
                    'state_province' => $this->metadata['raw']['stateProvince'] ?? null,
                    'postal_zip_code' => $this->metadata['raw']['postalZipCode'] ?? null,
                    'country' => $this->metadata['raw']['country'] ?? null,
                    'selected_products' => collect($this->metadata['raw']['selectedProducts'] ?? [])->map(function ($product) {
                        return [
                            'id' => $product['id'],
                            'name' => $product['name'],
                            'subtitle' => $product['subtitle'],
                            'description' => $product['description'],
                            'price' => $product['price'],
                        ];
                    }),
                    'allergies' => $this->metadata['raw']['allergies'] ?? null,
                    'current_medications' => $this->metadata['raw']['currentMedications'] ?? null,
                    'allergies_details' => $this->metadata['raw']['allergiesDetails'] ?? null,
                    'existing_conditions' => $this->metadata['raw']['existingConditions'] ?? null,
                    'previous_surgeries' => $this->metadata['raw']['previousSurgeries'] ?? null,
                    'heart_disease' => $this->metadata['raw']['heartDisease'] ?? null,
                    'kidney_disease' => $this->metadata['raw']['kidneyDisease'] ?? null,
                    'liver_disease' => $this->metadata['raw']['liverDisease'] ?? null,
                    'autoimmune_disorders' => $this->metadata['raw']['autoimmuneDisorders'] ?? null,
                    'other_conditions' => $this->metadata['raw']['otherConditions'] ?? null,
                    'recent_health_changes' => $this->metadata['raw']['recentHealthChanges'] ?? null,
                    'injectables_concerns' => $this->metadata['raw']['injectablesConcerns'] ?? null,
                    'needle_fear' => $this->metadata['raw']['needleFear'] ?? null,
                    'family_medical_history' => $this->metadata['raw']['familyMedicalHistory'] ?? null,
                    'additional_info' => $this->metadata['raw']['additionalInfo'] ?? null,
                ],
            ],
            'status' => $this->status,
            // 'pdf_path' => $this->pdf_path,
            // 'docuseal_id' => $this->docuseal_id,
            // 'docuseal_url' => $this->docuseal_url,
            'comment' => $this->comment,
            'created_at' => $this->created_at,
            // 'updated_at' => $this->updated_at,
            // 'deleted_at' => $this->deleted_at,
            // 'airtable_order_id' => $this->airtable_order_id,
            // 'airtable_status' => $this->airtable_status,
            // 'consent_pdf_path' => $this->consent_pdf_path,
        ];
    }
}
