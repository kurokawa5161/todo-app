<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Todo;
use Carbon\Carbon;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TodoDeadlineNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $todo;
    protected $daysBefore;

    /**
     * Create a new notification instance.
     */
    public function __construct(Todo $todo, int $daysBefore = 3)
    {
        $this->todo = $todo;
        $this->daysBefore = $daysBefore;
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
        // 締切通知は常に送信（reminder_daysで期限前の日数を管理）
        $channels[] = 'mail';

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
            ->subject("Todoの期限が近づいています（{$this->daysBefore}日後）")
            ->greeting('こんにちは、' . $notifiable->name . 'さん')
            ->line("**{$this->daysBefore}日後**が期限のTodoがあります")
            ->line('')
            ->line('**タイトル**：' . $this->todo->title)
            ->line('**締切日**：' . Carbon::parse($this->todo->end_date)->format('Y年m月d日'))
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
            'todo_id' =>  $this->todo->id,
            'todo_title' => $this->todo->title,
            'end_date' => Carbon::parse($this->todo->end_date)->format('Y-m-d'),
            'days_before' => $this->daysBefore
        ];
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Todoの期限が近づいています')
            ->body("{$this->daysBefore}日後が期限のTodo「{$this->todo->title}」があります")
            ->icon('/favicon.ico')
            ->data([
                'todo_id' => $this->todo->id,
                'url' => route('todos.edit', $this->todo)
            ])
            ->tag('todo-deadline-' . $this->todo->id);
    }
}
