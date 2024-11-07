<?php

namespace App\Models;

use App\Helpers\StatusConstants;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FormSession extends Model
{
    use HasUuids;

    protected $guarded = ['id'];
    protected $casts = [
        'data' => 'array',
        'metadata' => 'array'
    ];

    function completedPayment()
    {
        return $this->hasOne(Payment::class, "form_session_id")->where("status", StatusConstants::SUCCESSFUL);
    }

    function payments()
    {
        return $this->hasMany(Payment::class, "form_session_id")->latest();
    }

}
