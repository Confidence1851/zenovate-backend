<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GeneralException;
use App\Helpers\ApiConstants;
use App\Helpers\ApiHelper;
use App\Helpers\StatusConstants;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class CategoryController extends Controller
{
    /**
     * List all unique categories with product counts (only categories with active products)
     */
    public function index()
    {
        try {
            $categories = ProductCategory::withCount(['products' => function ($query) {
                $query->where('status', StatusConstants::ACTIVE);
            }])
                ->having('products_count', '>', 0)
                ->orderBy('order', 'asc')
                ->orderBy('name', 'asc')
                ->get()
                ->map(function ($category) {
                    return [
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'description' => $category->description,
                        'image_url' => $category->image_url,
                        'products_count' => $category->products_count,
                        'order' => $category->order,
                    ];
                });

            return ApiHelper::validResponse(
                'Categories retrieved successfully',
                $categories
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }

    /**
     * Get category details by slug with all products
     */
    public function show($slug)
    {
        try {
            $category = ProductCategory::where('slug', $slug)
                ->withCount(['products' => function ($query) {
                    $query->where('status', StatusConstants::ACTIVE);
                }])
                ->first();

            if (!$category) {
                return ApiHelper::problemResponse(
                    'Category not found',
                    ApiConstants::BAD_REQ_ERR_CODE
                );
            }

            // Return 404 if category has no active products
            if ($category->products_count === 0) {
                return ApiHelper::problemResponse(
                    'Category not found',
                    ApiConstants::NOT_FOUND_ERR_CODE
                );
            }

            $categoryData = [
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'image_url' => $category->image_url,
                'products_count' => $category->products_count,
            ];

            return ApiHelper::validResponse(
                'Category retrieved successfully',
                $categoryData
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }

    /**
     * Get all products in a category by slug
     */
    public function products($slug)
    {
        try {
            $category = ProductCategory::where('slug', $slug)->first();

            if (!$category) {
                return ApiHelper::problemResponse(
                    'Category not found',
                    ApiConstants::BAD_REQ_ERR_CODE
                );
            }

            $products = Product::where('status', StatusConstants::ACTIVE)
                ->where('category_id', $category->id)
                ->with('category')
                ->get();

            return ApiHelper::validResponse(
                'Category products retrieved successfully',
                ProductResource::collection($products)
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }
}
