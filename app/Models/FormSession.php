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

    /**
     * Get payment status display text
     */
    public function getPaymentStatus(): string
    {
        $payment = $this->completedPayment;
        return $payment ? 'Paid' : 'Unpaid';
    }

    /**
     * Get booking type display text
     */
    public function getBookingTypeDisplay(): string
    {
        return ucfirst($this->booking_type ?? 'form');
    }

    /**
     * Get customer name from metadata or user
     */
    public function getCustomerName(): string
    {
        $firstName = $this->metadata['raw']['firstName'] ?? $this->user->first_name ?? '';
        $lastName = $this->metadata['raw']['lastName'] ?? $this->user->last_name ?? '';
        return trim($firstName . ' ' . $lastName) ?: 'N/A';
    }

    /**
     * Get customer email from metadata or user
     */
    public function getCustomerEmail(): string
    {
        return $this->metadata['raw']['email'] ?? $this->user->email ?? 'N/A';
    }

    /**
     * Get customer phone from metadata or user
     */
    public function getCustomerPhone(): string
    {
        return $this->metadata['raw']['phoneNumber'] ?? $this->user->phone ?? 'N/A';
    }

    public function scopeSearch($query, $keyword)
    {
        $query->where(function ($q) use ($keyword) {
            $searchTerm = "%{$keyword}%";
            $q->where('reference', 'LIKE', $searchTerm)
                ->orWhereRaw("JSON_EXTRACT(metadata, '$.raw.firstName') LIKE ?", [$searchTerm])
                ->orWhereRaw("JSON_EXTRACT(metadata, '$.raw.lastName') LIKE ?", [$searchTerm])
                ->orWhereRaw("JSON_EXTRACT(metadata, '$.raw.email') LIKE ?", [$searchTerm])
                ->orWhereRaw("JSON_EXTRACT(metadata, '$.raw.phoneNumber') LIKE ?", [$searchTerm])
                ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                    $userQuery->where('first_name', 'LIKE', $searchTerm)
                        ->orWhere('last_name', 'LIKE', $searchTerm)
                        ->orWhere('email', 'LIKE', $searchTerm)
                        ->orWhere('phone', 'LIKE', $searchTerm);
                });
        });
    }

    function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }
}
