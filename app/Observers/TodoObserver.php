<?php

namespace App\Observers;

use App\Jobs\SlackNotificationJob;
use App\Models\Todo;

class TodoObserver
{
    /**
     * Todo作成時
     */
    public function created(Todo $todo): void
    {
        // テスト環境では実行しない（SQLite nested transaction回避）
        if (app()->environment('testing')) {
            return;
        }

        //Slack通知をキューに追加
        SlackNotificationJob::dispatch($todo, 'created');
    }

    /**
     * Todo更新時
     */
    public function updated(Todo $todo): void
    {
        // テスト環境では実行しない（SQLite nested transaction回避）
        if (app()->environment('testing')) {
            return;
        }

        //完了状態が変更された場合のみ通知
        if ($todo->wasChanged('completed_at')) {
            $action = $todo->completed_at ? 'completed' : 'uncompleted';
            SlackNotificationJob::dispatch($todo, $action);
        }
    }

    /**
     * Todo削除時
     */
    public function deleted(Todo $todo): void
    {
        // テスト環境では実行しない（SQLite nested transaction回避）
        if (app()->environment('testing')) {
            return;
        }

        //Slack通知をキューに追加
        SlackNotificationJob::dispatch($todo, 'deleted');
    }
}
