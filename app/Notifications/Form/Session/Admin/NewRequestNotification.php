<?php

namespace App\Notifications\Form\Session\Admin;

use App\Models\FormSession;
use App\Services\Form\Session\DTOService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewRequestNotification extends Notification
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
        $message = (new MailMessage)
            ->subject("Action Needed - New Order Received #".$this->session->reference)
            ->greeting("Hello")
            ->line("A new order has been received. Kindly review the details below")
            ->line("Client Name: ". $dto->fullName())
            ->line("Client Email: " . $dto->email())
            ->line("Client Phone: " . $dto->phone())
            ->line("Payment Reference: " . $dto->payment()->reference);

            $i = 1;
            foreach ($dto->paymentProducts() as $product) {
                $message->line("Product $i: - {$product->product->name}  - ".$product->getPrice());
                $i++;
            }

            $message->action('View Order', route("dashboard.form-sessions.show", $this->session->id));

            return $message;
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
