<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class OrderSheetPricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pricingData = [
            'Zenfit' => [
                ['values' => ['usd' => 296, 'cad' => 296]],
            ],
            'Zenergy' => [
                ['values' => ['usd' => 276, 'cad' => 276]],
            ],
            'Zenew' => [
                ['values' => ['usd' => 285, 'cad' => 285]],
            ],
            'Zenslim' => [
                ['values' => ['usd' => 315, 'cad' => 315]],
            ],
            'Zenluma' => [
                ['values' => ['usd' => 272, 'cad' => 272]],
            ],
            'ZenCover' => [
                ['values' => ['usd' => 307, 'cad' => 307]],
            ],
            'Zenlean' => [
                ['values' => ['usd' => 282, 'cad' => 282]],
            ],
            'Zenmune' => [
                ['values' => ['usd' => 313, 'cad' => 313]],
            ],
        ];

        foreach ($pricingData as $productName => $prices) {
            $product = Product::where('name', $productName)->first();

            if ($product) {
                $product->order_sheet_price = $prices;
                $product->enabled_for_order_sheet = true;
                $product->save();
                $this->command->info("Updated order sheet pricing for: {$productName}");
            } else {
                $this->command->warn("Product not found: {$productName}");
            }
        }

        $this->command->info('Order sheet pricing update completed!');
    }
}
