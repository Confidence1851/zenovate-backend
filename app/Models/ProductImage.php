<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $guarded = ['id'];
    
    protected $casts = [
        'is_primary' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the product that owns the image
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to get primary image
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to order by display_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc');
    }

    /**
     * Get image URL
     */
    public function getImageUrl(): ?string
    {
        if (empty($this->image_path)) {
            return null;
        }

        $encrypted = \App\Helpers\Helper::encrypt_decrypt("encrypt", $this->image_path);
        if ($encrypted) {
            $baseUrl = env('APP_URL', 'http://localhost');
            return rtrim($baseUrl, '/') . '/api/get-file/' . $encrypted;
        }
        
        return null;
    }
}
