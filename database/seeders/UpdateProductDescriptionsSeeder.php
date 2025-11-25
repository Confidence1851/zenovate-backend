<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateProductDescriptionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::whereNotNull('description')
            ->where('description', '!=', '')
            ->get();

        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($products as $product) {
            $originalDescription = $product->description;
            
            // Skip if description already contains HTML tags
            if (strip_tags($originalDescription) !== $originalDescription) {
                $this->command->info("Skipping {$product->name} - already contains HTML");
                $skippedCount++;
                continue;
            }

            $htmlDescription = $this->convertToHtml($originalDescription);
            
            if ($htmlDescription !== $originalDescription) {
                $product->description = $htmlDescription;
                $product->save();
                $updatedCount++;
                $this->command->info("Updated: {$product->name}");
            } else {
                $skippedCount++;
            }
        }

        $this->command->info("\nSeeder completed!");
        $this->command->info("Total products processed: " . $products->count());
        $this->command->info("Products updated: {$updatedCount}");
        $this->command->info("Products skipped: {$skippedCount}");
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
                // Section without a label
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

