# Database Seeders

This directory contains seeders for populating the database with initial and production data.

## Production Seeding Order

For production deployment, run seeders in the following order:

### Option 1: Use ProductionDataSeeder (Recommended)

Run the comprehensive production seeder that handles everything in the correct order:

```bash
php artisan db:seed --class=ProductionDataSeeder
```

This will run all seeders in the correct sequence:
1. **DiscountCodeSeeder** - Creates discount codes (20BF, 50CP, 30FF)
2. **ProductTableSeeder** - Creates base products (Immuna, Activa, Nadiva, Gloria, Energia)
3. **PeptideProductSeeder** - Creates/updates products from CSV file
4. **UpdateHardcodedProductInfoSeeder** - Updates specific products with hardcoded information
5. **MapProductImagesSeeder** - Maps product images to `image_path` column
6. **MigrateProductImagesSeeder** - Migrates images from `image_path` to `product_images` table
7. **UpdateProductDescriptionsSeeder** - Converts product descriptions to HTML format

### Option 2: Use DatabaseSeeder

Run the main database seeder:

```bash
php artisan db:seed
```

This will run:
- UserTableSeeder (creates admin users)
- ProductionDataSeeder (runs all product and discount code seeders)

### Option 3: Run Individual Seeders

If you need to run seeders individually, follow this order:

```bash
# 1. Discount codes (independent)
php artisan db:seed --class=DiscountCodeSeeder

# 2. Base products
php artisan db:seed --class=ProductTableSeeder

# 3. Products from CSV
php artisan db:seed --class=PeptideProductSeeder

# 4. Update hardcoded product info
php artisan db:seed --class=UpdateHardcodedProductInfoSeeder

# 5. Map images
php artisan db:seed --class=MapProductImagesSeeder

# 6. Migrate images to product_images table
php artisan db:seed --class=MigrateProductImagesSeeder

# 7. Convert descriptions to HTML
php artisan db:seed --class=UpdateProductDescriptionsSeeder
```

## Prerequisites

Before running seeders, ensure:

1. **Migrations are run**: All database migrations must be executed first
   ```bash
   php artisan migrate
   ```

2. **Required files exist**:
   - `storage/app/tmp/peptides.csv` - Required for PeptideProductSeeder
   - `storage/app/private/products/` - Directory for product images

3. **Required tables exist**:
   - `discount_codes` - For DiscountCodeSeeder
   - `products` - For all product-related seeders
   - `product_images` - For MigrateProductImagesSeeder

## Seeder Dependencies

```
DiscountCodeSeeder (independent)
    ↓
ProductTableSeeder
    ↓
PeptideProductSeeder
    ↓
UpdateHardcodedProductInfoSeeder
    ↓
MapProductImagesSeeder
    ↓
MigrateProductImagesSeeder (requires product_images table)
    ↓
UpdateProductDescriptionsSeeder (can run anytime after products exist)
```

## Notes

- All seeders use `updateOrCreate` or `firstOrCreate` to be idempotent (safe to run multiple times)
- MigrateProductImagesSeeder checks if images are already migrated to avoid duplicates
- UpdateProductDescriptionsSeeder skips products that already have HTML descriptions
- PeptideProductSeeder requires the CSV file at `storage/app/tmp/peptides.csv`

## Production Deployment

For production, run migrations first, then seeders:

```bash
# Run migrations
php artisan migrate --force

# Run production seeders
php artisan db:seed --class=ProductionDataSeeder --force
```

The `--force` flag is required in production to prevent confirmation prompts.

