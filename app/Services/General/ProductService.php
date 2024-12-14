<?php

namespace App\Services\General;

use App\Models\Product;
use Str;

class ProductService
{
    public static function generateSlug($product = null)
    {
        // Check if a product instance is passed
        if ($product && isset($product->name)) {
            // Generate the base slug from the product name
            $baseSlug = Str::slug(substr($product->name, 0, 20), '-');
        } else {
            throw new \InvalidArgumentException("Product instance with a valid name is required.");
        }

        // Initialize the slug and counter
        $slug = $baseSlug;
        $counter = 1;

        // Ensure slug uniqueness
        while (Product::where('slug', $slug)->withTrashed()->exists()) {
            $slug = substr($baseSlug, 0, 20 - strlen("-$counter")) . "-$counter";
            $counter++;
        }

        // Update the product's slug if a product instance is provided
        if ($product?->id) {
            $product->slug = $slug;
            $product->save();
        }

        return $slug;
    }
}
