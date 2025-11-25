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

    function getLocationPrice()
    {
        $info = IpAddressService::info();
        $currency = $info["currency"] ?? "USD";

        $list = [];
        foreach ($this->price as $value) {
            $value["value"] = $value["values"][strtolower($currency)];
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

}
