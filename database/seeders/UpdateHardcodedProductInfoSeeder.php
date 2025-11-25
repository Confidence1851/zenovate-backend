<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateHardcodedProductInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productsData = [
            'Energia' => [
                'subtitle' => 'B12 Energy & Nerve Support',
                'description' => '<div><strong>Description:</strong> Energia is a high-potency injectable methylcobalamin solution (10mg/mL) designed to support energy production, neurological health, and optimal B12 levels.</div>',
                'benefits' => "Boosts natural energy production and vitality\nSupports neurological health and function\nEnhances cognitive performance and mental clarity\nHelps maintain healthy B12 levels for vegetarians/vegans",
                'key_ingredients' => 'Methylcobalamin (B12)',
            ],
            'Gloria' => [
                'subtitle' => 'Antioxidant & Skin Brightening Solution',
                'description' => '<div><strong>Description:</strong> Gloria is a premium injectable solution containing 200mg/ml of pharmaceutical-grade glutathione designed to support antioxidant defense, skin health, and cellular detoxification.</div>',
                'benefits' => "Powerful antioxidant protection against free radicals\nSupports natural detoxification and liver function\nPromotes skin brightening and even tone\nEnhances cellular health and anti-aging benefits",
                'key_ingredients' => 'Glutathione',
            ],
            'Nadiva' => [
                'subtitle' => 'Cellular Regeneration & Anti-Aging',
                'description' => '<div><strong>Description:</strong> NADiva is a premium injectable NAD+ solution designed to support cellular repair, metabolic function, and age-management processes.</div>',
                'benefits' => "Supports DNA repair and cellular regeneration\nEnhances metabolic health and energy production\nPromotes neuroprotection and cognitive function\nAids in biological age management",
                'key_ingredients' => 'NAD+',
            ],
            'Immuna' => [
                'subtitle' => 'Immune Defense Complex',
                'description' => '<div><strong>Description:</strong> Immuna is a premium injectable solution combining Glutathione, Ascorbic Acid, and Zinc Sulfate designed to provide comprehensive immune support and antioxidant protection.</div>',
                'benefits' => "Enhances immune system function and defense\nProvides powerful antioxidant protection\nPromotes skin health and radiance\nSupports natural detoxification processes\nImproves cognitive function and mood stability",
                'key_ingredients' => 'Glutathione, Ascorbic Acid, Zinc Sulfate',
            ],
            'Activa' => [
                'subtitle' => 'Wellness & Vitality Solution',
                'description' => '<div><strong>Description:</strong> Activa is an injectable vitamin and nutrient solution containing B-vitamins (B1, B6, B12), L-Carnitine, and essential nutrients designed to support your body\'s natural processes.</div>',
                'benefits' => "Supports natural energy production and metabolism\nEnhances physical endurance and recovery\nPromotes mental clarity and focus\nSupports overall metabolic wellness",
                'key_ingredients' => 'B-vitamins (B1, B6, B12), L-Carnitine',
            ],
            'EpiPen' => [
                'subtitle' => 'Epinephrine Auto-Injector',
                'description' => '<div><strong>Description:</strong> The EpiPen® auto-injector is a pre-filled, single-use device designed to deliver a fast, accurate dose of epinephrine during a severe allergic reaction, also known as anaphylaxis. Its compact, portable design makes it easy to carry in a purse, backpack, or pocket, ensuring it\'s always within reach. This design makes it ideal for situations where every second matters.</div>',
                'benefits' => "Active Ingredient: Epinephrine\nIndication: Emergency treatment of severe allergic reactions caused by insect stings or bites, foods, medications or other allergens\nOnset of Action: Works within minutes to help open airways and improve breathing\nDevice Design: Simple, one-handed activation with audible click confirmation\nSupplied As: Single-use, preloaded auto-injector (0.3 mg for adults, 0.15 mg for children)",
                'key_ingredients' => 'Epinephrine',
            ],
        ];

        $updatedCount = 0;
        $notFoundCount = 0;

        foreach ($productsData as $productName => $data) {
            $product = Product::where('name', $productName)->first();

            if ($product) {
                // Only update fields that are provided and not empty
                if (!empty($data['subtitle'])) {
                    $product->subtitle = $data['subtitle'];
                }
                if (!empty($data['description'])) {
                    $product->description = $data['description'];
                }
                if (!empty($data['benefits'])) {
                    $product->benefits = $data['benefits'];
                }
                if (!empty($data['key_ingredients'])) {
                    $product->key_ingredients = $data['key_ingredients'];
                }

                // Set checkout configuration for EpiPen (direct checkout, no patient/clinic selection)
                if ($productName === 'EpiPen') {
                    $product->checkout_type = 'direct';
                    $product->requires_patient_clinic_selection = false;
                }

                $product->save();
                $updatedCount++;
                $this->command->info("Updated: {$productName}");
            } else {
                $notFoundCount++;
                $this->command->warn("Product not found: {$productName}");
            }
        }

        $this->command->info("\nSeeder completed!");
        $this->command->info("Products updated: {$updatedCount}");
        if ($notFoundCount > 0) {
            $this->command->info("Products not found: {$notFoundCount}");
        }
    }
}
