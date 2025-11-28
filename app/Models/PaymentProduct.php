<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;

class PaymentProduct extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['price' => 'array'];

    function payment()
    {
        return $this->belongsTo(Payment::class, "payment_id");
    }

    function product()
    {
        return $this->belongsTo(Product::class, "product_id");
    }

    function getPrice()
    {
        $info = $this->price;
        if (empty($info)) return "N/A";

        $priceString = strtoupper($info["currency"] . "" . number_format($info["value"], 2));

        // Handle both subscription products (with frequency/unit) and one-time products (without)
        if (isset($info['frequency']) && isset($info['unit'])) {
            return $priceString . " / {$info['frequency']} {$info['unit']}";
        }

        // For one-time purchases (like peptides), just return the price
        return $priceString;
    }
}
