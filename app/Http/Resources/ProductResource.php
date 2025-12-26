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
        // Determine context based on route name or path
        $routeName = $request->route()?->getName();
        $path = $request->path();

        $isIndexPage = $routeName === 'form.products.index'
            || $routeName === 'form.products.by-categories'
            || $routeName === 'products.order-sheet'
            || ($path === 'form/products' || str_contains($path, 'form/products/by-categories') || str_contains($path, 'form/products/order-sheet') || str_contains($path, 'api/form/products/order-sheet'));

        $isDetailPage = $routeName === 'form.products.info'
            || $routeName === 'products.info'
            || (preg_match('/^form\/products\/[^\/]+$/', $path) && ! str_contains($path, 'by-categories') && ! str_contains($path, 'order-sheet'));

        // Filter prices based on context
        $prices = [];

        // For order sheet, use currency from request if provided
        $currency = null;
        $useOrderSheetPrice = false;
        if ($isIndexPage && ($routeName === 'products.order-sheet' || str_contains($path, 'order-sheet'))) {
            $currency = $request->input('order_sheet_currency');
            $useOrderSheetPrice = true;
        }

        if ($isIndexPage) {
            // Index pages: Show ONLY 1-month price (frequency = 1) or flat pricing (no frequency)
            // For order sheet, use location-based currency (CAD for Canada, USD for others)
            $prices = $this->getLocationPrice(null, $currency, $useOrderSheetPrice);
            if (! $useOrderSheetPrice) {
                // Regular pricing: filter by frequency = 1
                $prices = array_filter($prices, function ($price) {
                    return isset($price['frequency']) && $price['frequency'] == 1;
                });
                $prices = array_values($prices);
            }
        } elseif ($isDetailPage) {
            // Detail pages: EXCLUDE 1-month price (show 3, 6, 9, 12 months only)
            // Use getLocationPrice(1) to exclude frequency 1 directly
            $prices = $this->getLocationPrice(1);
        } else {
            // Default: return all prices
            $prices = $this->getLocationPrice();
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'slug' => $this->slug,
            'subtitle' => $this->subtitle,
            'description' => $this->description,
            'nav_description' => $this->nav_description,
            'key_ingredients' => $this->key_ingredients,
            'benefits' => $this->benefits,
            'potency' => $this->potency,
            'price' => $prices,
            'quantity' => 1,
            'selected_price' => $this->selected_price,
            'image_path' => $this->image_path,
            'image_url' => $this->getImageUrls(),
            'checkout_type' => $this->checkout_type ?? 'form',
            'requires_patient_clinic_selection' => (bool) ($this->requires_patient_clinic_selection ?? false),
            'shipping_fee' => $this->shipping_fee,
            'tax_rate' => $this->tax_rate,
            'enabled_for_order_sheet' => (bool) ($this->enabled_for_order_sheet ?? false),
            'category' => new ProductCategoryResource($this->whenLoaded('category')),
        ];
    }
}
