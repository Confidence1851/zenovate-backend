<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MapProductImagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productsDir = storage_path('app/private/products');
        $frontendImagesDir = base_path('../site/src/assets/images');
        
        // Ensure products directory exists
        if (!File::exists($productsDir)) {
            File::makeDirectory($productsDir, 0755, true);
        }

        $mappedCount = 0;
        $copiedCount = 0;
        $updatedCount = 0;

        // Map existing images in backend storage to products
        $existingImages = [
            'zencover.png' => 'ZenCover',
            'zenergy.png' => 'Zenergy',
            'zenew.png' => 'Zenew',
            'zenfit.png' => 'Zenfit',
            'zenlean.png' => 'Zenlean',
            'zenluma.png' => 'Zenluma',
            'zenmune.png' => 'Zenmune',
            'zenslim.png' => 'Zenslim',
        ];

        foreach ($existingImages as $filename => $productName) {
            $filePath = $productsDir . '/' . $filename;
            if (File::exists($filePath)) {
                $product = Product::where('name', $productName)->first();
                if ($product) {
                    $product->image_path = "products/{$filename}";
                    $product->save();
                    $mappedCount++;
                    $this->command->info("Mapped: {$filename} → {$productName}");
                }
            }
        }

        // Copy frontend images to backend storage and map them
        $frontendToBackendMapping = [
            'u18nD58H5v2EH1.png' => ['activa.png', 'Activa'],
            '1duXn1s1.png' => ['gloria.png', 'Gloria'],
            '2HQyRBbVQmOWWZhM1.png' => ['immuna.png', 'Immuna'],
            '6LxAAhJso7Dk1.png' => ['energia.png', 'Energia'],
            'l7lA2QieckkRg1.png' => ['nadiva.png', 'Nadiva'],
            'epipen1.jpg' => ['epipen1.jpg', 'EpiPen'],
            'epipen2.jpg' => ['epipen2.jpg', 'EpiPen'],
        ];

        foreach ($frontendToBackendMapping as $frontendFile => $backendData) {
            $backendFilename = $backendData[0];
            $productName = $backendData[1];
            
            $sourcePath = $frontendImagesDir . '/' . $frontendFile;
            $destPath = $productsDir . '/' . $backendFilename;

            if (File::exists($sourcePath)) {
                // Copy file if it doesn't exist in backend
                if (!File::exists($destPath)) {
                    File::copy($sourcePath, $destPath);
                    $copiedCount++;
                    $this->command->info("Copied: {$frontendFile} → {$backendFilename}");
                }

                // Update product with image path
                $product = Product::where('name', $productName)->first();
                if ($product) {
                    // Handle EpiPen with multiple images
                    if ($productName === 'EpiPen') {
                        $currentPath = $product->image_path;
                        if (empty($currentPath)) {
                            $product->image_path = "products/{$backendFilename}";
                        } else {
                            // Store multiple images as comma-separated
                            $images = explode(',', $currentPath);
                            $newPath = "products/{$backendFilename}";
                            if (!in_array($newPath, $images)) {
                                $images[] = $newPath;
                                $product->image_path = implode(',', $images);
                            }
                        }
                    } else {
                        $product->image_path = "products/{$backendFilename}";
                    }
                    $product->save();
                    $updatedCount++;
                    $this->command->info("Updated: {$productName} → products/{$backendFilename}");
                }
            } else {
                $this->command->warn("Source file not found: {$sourcePath}");
            }
        }

        // Try to map any remaining products by matching filenames
        $allProducts = Product::all();
        $imageFiles = File::files($productsDir);
        
        foreach ($allProducts as $product) {
            if (empty($product->image_path)) {
                // Try to find matching image file
                $productNameLower = strtolower(str_replace(' ', '', $product->name));
                
                foreach ($imageFiles as $imageFile) {
                    $filename = $imageFile->getFilename();
                    $filenameWithoutExt = strtolower(pathinfo($filename, PATHINFO_FILENAME));
                    
                    // Check if filename matches product name (case-insensitive, no spaces)
                    if ($filenameWithoutExt === $productNameLower || 
                        strpos($filenameWithoutExt, $productNameLower) === 0 ||
                        strpos($productNameLower, $filenameWithoutExt) === 0) {
                        $product->image_path = "products/{$filename}";
                        $product->save();
                        $updatedCount++;
                        $this->command->info("Auto-mapped: {$product->name} → products/{$filename}");
                        break;
                    }
                }
            }
        }

        $this->command->info("\nSeeder completed!");
        $this->command->info("Images mapped from existing: {$mappedCount}");
        $this->command->info("Images copied from frontend: {$copiedCount}");
        $this->command->info("Products updated: {$updatedCount}");
    }
}

