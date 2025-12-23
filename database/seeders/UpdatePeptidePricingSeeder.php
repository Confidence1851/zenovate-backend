<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdatePeptidePricingSeeder extends Seeder
{
    /**
     * Calculate number of pens based on subscription duration in months.
     *
     * @param int $frequency
     * @return int
     */
    private function getPensCount(int $frequency): int
    {
        return match ($frequency) {
            3 => 1,
            6 => 2,
            9 => 3,
            12 => 4,
            default => 0,
        };
    }

    /**
     * Generate display name for subscription plan with pens count.
     *
     * @param int $frequency
     * @param string $unit
     * @param int $pens
     * @return string
     */
    private function getDisplayName(int $frequency, string $unit, int $pens): string
    {
        // Capitalize first letter of unit and pluralize if needed
        $unitDisplay = ucfirst($unit);
        if ($frequency > 1 && $unit === 'month') {
            $unitDisplay = 'Months';
        } elseif ($frequency === 1 && $unit === 'month') {
            $unitDisplay = 'Month';
        }

        // Format: "X Months (Y Pen)" or "X Months (Y Pens)"
        if ($pens > 0) {
            $penText = $pens === 1 ? 'Pen' : 'Pens';
            return "{$frequency} {$unitDisplay} ({$pens} {$penText})";
        }

        // If no pens, just show the frequency and unit
        return "{$frequency} {$unitDisplay}";
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pricingData = [
            'Zenfit' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['cad' => 296, 'usd' => 210.16], 'pens' => 0],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['cad' => 567.44, 'usd' => 567.44], 'pens' => 1],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['cad' => 1021.37, 'usd' => 1021.37], 'pens' => 2],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['cad' => 1366.56, 'usd' => 1366.56], 'pens' => 3],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['cad' => 1822.09, 'usd' => 1822.09], 'pens' => 4],
            ],
            'Zenergy' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['cad' => 276, 'usd' => 195.96], 'pens' => 0],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['cad' => 529.09, 'usd' => 529.09], 'pens' => 1],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['cad' => 1058.19, 'usd' => 1058.19], 'pens' => 2],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['cad' => 1499.09, 'usd' => 1499.09], 'pens' => 3],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['cad' => 1998.79, 'usd' => 1998.79], 'pens' => 4],
            ],
            'Zenew' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['cad' => 285, 'usd' => 202.35], 'pens' => 0],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['cad' => 546.35, 'usd' => 546.35], 'pens' => 1],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['cad' => 1092.68, 'usd' => 1092.68], 'pens' => 2],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['cad' => 1547.98, 'usd' => 1547.98], 'pens' => 3],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['cad' => 2063.97, 'usd' => 2063.97], 'pens' => 4],
            ],
            'Zenslim' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['cad' => 315, 'usd' => 223.65], 'pens' => 0],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['cad' => 603.85, 'usd' => 603.85], 'pens' => 1],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['cad' => 1207.70, 'usd' => 1207.70], 'pens' => 2],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['cad' => 1710.92, 'usd' => 1710.92], 'pens' => 3],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['cad' => 2281.23, 'usd' => 2281.23], 'pens' => 4],
            ],
            'Zenluma' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['cad' => 272, 'usd' => 193.12], 'pens' => 0],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['cad' => 521.42, 'usd' => 521.42], 'pens' => 1],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['cad' => 1042.84, 'usd' => 1042.84], 'pens' => 2],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['cad' => 1477.37, 'usd' => 1477.37], 'pens' => 3],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['cad' => 1969.82, 'usd' => 1969.82], 'pens' => 4],
            ],
            'ZenCover' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['cad' => 307, 'usd' => 217.97], 'pens' => 0],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['cad' => 588.51, 'usd' => 588.51], 'pens' => 1],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['cad' => 1177.03, 'usd' => 1177.03], 'pens' => 2],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['cad' => 1961.73, 'usd' => 1961.73], 'pens' => 3],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['cad' => 2223.29, 'usd' => 2223.29], 'pens' => 4],
            ],
            'Zenlean' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['cad' => 282, 'usd' => 200.22], 'pens' => 0],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['cad' => 540.60, 'usd' => 540.60], 'pens' => 1],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['cad' => 1081.18, 'usd' => 1081.18], 'pens' => 2],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['cad' => 1531.68, 'usd' => 1531.68], 'pens' => 3],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['cad' => 2042.24, 'usd' => 2042.24], 'pens' => 4],
            ],
            'Zenmune' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['cad' => 313, 'usd' => 222.23], 'pens' => 0],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['cad' => 599.99, 'usd' => 599.99], 'pens' => 1],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['cad' => 1199.99, 'usd' => 1199.99], 'pens' => 2],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['cad' => 1700.06, 'usd' => 1700.06], 'pens' => 3],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['cad' => 2266.75, 'usd' => 2266.75], 'pens' => 4],
            ],
        ];

        foreach ($pricingData as $productName => $prices) {
            $product = Product::where('name', $productName)->first();

            if ($product) {
                // Ensure pens count and display_name are set for each price entry
                $updatedPrices = array_map(function ($price) {
                    $frequency = $price['frequency'] ?? 0;
                    $unit = $price['unit'] ?? 'month';
                    
                    // Calculate pens count if not set
                    if (!isset($price['pens'])) {
                        $price['pens'] = $this->getPensCount($frequency);
                    }
                    
                    // Add display_name for subscription plans with month unit
                    if ($unit === 'month' && $frequency > 0) {
                        $price['display_name'] = $this->getDisplayName($frequency, $unit, $price['pens']);
                    }
                    
                    return $price;
                }, $prices);

                $product->price = $updatedPrices;
                $product->save();
                $this->command->info("Updated pricing for: {$productName}");
            } else {
                $this->command->warn("Product not found: {$productName}");
            }
        }

        // Also update any other products with subscription plans (month unit)
        $this->updateAllSubscriptionProducts();

        $this->command->info('Peptide pricing update completed!');
    }

    /**
     * Update all products with subscription plans to include display_name and pens count.
     */
    private function updateAllSubscriptionProducts(): void
    {
        $products = Product::whereNotNull('price')->get();
        $updatedCount = 0;

        foreach ($products as $product) {
            if (empty($product->price) || !is_array($product->price)) {
                continue;
            }

            $updated = false;
            $updatedPrices = array_map(function ($price) use (&$updated) {
                // Only process subscription plans with month unit
                if (!isset($price['frequency']) || !isset($price['unit']) || $price['unit'] !== 'month') {
                    return $price;
                }

                $frequency = (int) $price['frequency'];
                
                // Calculate pens count if not set
                if (!isset($price['pens'])) {
                    $price['pens'] = $this->getPensCount($frequency);
                    $updated = true;
                }
                
                // Add display_name if not set
                if (!isset($price['display_name'])) {
                    $price['display_name'] = $this->getDisplayName($frequency, $price['unit'], $price['pens']);
                    $updated = true;
                }
                
                return $price;
            }, $product->price);

            if ($updated) {
                $product->price = $updatedPrices;
                $product->save();
                $updatedCount++;
                $this->command->info("Updated subscription pricing for: {$product->name}");
            }
        }

        if ($updatedCount > 0) {
            $this->command->info("Updated {$updatedCount} additional products with subscription plans.");
        }
    }
}
