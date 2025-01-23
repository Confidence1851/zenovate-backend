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

}
