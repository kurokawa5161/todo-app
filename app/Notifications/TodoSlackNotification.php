<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Todo;
use Illuminate\Notifications\Messages\SlackMessage;

class TodoSlackNotification extends Notification
{
    use Queueable;
    protected $todo;
    protected $action;

    /**
     * Create a new notification instance.
     */
    public function __construct(Todo $todo, $action)
    {
        $this->todo = $todo;
        $this->action = $action;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toSlack(object $notifiable)
    {
        $message = match ($this->action) {
            'created' => '新しいTodoが作成されました',
            'updated' => 'Todoが更新されました',
            'completed' => 'Todoが完了しました',
            'due_soon' => 'Todoの期限が近づいています',
            default => 'Todoが更新されました'
        };

        return (new SlackMessage)
            ->content($message)
            ->attachment(function ($attachment) {
                $attachment->title($this->todo->title, route('todos.edit', $this->todo))
                    ->fields([
                        '内容' => $this->todo->content ?? 'なし',
                        '期限' => $this->todo->end_date->format('Y-m-d'),
                    ]);
            });
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $message = match ($this->action) {
            'created' => '新しいTodoが作成されました',
            'updated' => 'Todoが更新されました',
            'completed' => 'Todoが完了しました',
            'due_soon' => 'Todoの期限が近づいています',
            default => 'Todoが更新されました'
        };

        return [
            'message' => $message,
            'action' => $this->action,
            'todo_id' => $this->todo->id,
            'todo_title' => $this->todo->title,
            'todo_content' => $this->todo->content,
            'todo_end_date' => $this->todo->end_date->format('Y-m-d'),
        ];
    }
}
