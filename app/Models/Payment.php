<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
        'method_info' => 'array',
        'paid_at' => 'datetime',
    ];

    function formSession()
    {
        return $this->belongsTo(FormSession::class, "form_session_id");
    }

    function products()
    {
        return $this->hasManyThrough(
            Product::class,
            PaymentProduct::class,
            "payment_id",
            "id",
            "id",
            "product_id"
        );
    }

    function paymentProducts()
    {
        return $this->hasMany(PaymentProduct::class, "payment_id" , "id");
    }

    public function scopeSearch($query, $key)
    {
        $query->whereHas("formSession", function ($sub) use ($key) {
            $sub->search($key);
        })
            ->orWhere("reference", "like", "%$key%");
    }

    function getAmount($key) {
        return $this->currency ."".number_format($this->$key,2);
    }

    /**
     * Scope a query to only include order sheet payments.
     */
    public function scopeOrderSheet($query)
    {
        return $query->where('order_type', 'order_sheet');
    }

    /**
     * Scope a query to only include regular payments.
     */
    public function scopeRegular($query)
    {
        return $query->where('order_type', 'regular');
    }

}

