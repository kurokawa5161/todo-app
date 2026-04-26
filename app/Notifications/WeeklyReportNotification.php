<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyReportNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public array $stats, public array $upComingTodos) {}

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
        $message = (new MailMessage)
            ->subject('週次レポート - ' . now()->format('Y年m月d日'))
            ->greeting('こんにちは' . $notifiable->name . 'さん')
            ->line('先週の活動サマリをお送りします')
            ->line('');

        //統計情報
        $message->line('**先週の実績**')
            ->line("完了：{$this->stats['completed']}件")
            ->line("未完了：{$this->stats['pending']}件")
            ->line("今週期限：{$this->stats['upcoming']}件")
            ->line('');

        //今週期限のTodo
        if (count($this->upComingTodos) > 0) {
            $message->line('**今週期限のTodo**');
            foreach ($this->upComingTodos as $todo) {
                $message->line("- {$todo['title']} (期限：{$todo['end_date']})");
            }
        }
        return $message->action('Todoを確認する', url('todos'));
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
