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
        'metadata' => 'array',
    ];

    /**
     * Check if this is a direct checkout booking
     */
    public function isDirectCheckout(): bool
    {
        return $this->booking_type === 'direct';
    }

    /**
     * Check if this is a form-based booking
     */
    public function isFormBooking(): bool
    {
        return $this->booking_type === 'form';
    }

    function completedPayment()
    {
        return $this->hasOne(Payment::class, "form_session_id")->where("status", StatusConstants::SUCCESSFUL);
    }

    function payments()
    {
        return $this->hasMany(Payment::class, "form_session_id")->latest();
    }

    function getStatus()
    {
        return ucwords(str_replace("_", " ", $this->status));
    }

    public function scopeSearch($query, $keyword)
    {
        $query->where(function ($q) use ($keyword) {
            $q->whereRaw("CONCAT(reference, ' ', metadata) LIKE ?", ["%$keyword%"]);
        });
    }

    function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }

}
