<?php

namespace App\Notifications\Form\Session\Customer;

use App\Models\FormSession;
use App\Services\Form\Session\DTOService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReceivedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public FormSession $session)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $dto = new DTOService($this->session);
        return (new MailMessage)
            ->subject("Order Received #" . $dto->reference())
            ->greeting("Hi " . $dto->fullName())
            ->line("We are glad to inform you that your order has been received and being reviewed.")
            ->line("An email will be send after review and confirmation.");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
