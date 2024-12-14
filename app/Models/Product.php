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
            $value["id"] = Helper::encrypt(json_encode($value));
            $value["value"] = $value["values"][strtolower($currency)];
            $value["currency"] = $currency;
            unset($value["values"]);
            $list[] = $value;
        }

        return collect($list)->sort("value")->toArray();
    }
}
