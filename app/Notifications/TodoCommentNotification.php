<?php

namespace App\Notifications;

use App\Models\Todo;
use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TodoCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Todo $todo,
        public Comment $comment
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels =  ['database', 'broadcast'];

        //ユーザーのメール通知設定を確認
        if ($notifiable->notificationSetting?->comment_email_enabled ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'todo_id' => $this->todo->id,
            'todo_title' => $this->todo->title,
            'comment_id' => $this->comment->id,
            'comment_content' => $this->comment->body,
            'commenter_name' => $this->comment->user->name,
            'message' => "{$this->comment->user->name}があなたのTodo「{$this->todo->title}」にコメントしました",
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'todo_id' => $this->todo->id,
            'todo_title' => $this->todo->title,
            'message' => "{$this->comment->user->name} があなたのTodo「{$this->todo->title}」にコメントしました",
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("コメント通知 - {$this->todo->title}")
            ->greeting('こんにちは' . $notifiable->name . 'さん')
            ->line("{$this->comment->user->name}さんがあなたのTodoにコメントしました")
            ->line('')
            ->line("**Todo**:{$this->todo->title}")
            ->line("**コメント**:{$this->comment->body}")
            ->action('Todoを確認', url('/todos/' . $this->todo->id));
    }
}
