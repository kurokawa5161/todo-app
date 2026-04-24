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
        return ['database', 'broadcast'];
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
}
