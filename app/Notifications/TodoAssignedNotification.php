<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Notifications\Messages\BroadcastMessage;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TodoAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Todo $todo, public User $assignedBy)
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
        // TODO: broadcastはReverbサーバー起動後に有効化
        $channels =  ['database'/*, 'broadcast'*/];

        //ユーザーのメール通知設定の確認
        if ($notifiable->notificationSetting?->task_assigned_enabled ?? true) {
            $channels[] = 'mail';
        }

        //プッシュ通知
        if ($notifiable->notificationSetting?->push_enabled ?? true) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("タスクが割り当てられました - {$this->todo->title}")
            ->greeting("こんにちは、{$notifiable->name}さん")
            ->line("{$this->assignedBy->name}さんからタスクが割り当てられました")
            ->line('')
            ->line("**タスク**:{$this->todo->title}")
            ->line("**期限**: " . ($this->todo->end_date ? $this->todo->end_date->format('Y年m月d日') : '未設定'))
            ->action("タスクを確認", url('/todos/' . $this->todo->id));
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

    public function toDatabase(object $notifiable): array
    {
        return [
            'todo_id' => $this->todo->id,
            'todo_title' => $this->todo->title,
            'assigned_by' => $this->assignedBy->name,
            'assigned_by_id' => $this->assignedBy->id,
            'message' => "{$this->assignedBy->name}さんからタスク「{$this->todo->title}」が割り当てられました"
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'todo_id' => $this->todo->id,
            'todo_title' => $this->todo->title,
            'message' => "{$this->assignedBy->name}さんからタスクが割り当てられました"
        ]);
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('新しいタスクが割り当てられました')
            ->body("{$this->assignedBy->name}さんがタスク「{$this->todo->title}」を割り当てました")
            ->icon('/favicon.ico')
            ->data([
                'todo_id' => $this->todo->id,
                'url' => route('todos.edit', $this->todo)
            ])
            ->tag('todo-assigned-' . $this->todo->id);
    }
}
