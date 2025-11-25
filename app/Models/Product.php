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
     * Returns placeholder image if no image_path is set
     */
    function getImageUrls()
    {
        // Handle multiple images (comma-separated)
        $imagePaths = [];
        if (!empty($this->image_path)) {
            $imagePaths = explode(',', $this->image_path);
        }
        
        // If no images, use placeholder
        if (empty($imagePaths) || (count($imagePaths) === 1 && empty(trim($imagePaths[0])))) {
            $placeholderPath = 'products/placeholder.png';
            $encrypted = Helper::encrypt_decrypt("encrypt", $placeholderPath);
            if ($encrypted) {
                $baseUrl = env('APP_URL', 'http://localhost');
                return rtrim($baseUrl, '/') . '/api/get-file/' . $encrypted;
            }
            return null;
        }

        $urls = [];
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

        // Return single URL if only one image, otherwise return array
        return count($urls) === 1 ? $urls[0] : (count($urls) > 0 ? $urls : null);
    }

}
