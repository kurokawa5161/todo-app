<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Todo;
use Carbon\Carbon;

class TodoDeadlineNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $todo;

    /**
     * Create a new notification instance.
     */
    public function __construct(Todo $todo)
    {
        $this->todo = $todo;
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
            ->subject('Todoの期限が近づいています')
            ->line('タイトル：' . $this->todo->title)
            ->line('締切日：' . Carbon::parse($this->todo->end_date)->format('Y年m月d日'))
            ->action('Todoを確認', url('/todos/' . $this->todo->id));
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
