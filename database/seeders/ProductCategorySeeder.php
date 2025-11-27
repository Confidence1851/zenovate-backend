<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Helpers\StatusConstants;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding product categories...');

        // Define categories
        $categories = [
            [
                'name' => 'Peptides and more',
                'slug' => 'peptides',
                'description' => 'Advanced peptide formulations for targeted health benefits',
            ],
            [
                'name' => 'Wellness',
                'slug' => 'wellness',
                'description' => 'Comprehensive wellness solutions for optimal health',
            ],
            [
                'name' => 'Supplements',
                'slug' => 'supplements',
                'description' => 'Essential supplements to support your health journey',
            ],
        ];

        // Create or get categories in product_categories table
        $categoryIds = [];
        foreach ($categories as $categoryData) {
            $category = ProductCategory::where('slug', $categoryData['slug'])->first();

            if (!$category) {
                $category = ProductCategory::create([
                    'name' => $categoryData['name'],
                    'slug' => $categoryData['slug'],
                    'description' => $categoryData['description'],
                    'order' => 0,
                ]);
            }
            $categoryIds[$categoryData['slug']] = $category->id;
        }

        // Get all active products
        $products = Product::where('status', StatusConstants::ACTIVE)->get();

        if ($products->isEmpty()) {
            $this->command->warn('No active products found. Please run product seeders first.');
            return;
        }

        $assignedCount = 0;

        foreach ($products as $product) {
            // Determine category based on product name or assign default
            $category = $this->determineCategory($product->name, $categories);

            if ($category && isset($categoryIds[$category['slug']])) {
                // Update product with category_id
                if ($product->category_id !== $categoryIds[$category['slug']]) {
                    $product->category_id = $categoryIds[$category['slug']];
                    $product->save();
                    $assignedCount++;
                }
            }
        }

        $this->command->info("Product categories seeded successfully! Assigned {$assignedCount} categories to products.");
    }

    /**
     * Determine category for a product based on its name
     */
    private function determineCategory(string $productName, array $categories): ?array
    {
        $nameLower = strtolower($productName);

        // Check for peptide-related keywords
        $peptideKeywords = ['peptide', 'zen', 'cjc', 'sema', 'glp'];
        foreach ($peptideKeywords as $keyword) {
            if (strpos($nameLower, $keyword) !== false) {
                return $categories[0]; // Peptides
            }
        }

        // Check for wellness-related keywords
        $wellnessKeywords = ['wellness', 'health', 'immune', 'energy', 'vitality'];
        foreach ($wellnessKeywords as $keyword) {
            if (strpos($nameLower, $keyword) !== false) {
                return $categories[1]; // Wellness
            }
        }

        // Default to Supplements for others
        return $categories[2]; // Supplements
    }
}
