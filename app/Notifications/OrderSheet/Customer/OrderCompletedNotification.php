<?php

namespace App\Notifications\OrderSheet\Customer;

use App\Models\FormSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public FormSession $session)
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $metadata = $this->session->metadata['raw'] ?? [];
        $customerName = trim(($metadata['firstName'] ?? '') . ' ' . ($metadata['lastName'] ?? ''));
        $payment = $this->session->completedPayment;

        $message = (new MailMessage)
            ->subject('Order Completed #' . $this->session->reference)
            ->greeting('Hi ' . ($customerName ?: 'there') . ',');

        $message->line('Great news! Your order has been completed and is ready for shipping.');
        $message->line('Order Reference: ' . $this->session->reference);

        if ($payment) {
            $message->line('Total Amount: ' . $payment->currency . ' ' . number_format($payment->total, 2));
            if ($payment->receipt_url) {
                $message->action('View Receipt', $payment->receipt_url);
            }
        }

        $message->line('You will receive tracking information once your order ships.');
        $message->line('Thank you for choosing Zenovate!');

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'form_session_id' => $this->session->id,
            'reference' => $this->session->reference,
        ];
    }
}
