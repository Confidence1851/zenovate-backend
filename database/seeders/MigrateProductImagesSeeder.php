<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MigrateProductImagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::whereNotNull('image_path')
            ->where('image_path', '!=', '')
            ->get();

        $migratedCount = 0;
        $skippedCount = 0;

        foreach ($products as $product) {
            // Check if images already migrated
            if (ProductImage::where('product_id', $product->id)->exists()) {
                $this->command->info("Skipping {$product->name} - images already migrated");
                $skippedCount++;
                continue;
            }

            // Parse comma-separated image paths
            $imagePaths = explode(',', $product->image_path);
            $imagePaths = array_map('trim', $imagePaths);
            $imagePaths = array_filter($imagePaths); // Remove empty values

            if (empty($imagePaths)) {
                $skippedCount++;
                continue;
            }

            // Create ProductImage records
            foreach ($imagePaths as $index => $imagePath) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $imagePath,
                    'display_order' => $index,
                    'is_primary' => $index === 0, // First image is primary
                ]);
            }

            $migratedCount++;
            $this->command->info("Migrated {$product->name}: " . count($imagePaths) . " image(s)");
        }

        $this->command->info("\nMigration completed!");
        $this->command->info("Products migrated: {$migratedCount}");
        $this->command->info("Products skipped: {$skippedCount}");
        $this->command->info("Total images migrated: " . ProductImage::count());
    }
}

