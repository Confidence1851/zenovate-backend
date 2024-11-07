<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'name' => 'TripleDefense',
                'subtitle' => 'Enhanced defense system',
                'description' => 'Optimized protection through a triple-component strategy.',
                'price' => 39.99,
            ],
            [
                'name' => 'ShapeUp',
                'subtitle' => 'Fitness and health conditioning',
                'description' => 'A holistic approach to improving physical fitness and overall well-being.',
                'price' => 49.99,
            ],
            [
                'name' => 'Phoslim',
                'subtitle' => 'Metabolic & weight management',
                'description' => 'Designed to support a healthy metabolism and assist in weight control.',
                'price' => 34.99,
            ],
            [
                'name' => 'MethylB12',
                'subtitle' => 'Brain & nerve function',
                'description' => 'Vitamin B12 in its most bioavailable form to support neurological health.',
                'price' => 29.99,
            ],
            [
                'name' => 'NadCreation',
                'subtitle' => 'Boosts cellular energy and repair',
                'description' => 'Critical for energy metabolism and cellular health.',
                'price' => 44.99,
            ],
            [
                'name' => 'BiotinLixer',
                'subtitle' => 'Biotin to strengthen hair & skin',
                'description' => 'A rich blend of biotin to strengthen and beautify hair, skin, and nails.',
                'price' => 24.99,
            ],
            [
                'name' => 'Glutathione',
                'subtitle' => 'Powerful antioxidant for detoxification and immune support',
                'description' => 'Supports detox processes and enhances antioxidant defenses.',
                'price' => 54.99,
            ],
            [
                'name' => 'VitaminD3',
                'subtitle' => 'Essential for bone health and immune function',
                'description' => 'Critical for maintaining bone density and supporting immune system health.',
                'price' => 19.99,
            ],
        ];

        foreach ($data as $product) {
            Product::firstOrCreate([
                'name' => $product['name'],
            ], [
                'subtitle' => $product['subtitle'],
                'description' => $product['description'],
                'price' => $product['price'],
            ]);
        }
    }
}
