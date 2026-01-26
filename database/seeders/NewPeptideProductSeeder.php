<?php

namespace Database\Seeders;

use App\Helpers\StatusConstants;
use App\Models\Product;
use App\Services\General\ProductService;
use Illuminate\Database\Seeder;

class NewPeptideProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $newProducts = [
            [
                'name' => 'ZenFlow',
                'code' => 'ZEN09',
                'subtitle' => 'VIP + MOTS-c',
                'description' => $this->buildDescription('ZenFlow'),
                'benefits' => 'Mitochondrial efficiency, energy metabolism, neurovascular signaling, immune modulation, cellular stress resilience',
                'potency' => '300 units per pen',
                'key_ingredients' => 'VIP + MOTS-c',
                'image_path' => 'products/zenflow.png',
                'category_id' => 1,
                'price' => [
                    [
                        'unit' => 'month',
                        'values' => ['usd' => 185, 'cad' => 185],
                        'frequency' => 1,
                        'display_name' => '1 Month',
                    ],
                    [
                        'pens' => 1,
                        'unit' => 'month',
                        'values' => ['usd' => 555, 'cad' => 555],
                        'frequency' => 3,
                        'display_name' => '3 Months (1 Pen)',
                    ],
                ],
                'order_sheet_price' => [
                    ['values' => ['usd' => 292, 'cad' => 292]],
                ],
            ],
            [
                'name' => 'Zenklow',
                'code' => 'ZEN10',
                'subtitle' => 'BPC157 + GHK-CU + TB500 + KPV',
                'description' => $this->buildDescription('Zenklow'),
                'benefits' => 'Cellular repair, soft tissue recovery signaling, extracellular matrix remodeling, inflammation modulation, dermal/connective support pathways',
                'potency' => '300 units per pen',
                'key_ingredients' => 'BPC157 + GHK-CU + TB500 + KPV',
                'image_path' => 'products/zenklow.png',
                'category_id' => 1,
                'price' => [
                    [
                        'unit' => 'month',
                        'values' => ['usd' => 212, 'cad' => 212],
                        'frequency' => 1,
                        'display_name' => '1 Month',
                    ],
                    [
                        'pens' => 1,
                        'unit' => 'month',
                        'values' => ['usd' => 636, 'cad' => 636],
                        'frequency' => 3,
                        'display_name' => '3 Months (1 Pen)',
                    ],
                ],
                'order_sheet_price' => [
                    ['values' => ['usd' => 323, 'cad' => 323]],
                ],
            ],
        ];

        foreach ($newProducts as $productData) {
            try {
                $product = Product::updateOrCreate(
                    ['name' => $productData['name']],
                    [
                        'code' => $productData['code'],
                        'subtitle' => $productData['subtitle'],
                        'description' => $productData['description'],
                        'benefits' => $productData['benefits'],
                        'potency' => $productData['potency'],
                        'key_ingredients' => $productData['key_ingredients'],
                        'image_path' => $productData['image_path'],
                        'category_id' => $productData['category_id'],
                        'status' => StatusConstants::ACTIVE,
                        'checkout_type' => 'direct',
                        'requires_patient_clinic_selection' => true,
                        'price' => $productData['price'],
                        'order_sheet_price' => $productData['order_sheet_price'],
                        'enabled_for_order_sheet' => true,
                    ]
                );

                // Generate/update slug
                $product->slug = ProductService::generateSlug($product);
                $product->save();

                if ($product->wasRecentlyCreated) {
                    $this->command->info("Created: {$productData['name']}");
                } else {
                    $this->command->info("Updated: {$productData['name']}");
                }
            } catch (\Exception $e) {
                $this->command->error("Error processing {$productData['name']}: ".$e->getMessage());
            }
        }

        $this->command->info('✓ New peptide products seeded successfully');
    }

    /**
     * Build HTML description for new products
     */
    private function buildDescription(string $name): string
    {
        $descriptions = [
            'ZenFlow' => 'Category: Sterile Self-Injection Research Pen

ZenFlow (VIP + MOTS-c)

One pen: 300 units

Each unit (1 click): 0.1mg

Potential Benefits: ZenFlow combines MOTS-c, a mitochondrial derived peptide studied for cellular energy regulation, with VIP (Vasoactive Intestinal Peptide), a neuropeptide investigated in research environments for its role in vascular, immune, and cellular signaling pathways. This pairing is explored in experimental settings to evaluate mitochondrial neurovascular signaling crosstalk under controlled research conditions. Research remains preliminary, and no clinical benefits have been established.

Future Possible Indications: Mitochondrial efficiency, energy metabolism, neurovascular signaling, immune modulation, cellular stress resilience.

Research Dosing Schedule: Every other day
Lower dose: 6 units (6 clicks) → pen lasts ~100 days (~3.3 months)
Regular dose: 12 units (12 clicks) → pen lasts ~50 days (~7 weeks)
Higher dose: 18 units (18 clicks) → pen lasts ~33 days (~4–5 weeks)

Storage: Keep refrigerated 2–8°C; protect from light.

Contraindications: Active cancer, pregnancy/breastfeeding, uncontrolled cardiovascular disease, severe autonomic dysfunction, or hypersensitivity to peptide compounds.

Reference: https://pubmed.ncbi.nlm.nih.gov/25738459/

⚠️Sterile - For Research Use Only',
            'Zenklow' => "Category: Sterile Self-Injection Research Pen

Zenklow (BPC157 10mg/3ml, GHK-CU 50mg/3ml, TB500 10mg/3ml, KPV 10mg/3ml)

One pen: 300 units

Potential Benefits: Zenklow is a multi-peptide research formulation designed for experimental evaluation of tissue repair, inflammatory modulation, and dermal-connective signaling pathways. The blend combines four investigational peptides frequently studied in recovery focused research models. Research remains preliminary, and no clinical benefits have been established.

Future Possible Indications: Cellular repair, soft tissue recovery signaling, extracellular matrix remodeling, inflammation modulation, dermal/connective support pathways.

Research Dosing Schedule: Every other day
Lower dose: 6 units (6 clicks) → pen lasts ~100 days (~3.3 months)
Regular dose: 12 units (12 clicks) → pen lasts ~50 days (~7 weeks)
Higher dose: 18 units (18 clicks) → pen lasts ~33 days (~4–5 weeks)

Storage: Keep refrigerated 2–8°C; protect from light.

Contraindications: Active cancer, pregnancy/breastfeeding, Wilson's disease, severe liver disease, uncontrolled autoimmune disorders, or hypersensitivity to peptides or copper complexes.

Reference: https://pubmed.ncbi.nlm.nih.gov/29986520/

⚠️Sterile - For Research Use Only",
        ];

        $plainDescription = $descriptions[$name] ?? null;

        return $plainDescription ? $this->convertToHtml($plainDescription) : null;
    }

    /**
     * Convert plain text description to HTML format
     */
    private function convertToHtml(string $description): string
    {
        // Split by double newlines to get sections
        $sections = preg_split('/\n\s*\n/', trim($description));

        $htmlSections = [];

        foreach ($sections as $section) {
            $section = trim($section);
            if (empty($section)) {
                continue;
            }

            // Check if section starts with a label (e.g., "Peptide:", "Benefits:")
            $labelPattern = '/^([^:]+):\s*(.+)$/';

            if (preg_match($labelPattern, $section, $matches)) {
                $label = trim($matches[1]);
                $content = trim($matches[2]);

                // Convert URLs in content to clickable links
                $content = $this->convertUrlsToLinks($content);

                // Convert single newlines within content to <br> tags
                $content = nl2br($content, false);

                $htmlSections[] = "<div><strong>{$label}:</strong> {$content}</div>";
            } else {
                // Section without a label (like the product name line or disclaimer)
                $content = $this->convertUrlsToLinks($section);
                $content = nl2br($content, false);
                $htmlSections[] = "<div>{$content}</div>";
            }
        }

        return implode('', $htmlSections);
    }

    /**
     * Convert URLs in text to clickable links
     */
    private function convertUrlsToLinks(string $text): string
    {
        // Pattern to match URLs (more comprehensive)
        $urlPattern = '/(https?:\/\/[^\s<>"\'\)]+)/i';

        return preg_replace_callback($urlPattern, function ($matches) {
            $url = trim($matches[1]);
            // Remove trailing punctuation that might not be part of the URL
            $url = rtrim($url, '.,;:!?)');
            $escapedUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

            return '<a href="'.$escapedUrl.'" target="_blank" rel="noopener noreferrer">'.$escapedUrl.'</a>';
        }, $text);
    }
}
