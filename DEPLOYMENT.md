# Production Deployment Guide

## Database Setup

### Step 1: Run Migrations

Run all database migrations to create/update tables:

```bash
php artisan migrate --force
```

This will create/update:
- `discount_codes` table
- `product_images` table
- `payments` table (with discount fields)
- All other required tables

### Step 2: Run Production Seeders

Run the production data seeder to populate all data:

```bash
php artisan db:seed --class=ProductionDataSeeder --force
```

This will:
1. Create discount codes (20BF, 50CP, 30FF)
2. Create base products
3. Create/update products from CSV
4. Update product information
5. Map and migrate product images
6. Convert product descriptions to HTML

### Alternative: Run All Seeders

To run all seeders including users:

```bash
php artisan db:seed --force
```

This runs:
- UserTableSeeder
- ProductionDataSeeder (which includes all product and discount seeders)

## Required Files

Ensure these files exist before running seeders:

1. **CSV File**: `storage/app/tmp/peptides.csv`
   - Required for PeptideProductSeeder
   - Contains product data with pricing and descriptions

2. **Product Images Directory**: `storage/app/private/products/`
   - Required for image mapping and migration
   - Should contain product image files

3. **Placeholder Image**: `storage/app/private/products/placeholder.png`
   - Used as fallback for products without images

## Verification

After seeding, verify:

1. **Discount Codes**: Check that 3 discount codes exist
   ```bash
   php artisan tinker --execute="echo App\Models\DiscountCode::count();"
   ```

2. **Products**: Check that products are created and active
   ```bash
   php artisan tinker --execute="echo App\Models\Product::where('status', 'Active')->count();"
   ```

3. **Product Images**: Check that images are migrated
   ```bash
   php artisan tinker --execute="echo App\Models\ProductImage::count();"
   ```

## Troubleshooting

### CSV File Not Found
- Ensure `storage/app/tmp/peptides.csv` exists
- Check file permissions

### Images Not Migrating
- Ensure `product_images` table exists (run migrations)
- Check that products have `image_path` set
- Verify `storage/app/private/products/` directory exists

### Seeders Can Be Run Multiple Times
All seeders are idempotent and safe to run multiple times. They use `updateOrCreate` or `firstOrCreate` to avoid duplicates.

