<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "slug" => $this->slug,
            "subtitle" => $this->subtitle,
            "description" => $this->description,
            "nav_description" => $this->nav_description,
            "key_ingredients" => $this->key_ingredients,
            "benefits" => $this->benefits,
            "potency" => $this->potency,
            "price" => $this->getLocationPrice(),
            "quantity" => 1,
            "selected_price" => $this->selected_price,
            "image_path" => $this->image_path,
            "image_url" => $this->getImageUrls(),
            "checkout_type" => $this->checkout_type ?? 'form',
            "requires_patient_clinic_selection" => (bool) ($this->requires_patient_clinic_selection ?? false),
            "shipping_fee" => $this->shipping_fee,
            "tax_rate" => $this->tax_rate,
            "category" => new ProductCategoryResource($this->whenLoaded('category')),
        ];
    }
}
