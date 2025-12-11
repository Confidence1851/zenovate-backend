<?php

namespace App\Notifications\OrderSheet\Customer;

use App\Models\FormSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderUnfulfilledNotification extends Notification implements ShouldQueue
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
            ->subject('Order Cancelled - Unfulfilled #' . $this->session->reference)
            ->greeting('Hi ' . ($customerName ?: 'there') . ',');

        $message->line('We regret to inform you that your order has been cancelled and marked as unfulfilled.');
        $message->line('Order Reference: ' . $this->session->reference);

        if ($payment) {
            $message->line('Order Amount: ' . $payment->currency . ' ' . number_format($payment->total, 2));
        }

        $message->line('**Reason:**');
        $message->line($this->reason);

        $message->line('If you have any questions or concerns, please contact us at orders@zenovate.health');
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
