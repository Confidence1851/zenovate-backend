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
            $categories = ProductCategory::select('category_name', 'category_slug', 'category_description', 'category_image_path')
                ->join('products', 'product_category.product_id', '=', 'products.id')
                ->where('products.status', StatusConstants::ACTIVE)
                ->selectRaw('COUNT(DISTINCT product_category.product_id) as products_count')
                ->groupBy('category_name', 'category_slug', 'category_description', 'category_image_path')
                ->having('products_count', '>', 0)
                ->orderBy('category_name', 'asc')
                ->get()
                ->map(function ($item) {
                    $imageUrl = null;
                    if ($item->category_image_path) {
                        $encrypted = \App\Helpers\Helper::encrypt_decrypt("encrypt", $item->category_image_path);
                        if ($encrypted) {
                            $baseUrl = env('APP_URL', 'http://localhost');
                            $imageUrl = rtrim($baseUrl, '/') . '/api/get-file/' . $encrypted;
                        }
                    }

                    return [
                        'name' => $item->category_name,
                        'slug' => $item->category_slug,
                        'description' => $item->category_description,
                        'image_url' => $imageUrl,
                        'products_count' => $item->products_count,
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
            $category = ProductCategory::where('category_slug', $slug)
                ->first();

            if (!$category) {
                return ApiHelper::problemResponse(
                    'Category not found',
                    ApiConstants::BAD_REQ_ERR_CODE
                );
            }

            // Count only active products
            $productsCount = ProductCategory::where('category_slug', $slug)
                ->join('products', 'product_category.product_id', '=', 'products.id')
                ->where('products.status', StatusConstants::ACTIVE)
                ->count();

            // Return 404 if category has no active products
            if ($productsCount === 0) {
                return ApiHelper::problemResponse(
                    'Category not found',
                    ApiConstants::NOT_FOUND_ERR_CODE
                );
            }

            $imageUrl = null;
            if ($category->category_image_path) {
                $encrypted = \App\Helpers\Helper::encrypt_decrypt("encrypt", $category->category_image_path);
                if ($encrypted) {
                    $baseUrl = env('APP_URL', 'http://localhost');
                    $imageUrl = rtrim($baseUrl, '/') . '/api/get-file/' . $encrypted;
                }
            }

            $categoryData = [
                'name' => $category->category_name,
                'slug' => $category->category_slug,
                'description' => $category->category_description,
                'image_url' => $imageUrl,
                'products_count' => $productsCount,
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
            $productIds = ProductCategory::where('category_slug', $slug)
                ->pluck('product_id');

            $products = Product::where('status', StatusConstants::ACTIVE)
                ->whereIn('id', $productIds)
                ->with('productCategories')
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
