<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCategoryResource extends JsonResource
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
            "category_name" => $this->category_name,
            "category_slug" => $this->category_slug,
            "category_description" => $this->category_description,
            "category_image_url" => $this->category_image_url,
            "order" => $this->order,
        ];
    }
}
