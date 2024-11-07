<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array'
    ];

    function formSession()  {
        return $this->belongsTo(FormSession::class , "form_session_id");
    }

}
