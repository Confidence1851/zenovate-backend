<?php

namespace App\Notifications\OrderSheet\Customer;

use App\Models\FormSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderRefundedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public FormSession $session, public string $reason)
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
            ->subject('Order Refunded #' . $this->session->reference)
            ->greeting('Hi ' . ($customerName ?: 'there') . ',');

        $message->line('We wanted to inform you that your order has been cancelled and a refund has been processed.');
        $message->line('Order Reference: ' . $this->session->reference);

        if ($payment) {
            $message->line('Refund Amount: ' . $payment->currency . ' ' . number_format($payment->total, 2));
            $message->line('The refund has been processed through Stripe and should appear in your account within 5-10 business days, depending on your bank or card issuer.');
        }

        $message->line('**Reason for Refund:**');
        $message->line($this->reason);

        $message->line('If you have any questions about this refund, please contact us at orders@zenovate.health');
        $message->line('We apologize for any inconvenience this may cause.');

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'form_session_id' => $this->session->id,
            'reference' => $this->session->reference,
            'reason' => $this->reason,
        ];
    }
}
