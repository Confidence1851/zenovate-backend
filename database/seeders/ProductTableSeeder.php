<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Services\General\ProductService;
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
                "name" => "Immuna",
                "subtitle" => "Enhanced defense system",
                "description" => "Immuna is formulated with Glutathione, Ascorbic Acid, and Zinc Sulfate to boost immune function, provide antioxidant protection, and support skin health. Research shows that these ingredients aid in detoxification, while studies demonstrate their role in enhancing mood and cognitive function. Elevate your well-being with this powerful blend.",
                "status" => "Active",
                "airtable_id" => null,
                "nav_description" => null,
                "key_ingredients" => null,
                "benefits" => null,
                "price" => [
                    [
                        "frequency" => 12,
                        "unit" => "week",
                        "values" => ["cad" => 41, "usd" => 41]
                    ],
                    [
                        "frequency" => 36,
                        "unit" => "week",
                        "values" => ["cad" => 39, "usd" => 39]
                    ],
                    [
                        "frequency" => 52,
                        "unit" => "week",
                        "values" => ["cad" => 36, "usd" => 36]
                    ]
                ]
            ],
            [
                "name" => "Activa",
                "subtitle" => "Fitness and health conditioning",
                "description" => "Activa is a targeted blend of Vitamin B1, B6, B12, L-carnitine, Choline Chloride, Inositol, and Methionine, designed to support multiple aspects of health and performance. Studies show that these ingredients aid in weight management by enhancing energy metabolism and utilizing fatty acids for fuel. Research demonstrates their ability to boost athletic performance, promote cognitive function, support liver health and detoxification, and aid in nerve signaling and lipid metabolism. Activa is ideal for those seeking improved physical and mental vitality.",
                "status" => "Active",
                "airtable_id" => null,
                "nav_description" => null,
                "key_ingredients" => null,
                "benefits" => null,
                "price" => [
                    [
                        "frequency" => 12,
                        "unit" => "week",
                        "values" => ["cad" => 38, "usd" => 38]
                    ],
                    [
                        "frequency" => 36,
                        "unit" => "week",
                        "values" => ["cad" => 36, "usd" => 36]
                    ],
                    [
                        "frequency" => 52,
                        "unit" => "week",
                        "values" => ["cad" => 33, "usd" => 33]
                    ]
                ]
            ],
            [
                "name" => "Nadiva",
                "subtitle" => "Metabolic & weight management",
                "description" => "NADiva is a cutting-edge formulation featuring Nicotinamide Adenine Dinucleotide (NAD), designed for individuals seeking to support various aspects of health. Research shows that NAD plays a crucial role in DNA repair and promotes genomic stability, while studies demonstrate its neuroprotective effects that may help mitigate neurodegenerative conditions. Additionally, NADiva enhances metabolic health and may be recommended for those looking to slow the effects of aging and support overall cellular health. Experience the benefits of NADiva for a vibrant and healthy life.",
                "status" => "Active",
                "airtable_id" => null,
                "nav_description" => null,
                "key_ingredients" => null,
                "benefits" => null,
                "price" => [
                    [
                        "frequency" => 12,
                        "unit" => "week",
                        "values" => ["cad" => 42, "usd" => 42]
                    ],
                    [
                        "frequency" => 36,
                        "unit" => "week",
                        "values" => ["cad" => 39, "usd" => 39]
                    ],
                    [
                        "frequency" => 52,
                        "unit" => "week",
                        "values" => ["cad" => 35, "usd" => 35]
                    ]
                ]
            ],
            [
                "name" => "Gloria",
                "subtitle" => "Brain & nerve function",
                "description" => "Gloria is formulated with Glutathione, a powerful antioxidant supported by research. Studies show that Glutathione can enhance antioxidant defenses, reduce oxidative stress, and support detoxification, leading to improved liver function. It is also known for its anti-aging effects, helping to even out skin tone and promote a brighter, more youthful appearance. It is ideal for those looking to support their liver health and overall well-being.",
                "status" => "Active",
                "airtable_id" => null,
                "nav_description" => null,
                "key_ingredients" => null,
                "benefits" => null,
                "price" => [
                    [
                        "frequency" => 12,
                        "unit" => "week",
                        "values" => ["cad" => 37, "usd" => 37]
                    ],
                    [
                        "frequency" => 36,
                        "unit" => "week",
                        "values" => ["cad" => 35, "usd" => 35]
                    ],
                    [
                        "frequency" => 52,
                        "unit" => "week",
                        "values" => ["cad" => 31, "usd" => 31]
                    ]
                ]
            ],
            [
                "name" => "Energia",
                "subtitle" => "Boosts cellular energy and repair",
                "description" => "Energia is formulated with Methylcobalamin, a bioactive form of Vitamin B12, indicated for the treatment of Vitamin B12 deficiency. Research shows that Methylcobalamin is effective for addressing conditions such as pernicious anemia, malabsorption syndromes, and dietary deficiencies often seen in vegetarian or vegan diets. Studies demonstrate its role in alleviating neuropathies and cognitive decline associated with B12 deficiency, making Energia an essential support for optimal nerve function and cognitive health.",
                "status" => "Active",
                "airtable_id" => null,
                "nav_description" => null,
                "key_ingredients" => null,
                "benefits" => null,
                "price" => [
                    [
                        "frequency" => 12,
                        "unit" => "week",
                        "values" => ["cad" => 36, "usd" => 36]
                    ],
                    [
                        "frequency" => 36,
                        "unit" => "week",
                        "values" => ["cad" => 34, "usd" => 34]
                    ],
                    [
                        "frequency" => 52,
                        "unit" => "week",
                        "values" => ["cad" => 28, "usd" => 28]
                    ]
                ]
            ]
        ];

        Product::whereNull("price")->delete();


        foreach ($data as $product) {
            $product["slug"] = ProductService::generateSlug(new Product($product));
            Product::firstOrCreate([
                'name' => $product['name'],
            ], $product);
        }
    }
}
