<?php

namespace App\Notifications\OrderSheet\Admin;

use App\Models\Payment;
use App\Services\OrderSheet\OrderSummaryPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment)
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        try {
            $adminEmail = method_exists($notifiable, 'getEmailForNotifications')
                ? $notifiable->getEmailForNotifications()
                : (property_exists($notifiable, 'email') ? $notifiable->email : 'admin@zenovate.health');

            Log::info('Admin OrderSheet Notification: Starting', [
                'payment_id' => $this->payment->id,
                'payment_reference' => $this->payment->reference,
                'admin_email' => $adminEmail,
            ]);

            $formSession = $this->payment->formSession;
            if (!$formSession) {
                Log::warning('Admin OrderSheet Notification: No form session found', [
                    'payment_id' => $this->payment->id,
                ]);
            }

            $metadata = $formSession ? ($formSession->metadata['raw'] ?? []) : [];
            $customerName = trim(($metadata['firstName'] ?? '') . ' ' . ($metadata['lastName'] ?? ''));

            $message = (new MailMessage)
                ->subject('New Order Sheet Payment #' . $this->payment->reference)
                ->greeting('Hi Admin,');

            $message->line('A new order sheet payment has been received and confirmed.');

            // Order Details Section
            $message->line('**Order Details:**');
            $message->line('Order Reference: ' . $this->payment->reference);
            $message->line('Date: ' . $this->payment->created_at->format('F j, Y \a\t g:i A'));

            // Customer Information
            $message->line('**Customer Information:**');
            $message->line('Name: ' . ($customerName ?: 'N/A'));
            $message->line('Email: ' . ($metadata['email'] ?? 'N/A'));
            $message->line('Phone: ' . ($metadata['phoneNumber'] ?? $this->payment->phone ?? 'N/A'));
            if (!empty($metadata['account_number'])) {
                $message->line('Account Number: ' . $metadata['account_number']);
            }
            if (!empty($metadata['location'])) {
                $message->line('Location: ' . $metadata['location']);
            }
            if (!empty($metadata['shipping_address'])) {
                $message->line('Shipping Address: ' . $metadata['shipping_address']);
            }

            // Product Details
            try {
                $paymentProducts = $this->payment->paymentProducts()->with('product')->get();
                Log::info('Admin OrderSheet Notification: Loaded payment products', [
                    'payment_id' => $this->payment->id,
                    'product_count' => $paymentProducts->count(),
                ]);

                if ($paymentProducts->isNotEmpty()) {
                    $message->line('**Order Items:**');
                    foreach ($paymentProducts as $paymentProduct) {
                        $product = $paymentProduct->product;
                        if (!$product) {
                            Log::warning('Admin OrderSheet Notification: Product not found for payment product', [
                                'payment_id' => $this->payment->id,
                                'payment_product_id' => $paymentProduct->id,
                            ]);
                            continue;
                        }

                        $priceData = is_array($paymentProduct->price) ? $paymentProduct->price : ['value' => 0, 'currency' => $this->payment->currency];
                        $unitPrice = number_format($priceData['value'] ?? 0, 2);
                        $quantity = $paymentProduct->quantity ?? 1;
                        $lineTotal = ($priceData['value'] ?? 0) * $quantity;

                        $itemLine = sprintf(
                            '- %s (Qty: %d) × %s %s = %s %s',
                            $product->name ?? 'Unknown Product',
                            $quantity,
                            strtoupper($priceData['currency'] ?? $this->payment->currency),
                            $unitPrice,
                            strtoupper($priceData['currency'] ?? $this->payment->currency),
                            number_format($lineTotal, 2)
                        );
                        $message->line($itemLine);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Admin OrderSheet Notification: Error loading products', [
                    'payment_id' => $this->payment->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            // Pricing Summary
            $message->line('**Pricing Summary:**');
            $message->line('Subtotal: ' . $this->payment->currency . ' ' . number_format($this->payment->sub_total ?? 0, 2));
            if (!empty($this->payment->discount_code) && ($this->payment->discount_amount ?? 0) > 0) {
                $message->line('Discount (' . $this->payment->discount_code . '): -' . $this->payment->currency . ' ' . number_format($this->payment->discount_amount, 2));
            }
            if (($this->payment->tax_amount ?? 0) > 0) {
                $message->line('Tax: ' . $this->payment->currency . ' ' . number_format($this->payment->tax_amount, 2));
            }
            if (($this->payment->shipping_fee ?? 0) > 0) {
                $message->line('Shipping: ' . $this->payment->currency . ' ' . number_format($this->payment->shipping_fee, 2));
            } else {
                $message->line('Shipping: FREE');
            }
            $message->line('**Total: ' . $this->payment->currency . ' ' . number_format($this->payment->total, 2) . '**');

            // Add link to view form session in dashboard
            try {
                $formSession = $this->payment->formSession;
                if ($formSession) {
                    $formSessionRoute = route('dashboard.form-sessions.show', $formSession->id);
                    $message->action('View Full Order Details', $formSessionRoute);
                    Log::info('Admin OrderSheet Notification: Route generated successfully', [
                        'payment_id' => $this->payment->id,
                        'form_session_id' => $formSession->id,
                        'route' => $formSessionRoute,
                    ]);
                } else {
                    Log::warning('Admin OrderSheet Notification: No form session found for route', [
                        'payment_id' => $this->payment->id,
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Admin OrderSheet Notification: Failed to generate route', [
                    'payment_id' => $this->payment->id,
                    'error' => $e->getMessage(),
                ]);
                // Route doesn't exist, skip action button
            }

            // Attach PDF order summary
            try {
                $pdfService = new OrderSummaryPdfService();
                $pdfContent = $pdfService->generate($this->payment);
                $message->attachData($pdfContent, 'order-summary-' . $this->payment->reference . '.pdf', [
                    'mime' => 'application/pdf',
                ]);
                $message->line('**Please find the detailed order summary attached as a PDF.**');
                Log::info('Admin OrderSheet Notification: PDF attached successfully', [
                    'payment_id' => $this->payment->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Admin OrderSheet Notification: Failed to generate PDF', [
                    'payment_id' => $this->payment->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Continue without PDF attachment if generation fails
            }

            $adminEmail = method_exists($notifiable, 'getEmailForNotifications')
                ? $notifiable->getEmailForNotifications()
                : (property_exists($notifiable, 'email') ? $notifiable->email : 'admin@zenovate.health');

            Log::info('Admin OrderSheet Notification: Completed successfully', [
                'payment_id' => $this->payment->id,
                'admin_email' => $adminEmail,
            ]);

            return $message;
        } catch (\Exception $e) {
            Log::error('Admin OrderSheet Notification: Failed in toMail method', [
                'payment_id' => $this->payment->id,
                'admin_email' => $notifiable->email ?? 'unknown',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw to let Laravel handle it
        }
    }

    public function toArray(object $notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'reference' => $this->payment->reference,
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Admin OrderSheet Notification: Job failed', [
            'payment_id' => $this->payment->id,
            'payment_reference' => $this->payment->reference ?? 'unknown',
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
