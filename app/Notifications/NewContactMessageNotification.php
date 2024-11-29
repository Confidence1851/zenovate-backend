<?php

namespace App\Notifications;

use App\Models\ContactUsMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewContactMessageNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public ContactUsMessage $message)
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
        return (new MailMessage)
            ->subject("New Message from Contact Form")
            ->greeting('Hello!')
            ->line('Someone just sumbitted a query via the contact form. Kindly see the details below:')
            ->line("Name: " . $this->message->name)
            ->line("Email: " . $this->message->email)
            ->line("Phone: " . $this->message->phone)
            ->line("Subject: " . $this->message->subject)
            ->line("Message: " . $this->message->message)
            ->replyTo($this->message->email, $this->message->name)
            ->line('Kindly respond as soon as possible.');
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
