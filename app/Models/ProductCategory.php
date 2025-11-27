<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $table = 'product_categories';

    protected $guarded = ['id'];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Get all products in this category
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * Scope to filter by category slug
     */
    public function scopeByCategory($query, $slug)
    {
        return $query->where('slug', $slug);
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
    public function getImageUrlAttribute()
    {
        if (!$this->image_path) {
            return null;
        }

        // Generate secure URL for category image (same method as products)
        $encrypted = \App\Helpers\Helper::encrypt_decrypt("encrypt", $this->image_path);
        if ($encrypted) {
            $baseUrl = env('APP_URL', 'http://localhost');
            return rtrim($baseUrl, '/') . '/api/get-file/' . $encrypted;
        }
        return null;
    }
}
