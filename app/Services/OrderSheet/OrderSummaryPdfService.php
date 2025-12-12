<?php

namespace App\Services\OrderSheet;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class OrderSummaryPdfService
{
    /**
     * Generate PDF order summary for a payment
     *
     * @param Payment $payment
     * @return string PDF content as string
     */
    public function generate(Payment $payment): string
    {
        // Load relationships - ensure products are loaded
        $payment->load(['paymentProducts.product', 'formSession']);

        // Reload products if needed to ensure they're available
        foreach ($payment->paymentProducts as $paymentProduct) {
            if (!$paymentProduct->product && $paymentProduct->product_id) {
                $paymentProduct->load('product');
            }
        }

        $formSession = $payment->formSession;
        $metadata = $formSession ? ($formSession->metadata['raw'] ?? []) : [];

        // Get selected products from metadata as fallback
        $selectedProductsMetadata = $metadata['selectedProducts'] ?? [];
        $productsById = [];
        foreach ($selectedProductsMetadata as $selectedProduct) {
            // Handle both 'id' and 'product_id' keys
            $productId = $selectedProduct['id'] ?? $selectedProduct['product_id'] ?? null;
            $productName = $selectedProduct['name'] ?? null;
            if ($productId && $productName) {
                $productsById[$productId] = $productName;
            }
        }

        // Prepare data for PDF
        $data = [
            'payment' => $payment,
            'formSession' => $formSession,
            'metadata' => $metadata,
            'customerName' => trim(($metadata['firstName'] ?? '') . ' ' . ($metadata['lastName'] ?? '')),
            'customerEmail' => $metadata['email'] ?? 'N/A',
            'customerPhone' => $metadata['phoneNumber'] ?? $payment->phone ?? 'N/A',
            'accountNumber' => $metadata['account_number'] ?? null,
            'location' => $metadata['location'] ?? null,
            'shippingAddress' => $metadata['shipping_address'] ?? null,
            'additionalInformation' => $metadata['additional_information'] ?? null,
            'products' => $payment->paymentProducts->map(function ($paymentProduct) use ($payment, $productsById) {
                // Ensure product is loaded
                if (!$paymentProduct->relationLoaded('product')) {
                    $paymentProduct->load('product');
                }

                $product = $paymentProduct->product;

                // Get product name - try multiple methods
                $productName = null;

                // Method 1: From loaded relationship
                if ($product) {
                    $productName = $product->name ?? $product->title ?? null;
                }

                // Method 2: Fresh query by ID (in case relationship wasn't loaded properly)
                if (!$productName && $paymentProduct->product_id) {
                    $product = \App\Models\Product::find($paymentProduct->product_id);
                    if ($product) {
                        $productName = $product->name ?? $product->title ?? null;
                    }
                }

                // Method 3: Try withTrashed in case product was soft-deleted
                if (!$productName && $paymentProduct->product_id) {
                    try {
                        $product = \App\Models\Product::withTrashed()->find($paymentProduct->product_id);
                        if ($product) {
                            $productName = $product->name ?? $product->title ?? null;
                        }
                    } catch (\Exception $e) {
                        // withTrashed might not be available if soft deletes aren't enabled
                    }
                }

                // Method 4: Fallback to metadata if product still not found
                if (!$productName && $paymentProduct->product_id && isset($productsById[$paymentProduct->product_id])) {
                    $productName = $productsById[$paymentProduct->product_id];
                }

                // Final fallback - show product ID
                if (!$productName) {
                    $productName = 'Product #' . ($paymentProduct->product_id ?? 'Unknown');
                    // Log if product is missing
                    Log::warning('PDF Generation: Product not found for PaymentProduct', [
                        'payment_product_id' => $paymentProduct->id,
                        'product_id' => $paymentProduct->product_id,
                        'payment_id' => $payment->id,
                        'payment_reference' => $payment->reference,
                    ]);
                }

                $priceData = is_array($paymentProduct->price) ? $paymentProduct->price : ['value' => 0, 'currency' => $payment->currency];
                $quantity = $paymentProduct->quantity ?? 1;
                $unitPrice = $priceData['value'] ?? 0;
                $lineTotal = $unitPrice * $quantity;

                return [
                    'name' => $productName,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'currency' => $priceData['currency'] ?? $payment->currency,
                ];
            }),
        ];

        // Render HTML view
        $html = View::make('pdf.order-summary', $data)->render();

        // Configure mPDF
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        // Create mPDF instance
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
            'margin_header' => 9,
            'margin_footer' => 9,
            'fontDir' => array_merge($fontDirs, [
                storage_path('fonts'),
            ]),
            'fontdata' => $fontData,
            'default_font' => 'dejavusans',
        ]);

        // Set PDF metadata
        $mpdf->SetTitle('Order Summary - ' . $payment->reference);
        $mpdf->SetAuthor('Zenovate Health');
        $mpdf->SetSubject('Order Summary');
        $mpdf->SetKeywords('Order, Summary, Invoice, Zenovate');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Return PDF as string
        return $mpdf->Output('', 'S');
    }

    /**
     * Generate PDF and save to file
     *
     * @param Payment $payment
     * @param string $filePath
     * @return bool
     */
    public function generateToFile(Payment $payment, string $filePath): bool
    {
        try {
            $pdfContent = $this->generate($payment);
            return file_put_contents($filePath, $pdfContent) !== false;
        } catch (\Exception $e) {
            Log::error('Failed to generate PDF to file', [
                'payment_id' => $payment->id,
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
