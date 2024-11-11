<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentProduct extends Model
{
    protected $guarded = ['id'];

    function payment()
    {
        return $this->belongsTo(Payment::class, "payment_id");
    }

    function product()
    {
        return $this->belongsTo(Product::class, "product_id");
    }
}
