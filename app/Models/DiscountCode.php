<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscountCode extends Model
{
    use SoftDeletes;
    
    protected $guarded = ['id'];
    
    protected $casts = [
        'value' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
    ];

    /**
     * Check if the discount code is currently valid
     */
    public function isValid(): bool
    {
        // Check status
        if ($this->status !== 'Active') {
            return false;
        }

        // Check date range
        $now = now();
        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }
        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        // Check usage limit (0 = unlimited)
        if ($this->usage_limit > 0 && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
