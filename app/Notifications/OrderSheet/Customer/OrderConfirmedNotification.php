<?php

namespace App\Notifications\OrderSheet\Customer;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
        $formSession = $this->payment->formSession;
        $metadata = $formSession ? ($formSession->metadata['raw'] ?? []) : [];
        $customerName = trim(($metadata['firstName'] ?? '') . ' ' . ($metadata['lastName'] ?? ''));

        $message = (new MailMessage)
            ->subject('Order Confirmed #' . $this->payment->reference)
            ->greeting('Hi ' . ($customerName ?: 'there') . ',');

        if ($this->payment->order_type === 'order_sheet') {
            $message->line('Thank you for your order! Your order sheet has been confirmed and is being processed.');
            $message->line('Order Reference: ' . $this->payment->reference);
            $message->line('Total Amount: ' . $this->payment->currency . ' ' . number_format($this->payment->total, 2));

            if ($this->payment->receipt_url) {
                $message->action('View Receipt', $this->payment->receipt_url);
            }

            $message->line('You will receive another email once your order ships.');
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
