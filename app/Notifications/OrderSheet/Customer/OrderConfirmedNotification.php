<?php

namespace App\Notifications\OrderSheet\Customer;

use App\Models\Payment;
use App\Services\OrderSheet\OrderSummaryPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class OrderConfirmedNotification extends Notification implements ShouldQueue
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
        // Reload payment with relationships in case it was serialized for queue
        $this->payment->load('formSession');
        $formSession = $this->payment->formSession;
        $metadata = $formSession ? ($formSession->metadata['raw'] ?? []) : [];
        $customerName = trim(($metadata['firstName'] ?? '') . ' ' . ($metadata['lastName'] ?? ''));

        $message = (new MailMessage)
            ->subject('Order Confirmed #' . $this->payment->reference)
            ->greeting('Hi ' . ($customerName ?: 'there') . ',');

        // Check if this is an order sheet (direct booking)
        $isOrderSheet = $this->payment->order_type === 'order_sheet'
            || ($formSession && $formSession->booking_type === 'direct');

        if ($isOrderSheet) {
            $message->line('Thank you for your order! Your order sheet has been confirmed and is being processed.');
            $message->line('Order Reference: ' . $this->payment->reference);
            $message->line('Total Amount: ' . $this->payment->currency . ' ' . number_format($this->payment->total, 2));

            if ($this->payment->receipt_url) {
                $message->action('View Receipt', $this->payment->receipt_url);
            }

            $message->line('You will receive another email once your order ships.');

            // Attach PDF order summary
            try {
                $pdfService = new OrderSummaryPdfService();
                $pdfContent = $pdfService->generate($this->payment);
                $message->attachData($pdfContent, 'order-summary-' . $this->payment->reference . '.pdf', [
                    'mime' => 'application/pdf',
                ]);
                $message->line('**Please find your detailed order summary attached as a PDF.**');
            } catch (\Exception $e) {
                Log::error('Failed to generate PDF for customer notification', [
                    'payment_id' => $this->payment->id,
                    'error' => $e->getMessage(),
                ]);
                // Continue without PDF attachment if generation fails
            }
        } else {
            $message->line('We are glad to inform you that your order was confirmed.');
        }

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'reference' => $this->payment->reference,
        ];
    }
}
