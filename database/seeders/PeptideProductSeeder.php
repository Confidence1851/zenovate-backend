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
        $dataRowNumber = 0; // Track actual data rows (excluding header and skipped rows)
        $createdCount = 0;
        $updatedCount = 0;
        $errorCount = 0;
        $errors = [];

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

            // Increment data row number for valid peptide rows
            $dataRowNumber++;

            $name = $this->extractFirstName($suggestedNames);
            $peptide = $this->getValue($row, $indices, 'Peptide');
            $benefits = $this->getValue($row, $indices, 'Benefits');
            $contraindications = $this->getValue($row, $indices, 'Contraindications');
            $penStrength = $this->getValue($row, $indices, 'Pen Strength');
            $oneMonthDose = $this->getValue($row, $indices, '1-Month Dose');
            $threeMonthsDose = $this->getValue($row, $indices, '3-Months Dose');
            $reference = $this->getValue($row, $indices, 'Reference');

            // Pricing data - Use column H: Pricing for Individual Clients (CAD)
            $pricingIndividualCad = $this->cleanPrice($this->getValue($row, $indices, 'Pricing for Individual Clients (CAD)'));
            $pricingClinicUsd = $this->cleanPrice($this->getValue($row, $indices, 'Pricing for Clinics (USD)'));
            $pricingClinicCad = $this->cleanPrice($this->getValue($row, $indices, 'Pricing for Clinics (CAD)'));
            $usdPricing = $this->cleanPrice($this->getValue($row, $indices, 'USD(Pricing)'));
            $cadPricing = $this->cleanPrice($this->getValue($row, $indices, 'CAD(Pricing)'));

            // Build description from CSV data
            $descriptionParts = [];

            if ($peptide) {
                $descriptionParts[] = "Peptide: " . $this->cleanText($peptide);
            }

            if ($penStrength) {
                $descriptionParts[] = "Pen Strength: " . $this->cleanText($penStrength);
            }

            if ($benefits) {
                $descriptionParts[] = "Potential Benefits: " . $this->cleanText($benefits);
            }

            if ($oneMonthDose) {
                $descriptionParts[] = "Mega Research Dose: " . $this->cleanText($oneMonthDose);
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

            // Get detailed description for active peptides (includes CSV data)
            $detailedDescription = $this->getDetailedDescription($name, $peptide, $penStrength, $benefits, $threeMonthsDose, $oneMonthDose, $contraindications, $reference);

            // Use detailed description if available, otherwise use the built description from CSV
            $plainDescription = $detailedDescription ?: (!empty($descriptionParts) ? implode("\n\n", $descriptionParts) : null);

            // Convert to HTML format
            $description = $plainDescription ? $this->convertToHtml($plainDescription) : null;

            // Build subtitle from peptide name
            $subtitle = $peptide ?: null;

            // Build price array
            // For peptides, use ONLY column H: Pricing for Individual Clients (CAD)
            // Simple pricing (no frequency/unit) - just one price
            $price = [];

            // Only use individual client pricing from column H (CAD only)
            if ($pricingIndividualCad) {
                $price[] = [
                    "values" => [
                        "cad" => (float)$pricingIndividualCad
                    ]
                ];
            }

            // Determine status: Only these 8 specific peptides should be active
            $activePeptideNames = [
                'Zenfit',
                'Zenergy',
                'Zenew',
                'Zenslim',
                'Zenluma',
                'ZenCover',
                'Zenlean',
                'Zenmune'
            ];
            $isActive = in_array($name, $activePeptideNames);

            // Prepare product data for update
            // Note: We don't set airtable_id to null to preserve existing values in production
            $productData = [
                "name" => $name,
                "subtitle" => $subtitle,
                "description" => $description ?: null,
                "benefits" => $benefits ?: null,
                "status" => $isActive ? StatusConstants::ACTIVE : StatusConstants::INACTIVE,
                "nav_description" => null,
                "key_ingredients" => $peptide ?: null,
                "price" => !empty($price) ? $price : null,
                "checkout_type" => "direct",
                "requires_patient_clinic_selection" => true,
            ];

            try {
                // Use updateOrCreate for idempotency (safe to run multiple times)
                // This ensures the product is created if it doesn't exist, or updated if it does
                $product = Product::updateOrCreate(
                    ['name' => $name], // Search by name
                    $productData       // Update/create with this data
                );

                // Generate/update slug (this handles both new and existing products)
                $product->slug = ProductService::generateSlug($product);
                $product->save();

                // Track counts
                if ($product->wasRecentlyCreated) {
                    $createdCount++;
                    $this->command->info("Created: {$name}");
                } else {
                    $updatedCount++;
                    $this->command->info("Updated: {$name} (Status: " . $product->status . ")");
                }
            } catch (\Exception $e) {
                $errorCount++;
                $errorMessage = "Error processing {$name}: " . $e->getMessage();
                $errors[] = $errorMessage;
                $this->command->error($errorMessage);
                // Continue processing other products even if one fails
            }
        }

        fclose($file);

        // Summary output
        $this->command->info("\n" . str_repeat("=", 50));
        $this->command->info("Seeder completed!");
        $this->command->info(str_repeat("=", 50));
        $this->command->info("Total rows processed: {$rowCount}");
        $this->command->info("Products created: {$createdCount}");
        $this->command->info("Products updated: {$updatedCount}");

        if ($errorCount > 0) {
            $this->command->warn("Errors encountered: {$errorCount}");
            foreach ($errors as $error) {
                $this->command->error("  - {$error}");
            }
        } else {
            $this->command->info("✓ No errors encountered");
        }

        // Verify active peptides count
        $activePeptides = Product::where('status', StatusConstants::ACTIVE)
            ->whereIn('name', ['Zenfit', 'Zenergy', 'Zenew', 'Zenslim', 'Zenluma', 'ZenCover', 'Zenlean', 'Zenmune'])
            ->count();

        $this->command->info("\nActive peptides: {$activePeptides} (expected: 8)");
        if ($activePeptides === 8) {
            $this->command->info("✓ Active peptides count is correct");
        } else {
            $this->command->warn("⚠ Active peptides count mismatch! Expected 8, found {$activePeptides}");
        }
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

    /**
     * Get detailed description for active peptides (includes CSV data)
     */
    private function getDetailedDescription(string $name, ?string $peptide, ?string $penStrength, ?string $benefits, ?string $researchDose, ?string $oneMonthDose, ?string $contraindications, ?string $reference): ?string
    {
        $descriptions = [
            'Zenfit' => [
                'category' => 'Sterile Self-Injection Research Pen',
                'peptide' => $peptide ?: 'Ipamorelin/CJC-1295',
                'strength' => $penStrength ?: '5 mg/mL · 3 mL pen (15 mg total)',
                'indications' => 'Recovery, GH support, improved metabolism',
                'research_dose' => $researchDose ?: '60–75 mcg per day, 5 days/week',
                'storage' => 'Keep refrigerated 2–8°C; protect from light.',
            ],
            'Zenergy' => [
                'category' => 'Sterile Self-Injection Research Pen',
                'peptide' => $peptide ?: 'MOTS-c',
                'strength' => $penStrength ?: '5 mg/mL · 3 mL pen (15 mg total)',
                'indications' => 'Improved metabolism, Mitochondrial health',
                'research_dose' => $researchDose ?: '0.3 mg per week OR 0.15 mg twice weekly',
                'storage' => 'Keep refrigerated 2–8°C; protect from light.',
            ],
            'Zenew' => [
                'category' => 'Sterile Self-Injection Research Pen',
                'peptide' => $peptide ?: 'NAD+',
                'strength' => $penStrength ?: '100 mg/mL · 3 mL pen (300 mg total)',
                'indications' => 'Cellular repair, energy metabolism, neuroprotection',
                'research_dose' => $researchDose ?: '6 mg once weekly OR 3 mg twice weekly',
                'storage' => 'Keep refrigerated 2–8°C; protect from light.',
            ],
            'Zenslim' => [
                'category' => 'Sterile Self-Injection Research Pen',
                'peptide' => $peptide ?: 'Retatrutide',
                'strength' => $penStrength ?: '10 mg/mL · 3 mL pen (30 mg total)',
                'indications' => 'Appetite control, weight reduction, metabolic enhancement',
                'research_dose' => $researchDose ?: '0.6 mg once weekly OR 0.3 mg twice weekly',
                'storage' => 'Refrigerate 2–8°C; protect from light.',
            ],
            'Zenluma' => [
                'category' => 'Sterile Self-Injection Research Pen',
                'peptide' => $peptide ?: 'GHK-Cu',
                'strength' => $penStrength ?: '10 mg/mL · 3 mL pen (30 mg total)',
                'indications' => 'Wound healing, anti-aging, collagen support',
                'research_dose' => $researchDose ?: '80–100 mcg per day OR 200–250 mcg per day, 3–4 days/week',
                'storage' => 'Keep refrigerated 2–8°C; protect from light.',
            ],
            'ZenCover' => [
                'category' => 'Sterile Self-Injection Research Pen',
                'peptide' => $peptide ?: 'TB-500 + BPC-157',
                'strength' => $penStrength ?: '5 mg/mL · 3 mL pen (15 mg total)',
                'indications' => 'Tissue healing, joint/tendon recovery',
                'research_dose' => $researchDose ?: '250 mcg per day, 2–3 days/week',
                'storage' => 'Keep refrigerated 2–8°C; protect from light.',
            ],
            'Zenlean' => [
                'category' => 'Sterile Self-Injection Research Pen',
                'peptide' => $peptide ?: 'Tesamorelin',
                'strength' => $penStrength ?: '5 mg/mL · 3 mL pen (15 mg total)',
                'indications' => 'Reduction of visceral abdominal fat',
                'research_dose' => $researchDose ?: '40–50 mcg/day',
                'storage' => 'Keep refrigerated 2–8°C; protect from light.',
            ],
            'Zenmune' => [
                'category' => 'Sterile Self-Injection Research Pen',
                'peptide' => $peptide ?: 'Thymosin Alpha-1',
                'strength' => $penStrength ?: '10 mg/mL · 3 mL pen (30 mg total)',
                'indications' => 'Immune modulation, immune balance',
                'research_dose' => $researchDose ?: '1.25 mg × 2 weekly',
                'storage' => 'Keep refrigerated 2–8°C; protect from light.',
            ],
        ];

        if (!isset($descriptions[$name])) {
            return null;
        }

        $info = $descriptions[$name];

        // Build description with CSV data
        $description = "Category: {$info['category']}\n\n";
        $description .= "{$name} ({$info['peptide']})\n\n";
        $description .= "Each pen contains: {$info['strength']}\n\n";

        // Add benefits from CSV if available
        if ($benefits) {
            $description .= "Potential Benefits: {$benefits}\n\n";
        }

        $description .= "Future Possible Indications: {$info['indications']}\n\n";
        $description .= "Research Dose: {$info['research_dose']}\n\n";

        // Add Mega Research Dose from CSV if available
        if ($oneMonthDose) {
            $description .= "Mega Research Dose: {$oneMonthDose}\n\n";
        }

        // Add 3-Months Dose from CSV if available
        if ($researchDose && $researchDose !== $info['research_dose']) {
            $description .= "3-Months Dose: {$researchDose}\n\n";
        }

        $description .= "Storage: {$info['storage']}\n\n";

        // Add contraindications from CSV if available
        if ($contraindications) {
            $description .= "Contraindications: {$contraindications}\n\n";
        }

        // Add reference from CSV if available
        if ($reference) {
            $description .= "Reference: {$reference}\n\n";
        }

        $description .= "⚠️Sterile - For Research Use Only";

        return $description;
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
            return '<a href="' . $escapedUrl . '" target="_blank" rel="noopener noreferrer">' . $escapedUrl . '</a>';
        }, $text);
    }
}
