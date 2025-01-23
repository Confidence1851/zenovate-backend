<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = $this->product;
        return [
            "id" => $product->id,
            "name" => $product->name,
            "slug" => $product->slug,
            "subtitle" => $product->subtitle,
            "description" => $product->description,
            "nav_description" => $product->nav_description,
            "key_ingredients" => $product->key_ingredients,
            "benefits" => $product->benefits,
            "quantity" => 1,
            "selected_price" => $this->getPrice(),
        ];
    }
}
