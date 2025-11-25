<?php

namespace App\Models;

use App\Helpers\Helper;
use App\Services\General\IpAddressService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    protected $guarded = ['id'];
    protected $casts = ['price' => 'array'];

    /**
     * Get all images for this product
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class)->ordered();
    }

    /**
     * Get primary image
     */
    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    /**
     * Get product categories
     */
    public function productCategories()
    {
        return $this->hasMany(ProductCategory::class)->ordered();
    }

    function getLocationPrice()
    {
        $info = IpAddressService::info();
        $currency = $info["currency"] ?? "CAD";

        $list = [];
        foreach ($this->price as $value) {
            $currencyKey = strtolower($currency);
            
            // Check if the requested currency exists, otherwise fall back to CAD or first available
            if (!isset($value["values"][$currencyKey])) {
                // Try CAD first (default)
                if (isset($value["values"]["cad"])) {
                    $currencyKey = "cad";
                    $currency = "CAD";
                } elseif (isset($value["values"]["usd"])) {
                    // Fall back to USD if CAD not available
                    $currencyKey = "usd";
                    $currency = "USD";
                } else {
                    // Use first available currency
                    $currencyKey = array_key_first($value["values"]);
                    $currency = strtoupper($currencyKey);
                }
            }
            
            $value["value"] = $value["values"][$currencyKey];
            $value["currency"] = $currency;
            unset($value["values"]);
            
            $price_id = ["product_id" => $this->id, "value" => $value];
            $value["id"] = Helper::encrypt(json_encode($price_id));
            $list[] = $value;
        }

        // Sort by 'value' in ascending order
        return collect($list)->sortBy("value")->values()->toArray();
    }

    /**
     * Get image URL(s) for the product
     * Returns array of URLs if multiple images, or single URL string
     * Returns placeholder image if no images found
     * Backward compatible: checks product_images table first, then falls back to image_path column
     */
    function getImageUrls()
    {
        $urls = [];

        // First, try to get images from product_images table (new way)
        $images = $this->images;
        if ($images->isNotEmpty()) {
            foreach ($images as $image) {
                $imageUrl = $image->getImageUrl();
                if ($imageUrl) {
                    $urls[] = $imageUrl;
                }
            }
        }

        // Fallback to image_path column if no images in table (backward compatibility)
        if (empty($urls) && !empty($this->image_path)) {
            $imagePaths = explode(',', $this->image_path);
            foreach ($imagePaths as $path) {
                $path = trim($path);
                if (!empty($path)) {
                    $encrypted = Helper::encrypt_decrypt("encrypt", $path);
                    if ($encrypted) {
                        $baseUrl = env('APP_URL', 'http://localhost');
                        $urls[] = rtrim($baseUrl, '/') . '/api/get-file/' . $encrypted;
                    }
                }
            }
        }

        // If still no images, use placeholder
        if (empty($urls)) {
            $placeholderPath = 'products/placeholder.png';
            $encrypted = Helper::encrypt_decrypt("encrypt", $placeholderPath);
            if ($encrypted) {
                $baseUrl = env('APP_URL', 'http://localhost');
                return rtrim($baseUrl, '/') . '/api/get-file/' . $encrypted;
            }
            return null;
        }

        // Return single URL if only one image, otherwise return array
        return count($urls) === 1 ? $urls[0] : $urls;
    }

    /**
     * Check if product uses direct checkout
     */
    public function isDirectCheckout(): bool
    {
        return $this->checkout_type === 'direct';
    }

    /**
     * Check if product requires patient/clinic selection
     */
    public function requiresPatientClinicSelection(): bool
    {
        return (bool) $this->requires_patient_clinic_selection;
    }

    /**
     * Get shipping fee (product-specific or global)
     */
    public function getShippingFee(): float
    {
        if ($this->shipping_fee !== null) {
            return (float) $this->shipping_fee;
        }
        
        return (float) config('checkout.shipping_fee', env('CHECKOUT_SHIPPING_FEE', 60));
    }

    /**
     * Get tax rate (product-specific or global)
     */
    public function getTaxRate(): float
    {
        if ($this->tax_rate !== null) {
            return (float) $this->tax_rate;
        }
        
        return (float) config('checkout.tax_rate', env('CHECKOUT_TAX_RATE', 0));
    }

}
