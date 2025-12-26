<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class UpdatePeptidePricingSeeder extends Seeder
{
    /**
     * Calculate number of pens based on subscription duration in months.
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
                ['frequency' => 1, 'unit' => 'month', 'values' => ['usd' => 197.33, 'cad' => 197.33]],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['usd' => 592, 'cad' => 592]],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['usd' => 1065.60, 'cad' => 1065.60]],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['usd' => 1509.60, 'cad' => 1509.60]],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['usd' => 2012.80, 'cad' => 2012.80]],
            ],
            'Zenergy' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['usd' => 184, 'cad' => 184]],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['usd' => 552, 'cad' => 552]],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['usd' => 993.6, 'cad' => 993.6]],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['usd' => 1407.60, 'cad' => 1407.60]],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['usd' => 1876.80, 'cad' => 1876.80]],
            ],
            'Zenew' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['usd' => 190, 'cad' => 190]],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['usd' => 570, 'cad' => 570]],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['usd' => 1026.00, 'cad' => 1026.00]],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['usd' => 1539.00, 'cad' => 1539.00]],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['usd' => 1938.00, 'cad' => 1938.00]],
            ],
            'Zenslim' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['usd' => 210, 'cad' => 210]],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['usd' => 630, 'cad' => 630]],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['usd' => 1134.00, 'cad' => 1134.00]],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['usd' => 1606.50, 'cad' => 1606.50]],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['usd' => 2142.00, 'cad' => 2142.00]],
            ],
            'Zenluma' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['usd' => 181.33, 'cad' => 181.33]],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['usd' => 544, 'cad' => 544]],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['usd' => 979.2, 'cad' => 979.2]],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['usd' => 1387.20, 'cad' => 1387.20]],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['usd' => 1849.60, 'cad' => 1849.60]],
            ],
            'ZenCover' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['usd' => 204.67, 'cad' => 204.67]],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['usd' => 614, 'cad' => 614]],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['usd' => 1105.20, 'cad' => 1105.20]],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['usd' => 1565.70, 'cad' => 1565.70]],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['usd' => 2087.60, 'cad' => 2087.60]],
            ],
            'Zenlean' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['usd' => 188, 'cad' => 188]],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['usd' => 564, 'cad' => 564]],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['usd' => 1015.20, 'cad' => 1015.20]],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['usd' => 1438.20, 'cad' => 1438.20]],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['usd' => 1917.60, 'cad' => 1917.60]],
            ],
            'Zenmune' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['usd' => 208.67, 'cad' => 208.67]],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['usd' => 626, 'cad' => 626]],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['usd' => 1126.80, 'cad' => 1126.80]],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['usd' => 1596.30, 'cad' => 1596.30]],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['usd' => 2128.40, 'cad' => 2128.40]],
            ],
        ];

        foreach ($pricingData as $productName => $prices) {
            $product = Product::where('name', $productName)->first();

            if ($product) {
                $product->price = $prices;
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
            if (empty($product->price) || ! is_array($product->price)) {
                continue;
            }

            $updated = false;
            $updatedPrices = array_map(function ($price) use (&$updated) {
                // Only process subscription plans with month unit
                if (! isset($price['frequency']) || ! isset($price['unit']) || $price['unit'] !== 'month') {
                    return $price;
                }

                $frequency = (int) $price['frequency'];

                // Calculate pens count if not set
                if (! isset($price['pens'])) {
                    $price['pens'] = $this->getPensCount($frequency);
                    $updated = true;
                }

                // Add display_name if not set
                if (! isset($price['display_name'])) {
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
