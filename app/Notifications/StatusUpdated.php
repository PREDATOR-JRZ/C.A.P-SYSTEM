<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Step 8: the student account is notified of its status (dashboard + email). */
class StatusUpdated extends Notification
{
    public function __construct(public string $title, public string $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line($this->message)
            ->action('Open your dashboard', route('student.dashboard'));
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title, 'message' => $this->message];
    }
}
