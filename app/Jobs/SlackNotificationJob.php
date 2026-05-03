<?php

namespace App\Jobs;

use App\Models\Todo;
use App\Services\SlackService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SlackNotificationJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    protected $todo;
    protected $action;

    /**
     * Create a new job instance.
     */
    public function __construct(Todo $todo, string $action)
    {
        $this->todo = $todo;
        $this->action = $action;
    }

    /**
     * Execute the job.
     */
    public function handle(SlackService $slackService): void
    {
        $message = $this->buildMessage();

        //Slackにメッセージ送信（ログのみ、実際の送信はスキップ）
        $slackService->sendMessage(
            channel: '#todo-notifications',
            text: $message,
            user: $this->todo->user
        );
    }

    /**
     * メッセージ作成
     */
    protected function buildMessage(): string
    {
        $user = $this->todo->user->name ?? '不明';
        $title = $this->todo->title;

        return match ($this->action) {
            'created' => "{$user}さんが新しいTodoを作成しました：「{$title}」",
            'completed' => "{$user}さんがTodoを完了しました：「{$title}」",
            'uncompleted' => "{$user}さんがTodoを未完了に戻しました：「{$title}」",
            'deleted' => "{$user}さんがTodoを削除しました：「{$title}」",
            default  => "{$user}さんがTodoを操作しました：「{$title}」",
        };
    }
}
