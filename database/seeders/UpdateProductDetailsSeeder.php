<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateProductDetailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Product details from content.txt
        $productDetails = [
            'Zenfit' => [
                'peptide' => 'Ipamorelin/CJC1295',
                'each_unit' => '0.05 mg total peptide (≈0.0167 mg CJC-1295 + 0.0333 mg Ipamorelin)',
                'dosing_schedule' => 'Every other day',
                'dosing_details' => [
                    'Lower dose: 6 units (6 clicks) → pen lasts ~100 days (~3.3 months)',
                    'Regular dose: 12 units (12 clicks) → pen lasts ~50 days (~7 weeks)',
                    'Higher dose: 18 units (18 clicks) → pen lasts ~33 days (~4–5 weeks)',
                ],
            ],
            'Zenergy' => [
                'peptide' => 'MOTS-c',
                'each_unit' => '0.05 mg MOTS-c',
                'dosing_schedule' => 'Every other day',
                'dosing_details' => [
                    'Lower dose: 6 units (6 clicks) → pen lasts ~100 days',
                    'Regular dose: 12 units (12 clicks) → pen lasts ~50 days',
                    'Higher dose: 18 units (18 clicks) → pen lasts ~33 days',
                ],
            ],
            'Zenslim' => [
                'peptide' => 'Retatrutide',
                'each_unit' => '0.1 mg Retatrutide',
                'dosing_schedule' => 'Once weekly',
                'dosing_details' => [
                    'Lower dose: 6 units (6 clicks) → pen lasts ~100 days',
                    'Regular dose: 12 units (12 clicks) → pen lasts ~50 days',
                    'Higher dose: 18 units (18 clicks) → pen lasts ~33 days',
                ],
            ],
            'Zenluma' => [
                'peptide' => 'GHK-CU',
                'each_unit' => '0.1 mg GHK-Cu',
                'dosing_schedule' => 'Every other day',
                'dosing_details' => [
                    'Lower dose: 6 units (6 clicks) → pen lasts ~100 days',
                    'Regular dose: 12 units (12 clicks) → pen lasts ~50 days',
                    'Higher dose: 18 units (18 clicks) → pen lasts ~33 days',
                ],
            ],
            'ZenCover' => [
                'peptide' => 'TB500 + BPC157',
                'each_unit' => '0.05 mg total blend (0.025 mg BPC-157 + 0.025 mg TB-500)',
                'dosing_schedule' => 'Every other day',
                'dosing_details' => [
                    'Lower dose: 6 units (6 clicks) → pen lasts ~100 days',
                    'Regular dose: 12 units (12 clicks) → pen lasts ~50 days',
                    'Higher dose: 18 units (18 clicks) → pen lasts ~33 days',
                ],
            ],
            'Zenlean' => [
                'peptide' => 'Tesamorelin',
                'each_unit' => '0.05 mg Tesamorelin',
                'dosing_schedule' => 'Every other day',
                'dosing_details' => [
                    'Lower dose: 6 units (6 clicks) → pen lasts ~100 days',
                    'Regular dose: 12 units (12 clicks) → pen lasts ~50 days',
                    'Higher dose: 18 units (18 clicks) → pen lasts ~33 days',
                ],
            ],
            'Zenmune' => [
                'peptide' => 'Thymosin Alpha-1',
                'each_unit' => '0.1 mg Thymosin Alpha-1',
                'dosing_schedule' => 'Every other day',
                'dosing_details' => [
                    'Lower dose: 6 units (6 clicks) → pen lasts ~100 days',
                    'Regular dose: 12 units (12 clicks) → pen lasts ~50 days',
                    'Higher dose: 18 units (18 clicks) → pen lasts ~33 days',
                ],
            ],
            'Zenew' => [
                'peptide' => 'NAD+',
                'each_unit' => '1 mg NAD⁺',
                'dosing_schedule' => 'Every other day',
                'dosing_details' => [
                    'Lower dose: 6 units (6 clicks) → pen lasts ~100 days',
                    'Regular dose: 12 units (12 clicks) → pen lasts ~50 days',
                    'Higher dose: 18 units (18 clicks) → pen lasts ~33 days',
                ],
            ],
        ];

        $updatedCount = 0;
        $notFoundCount = 0;

        foreach ($productDetails as $productName => $data) {
            $product = Product::where('name', $productName)->first();

            if (!$product) {
                $notFoundCount++;
                $this->command->warn("Product not found: {$productName}");
                continue;
            }

            // Get current description from database
            $currentDesc = $product->description ?? '';
            
            if (empty($currentDesc)) {
                $this->command->warn("Product {$productName} has no description");
                $notFoundCount++;
                continue;
            }

            // Extract existing sections using string functions (no regex)
            $sections = $this->extractSections($currentDesc);
            
            // Build new sections from content.txt
            $productNameSection = '<div>' . htmlspecialchars($productName, ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($data['peptide'], ENT_QUOTES, 'UTF-8') . ')</div>';
            $newOnePen = '<div><strong>One pen: 300 units</strong></div>';
            $newEachUnit = '<div><strong>Each unit (1 click): ' . htmlspecialchars($data['each_unit'], ENT_QUOTES, 'UTF-8') . '</strong></div>';
            
            $dosingDetailsHtml = '';
            foreach ($data['dosing_details'] as $detail) {
                $dosingDetailsHtml .= '<div>' . htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') . '</div>';
            }
            $newDosingSection = '<div><strong>Research Dosing Schedule: ' . htmlspecialchars($data['dosing_schedule'], ENT_QUOTES, 'UTF-8') . '</strong></div>' . $dosingDetailsHtml;

            // Rebuild description with updated sections
            $updatedDescription = $sections['category'] . 
                $productNameSection . 
                $newOnePen . 
                $newEachUnit . 
                $sections['benefits'] . 
                $sections['indications'] . 
                $newDosingSection . 
                $sections['storage'] . 
                $sections['contraindications'] . 
                $sections['reference'] . 
                $sections['disclaimer'];

            // Set the full updated description
            $product->description = $updatedDescription;
            $product->save();
            $updatedCount++;
            $this->command->info("Updated: {$productName}");
        }

        $this->command->info("\nSeeder completed!");
        $this->command->info("Products updated: {$updatedCount}");
        if ($notFoundCount > 0) {
            $this->command->info("Products not found: {$notFoundCount}");
        }
    }

    /**
     * Extract sections from description using string functions (no regex)
     */
    private function extractSections(string $description): array
    {
        $sections = [];
        
        // Category
        $catStart = strpos($description, '<div><strong>Category:</strong>');
        if ($catStart !== false) {
            $catEnd = strpos($description, '</div>', $catStart);
            $sections['category'] = substr($description, $catStart, $catEnd - $catStart + 6);
        } else {
            $sections['category'] = '';
        }
        
        // Benefits
        $benStart = strpos($description, '<div><strong>Potential Benefits:</strong>');
        if ($benStart !== false) {
            $benEnd = strpos($description, '</div>', $benStart);
            $sections['benefits'] = substr($description, $benStart, $benEnd - $benStart + 6);
        } else {
            $sections['benefits'] = '';
        }
        
        // Indications
        $indStart = strpos($description, '<div><strong>Future Possible Indications:</strong>');
        if ($indStart !== false) {
            $indEnd = strpos($description, '</div>', $indStart);
            $sections['indications'] = substr($description, $indStart, $indEnd - $indStart + 6);
        } else {
            $sections['indications'] = '';
        }
        
        // Storage
        $storStart = strpos($description, '<div><strong>Storage:</strong>');
        if ($storStart !== false) {
            $storEnd = strpos($description, '</div>', $storStart);
            $sections['storage'] = substr($description, $storStart, $storEnd - $storStart + 6);
        } else {
            $sections['storage'] = '';
        }
        
        // Contraindications
        $contraStart = strpos($description, '<div><strong>Contraindications:</strong>');
        if ($contraStart !== false) {
            $contraEnd = strpos($description, '</div>', $contraStart);
            $sections['contraindications'] = substr($description, $contraStart, $contraEnd - $contraStart + 6);
        } else {
            $sections['contraindications'] = '';
        }
        
        // Reference
        $refStart = strpos($description, '<div><strong>Reference:</strong>');
        if ($refStart !== false) {
            $refEnd = strpos($description, '</div>', $refStart);
            $sections['reference'] = substr($description, $refStart, $refEnd - $refStart + 6);
        } else {
            $sections['reference'] = '';
        }
        
        // Disclaimer
        $disStart = strpos($description, '<div>⚠️');
        if ($disStart !== false) {
            $disEnd = strpos($description, '</div>', $disStart);
            $sections['disclaimer'] = substr($description, $disStart, $disEnd - $disStart + 6);
        } else {
            $sections['disclaimer'] = '';
        }
        
        return $sections;
    }
}
