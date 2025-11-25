<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $table = 'product_category';
    
    protected $guarded = ['id'];
    
    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Get the product that owns this category assignment
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to filter by category slug
     */
    public function scopeByCategory($query, $slug)
    {
        return $query->where('category_slug', $slug);
    }

    /**
     * Scope to order by order field
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Get category image URL
     */
    public function getCategoryImageUrlAttribute()
    {
        if (!$this->category_image_path) {
            return null;
        }

        // Generate secure URL for category image (same method as products)
        $encrypted = \App\Helpers\Helper::encrypt_decrypt("encrypt", $this->category_image_path);
        if ($encrypted) {
            $baseUrl = env('APP_URL', 'http://localhost');
            return rtrim($baseUrl, '/') . '/api/get-file/' . $encrypted;
        }
        return null;
    }
}
