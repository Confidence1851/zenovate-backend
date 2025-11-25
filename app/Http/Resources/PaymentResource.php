<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            "shipping_fee" => $this->getAmount("shipping_fee"),
            "sub_total" => $this->getAmount("sub_total"),
            "total" => $this->getAmount("total"),
            "status" => $this->status,
            "paid_at" => $this->paid_at?->format("Y-m-d h:i A"),
            'products' => PaymentProductResource::collection($this->paymentProducts),
            "metadata" => $this->metadata,
            "method" => $this->method,
            "method_info" => $this->method_info,
            "discount_code" => $this->discount_code,
            "discount_amount" => $this->discount_amount ? $this->currency . "" . number_format($this->discount_amount, 2) : null,
        ];
    }
}
