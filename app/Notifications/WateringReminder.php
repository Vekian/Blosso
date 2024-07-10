<?php

namespace App\Notifications;

use App\Models\Plant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WateringReminder extends Notification
{
    use Queueable;
    protected Plant $plant;

    /**
     * Create a new notification instance.
     */
    public function __construct(Plant $plant)
    {
        $this->plant = $plant;
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
                    ->line('Une de vos plantes doit être arrosée')
                    ->line('La plante ' . $this->plant->common_name . " doit être arrosée.");
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
