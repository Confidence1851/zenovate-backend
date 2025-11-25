<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Services\General\ProductService;
use App\Helpers\StatusConstants;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PeptideProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = storage_path('app/tmp/peptides.csv');
        
        if (!file_exists($csvPath)) {
            $this->command->error("CSV file not found at: {$csvPath}");
            return;
        }

        $file = fopen($csvPath, 'r');
        if (!$file) {
            $this->command->error("Could not open CSV file: {$csvPath}");
            return;
        }

        // Read header row
        $headers = fgetcsv($file);
        if (!$headers) {
            $this->command->error("Could not read CSV headers");
            fclose($file);
            return;
        }

        // Map header indices
        $indices = [];
        foreach ($headers as $index => $header) {
            $indices[trim($header)] = $index;
        }

        $rowCount = 0;
        $createdCount = 0;
        $updatedCount = 0;

        // Read data rows
        while (($row = fgetcsv($file)) !== false) {
            $rowCount++;
            
            // Skip empty rows or disclaimer rows
            if (empty($row[0]) || trim($row[0]) === 'Disclaimer' || trim($row[0]) === '') {
                continue;
            }

            // Get suggested names (use first name if multiple)
            $suggestedNames = $this->getValue($row, $indices, 'Suggested Names');
            if (empty($suggestedNames)) {
                // Skip rows without suggested names
                continue;
            }

            $name = $this->extractFirstName($suggestedNames);
            $peptide = $this->getValue($row, $indices, 'Peptide');
            $benefits = $this->getValue($row, $indices, 'Benefits');
            $contraindications = $this->getValue($row, $indices, 'Contraindications');
            $penStrength = $this->getValue($row, $indices, 'Pen Strength');
            $oneMonthDose = $this->getValue($row, $indices, '1-Month Dose');
            $threeMonthsDose = $this->getValue($row, $indices, '3-Months Dose');
            $reference = $this->getValue($row, $indices, 'Reference');
            
            // Pricing data
            $pricingIndividualUsd = $this->cleanPrice($this->getValue($row, $indices, 'Pricing for Individual Clients (USD)'));
            $pricingIndividualCad = $this->cleanPrice($this->getValue($row, $indices, 'Pricing for Individual Clients (CAD)'));
            $pricingClinicUsd = $this->cleanPrice($this->getValue($row, $indices, 'Pricing for Clinics (USD)'));
            $pricingClinicCad = $this->cleanPrice($this->getValue($row, $indices, 'Pricing for Clinics (CAD)'));
            $usdPricing = $this->cleanPrice($this->getValue($row, $indices, 'USD(Pricing)'));
            $cadPricing = $this->cleanPrice($this->getValue($row, $indices, 'CAD(Pricing)'));

            // Build description
            $descriptionParts = [];
            
            if ($peptide) {
                $descriptionParts[] = "Peptide: " . $this->cleanText($peptide);
            }
            
            if ($benefits) {
                $descriptionParts[] = "Benefits: " . $this->cleanText($benefits);
            }
            
            if ($penStrength) {
                $descriptionParts[] = "Pen Strength: " . $this->cleanText($penStrength);
            }
            
            if ($oneMonthDose) {
                $descriptionParts[] = "1-Month Dose: " . $this->cleanText($oneMonthDose);
            }
            
            if ($threeMonthsDose) {
                $descriptionParts[] = "3-Months Dose: " . $this->cleanText($threeMonthsDose);
            }
            
            if ($contraindications) {
                $descriptionParts[] = "Contraindications: " . $this->cleanText($contraindications);
            }
            
            if ($reference) {
                $descriptionParts[] = "Reference: " . $this->cleanText($reference);
            }

            $description = !empty($descriptionParts) ? implode("\n\n", $descriptionParts) : null;

            // Build subtitle from peptide name
            $subtitle = $peptide ?: null;

            // Build price array
            $price = [];
            
            // Add individual client pricing if available
            if ($pricingIndividualUsd || $pricingIndividualCad) {
                $values = [];
                if ($pricingIndividualUsd) {
                    $values['usd'] = (float)$pricingIndividualUsd;
                }
                if ($pricingIndividualCad) {
                    $values['cad'] = (float)$pricingIndividualCad;
                }
                
                if (!empty($values)) {
                    $price[] = [
                        "frequency" => 12,
                        "unit" => "week",
                        "values" => $values
                    ];
                }
            }
            
            // Add clinic pricing if available (as a different tier)
            if ($pricingClinicUsd || $pricingClinicCad) {
                $values = [];
                if ($pricingClinicUsd) {
                    $values['usd'] = (float)$pricingClinicUsd;
                }
                if ($pricingClinicCad) {
                    $values['cad'] = (float)$pricingClinicCad;
                }
                
                if (!empty($values)) {
                    $price[] = [
                        "frequency" => 36,
                        "unit" => "week",
                        "values" => $values
                    ];
                }
            }
            
            // Fallback to base pricing if no individual/clinic pricing
            if (empty($price) && ($usdPricing || $cadPricing)) {
                $values = [];
                if ($usdPricing) {
                    $values['usd'] = (float)$usdPricing;
                }
                if ($cadPricing) {
                    $values['cad'] = (float)$cadPricing;
                }
                
                if (!empty($values)) {
                    $price[] = [
                        "frequency" => 12,
                        "unit" => "week",
                        "values" => $values
                    ];
                }
            }

            // Prepare product data
            $productData = [
                "name" => $name,
                "subtitle" => $subtitle,
                "description" => $description ?: null,
                "benefits" => $benefits ?: null,
                "status" => StatusConstants::ACTIVE,
                "airtable_id" => null,
                "nav_description" => null,
                "key_ingredients" => $peptide ?: null,
                "price" => !empty($price) ? $price : null,
            ];

            // Create or update product
            $product = Product::where('name', $name)->first();
            
            if ($product) {
                $product->update($productData);
                $product->slug = ProductService::generateSlug($product);
                $product->save();
                $updatedCount++;
                $this->command->info("Updated: {$name}");
            } else {
                $product = new Product($productData);
                $product->slug = ProductService::generateSlug($product);
                $product->save();
                $createdCount++;
                $this->command->info("Created: {$name}");
            }
        }

        fclose($file);

        $this->command->info("\nSeeder completed!");
        $this->command->info("Total rows processed: {$rowCount}");
        $this->command->info("Products created: {$createdCount}");
        $this->command->info("Products updated: {$updatedCount}");
    }

    /**
     * Get value from CSV row by column name
     */
    private function getValue(array $row, array $indices, string $columnName): ?string
    {
        if (!isset($indices[$columnName])) {
            return null;
        }

        $index = $indices[$columnName];
        $value = isset($row[$index]) ? trim($row[$index]) : null;
        
        return $value !== '' ? $value : null;
    }

    /**
     * Extract first name from comma-separated suggested names
     */
    private function extractFirstName(string $suggestedNames): string
    {
        $names = array_map('trim', explode(',', $suggestedNames));
        return $names[0];
    }

    /**
     * Clean price value (remove $, commas, and other non-numeric characters except decimal point)
     */
    private function cleanPrice(?string $price): ?string
    {
        if (empty($price)) {
            return null;
        }

        // Remove $, commas, and other non-numeric characters except decimal point
        $cleaned = preg_replace('/[^0-9.]/', '', $price);
        
        return $cleaned !== '' ? $cleaned : null;
    }

    /**
     * Clean text value (remove extra whitespace and normalize line breaks)
     */
    private function cleanText(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // Normalize line breaks and remove extra whitespace
        $cleaned = preg_replace('/\s+/', ' ', $text);
        $cleaned = trim($cleaned);
        
        return $cleaned;
    }
}

