<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Production Data Seeder
 * 
 * This seeder runs all data seeders in the correct order for production deployment.
 * It ensures that:
 * 1. Discount codes are created
 * 2. Products are created/updated from CSV
 * 3. Product information is updated with hardcoded data
 * 4. Product images are mapped and migrated
 * 5. Product descriptions are converted to HTML format
 * 
 * Run this seeder after running migrations:
 * php artisan db:seed --class=ProductionDataSeeder
 */
class ProductionDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting production data seeding...');
        $this->command->info('=====================================');

        // Step 1: Seed discount codes (independent, needs discount_codes table)
        $this->command->info("\n[1/7] Seeding discount codes...");
        $this->call(DiscountCodeSeeder::class);

        // Step 2: Seed base products (needs products table)
        $this->command->info("\n[2/7] Seeding base products...");
        $this->call(ProductTableSeeder::class);

        // Step 3: Seed/update products from CSV (needs products table)
        $this->command->info("\n[3/7] Seeding products from CSV...");
        $this->call(PeptideProductSeeder::class);

        // Step 4: Update hardcoded product information (needs products table)
        $this->command->info("\n[4/7] Updating hardcoded product information...");
        $this->call(UpdateHardcodedProductInfoSeeder::class);

        // Step 5: Map product images to image_path column (needs products table)
        $this->command->info("\n[5/7] Mapping product images...");
        $this->call(MapProductImagesSeeder::class);

        // Step 6: Migrate images from image_path to product_images table (needs products and product_images tables)
        $this->command->info("\n[6/7] Migrating product images to product_images table...");
        $this->call(MigrateProductImagesSeeder::class);

        // Step 7: Convert product descriptions to HTML format (needs products table)
        $this->command->info("\n[7/8] Converting product descriptions to HTML...");
        $this->call(UpdateProductDescriptionsSeeder::class);

        // Step 8: Assign categories to products (needs products and product_category tables)
        $this->command->info("\n[8/8] Assigning product categories...");
        $this->call(ProductCategorySeeder::class);

        $this->command->info("\n=====================================");
        $this->command->info('Production data seeding completed successfully!');
    }
}
