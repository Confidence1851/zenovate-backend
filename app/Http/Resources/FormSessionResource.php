<?php

namespace App\Http\Resources;

use App\Helpers\Helper;
use App\Services\Form\Payment\ProcessorService;
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
            'status' => $this->status,
            'comment' => $this->comment,
            'created_at' => $this->created_at->format("Y-m-d h:i A"),
        ];
    }
}
