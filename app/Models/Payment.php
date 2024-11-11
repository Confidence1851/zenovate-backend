<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array'
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

    public function scopeSearch($query, $key)
    {
        $query->whereHas("formSession", function ($sub) use ($key) {
            $sub->search($key);
        })
            ->orWhere("reference", "like", "%$key%");
    }

    function getAmount($key) {
        return $this->currency ." ".number_format($this->$key,2);
    }

}

