<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdatePeptidePricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pricingData = [
            'Zenfit' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['cad' => 210.16, 'usd' => 210.16]],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['cad' => 567.44, 'usd' => 567.44]],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['cad' => 1021.37, 'usd' => 1021.37]],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['cad' => 1366.56, 'usd' => 1366.56]],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['cad' => 1822.09, 'usd' => 1822.09]],
            ],
            'Zenergy' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['cad' => 195.96, 'usd' => 195.96]],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['cad' => 529.09, 'usd' => 529.09]],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['cad' => 1058.19, 'usd' => 1058.19]],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['cad' => 1499.09, 'usd' => 1499.09]],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['cad' => 1998.79, 'usd' => 1998.79]],
            ],
            'Zenew' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['cad' => 202.35, 'usd' => 202.35]],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['cad' => 546.35, 'usd' => 546.35]],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['cad' => 1092.68, 'usd' => 1092.68]],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['cad' => 1547.98, 'usd' => 1547.98]],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['cad' => 2063.97, 'usd' => 2063.97]],
            ],
            'Zenslim' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['cad' => 223.65, 'usd' => 223.65]],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['cad' => 603.85, 'usd' => 603.85]],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['cad' => 1207.70, 'usd' => 1207.70]],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['cad' => 1710.92, 'usd' => 1710.92]],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['cad' => 2281.23, 'usd' => 2281.23]],
            ],
            'Zenluma' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['cad' => 193.12, 'usd' => 193.12]],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['cad' => 521.42, 'usd' => 521.42]],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['cad' => 1042.84, 'usd' => 1042.84]],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['cad' => 1477.37, 'usd' => 1477.37]],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['cad' => 1969.82, 'usd' => 1969.82]],
            ],
            'ZenCover' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['cad' => 217.97, 'usd' => 217.97]],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['cad' => 588.51, 'usd' => 588.51]],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['cad' => 1177.03, 'usd' => 1177.03]],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['cad' => 1961.73, 'usd' => 1961.73]],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['cad' => 2223.29, 'usd' => 2223.29]],
            ],
            'Zenlean' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['cad' => 200.22, 'usd' => 200.22]],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['cad' => 540.60, 'usd' => 540.60]],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['cad' => 1081.18, 'usd' => 1081.18]],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['cad' => 1531.68, 'usd' => 1531.68]],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['cad' => 2042.24, 'usd' => 2042.24]],
            ],
            'Zenmune' => [
                ['frequency' => 1, 'unit' => 'month', 'values' => ['cad' => 222.23, 'usd' => 222.23]],
                ['frequency' => 3, 'unit' => 'month', 'values' => ['cad' => 599.99, 'usd' => 599.99]],
                ['frequency' => 6, 'unit' => 'month', 'values' => ['cad' => 1199.99, 'usd' => 1199.99]],
                ['frequency' => 9, 'unit' => 'month', 'values' => ['cad' => 1700.06, 'usd' => 1700.06]],
                ['frequency' => 12, 'unit' => 'month', 'values' => ['cad' => 2266.75, 'usd' => 2266.75]],
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

        $this->command->info('Peptide pricing update completed!');
    }
}
