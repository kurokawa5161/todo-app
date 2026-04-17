<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use App\Notifications\TodoDeadlineNotification;


class SendDeadlineNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-deadline-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '明日が締切のTodoについて通知を送信';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tomorrow = Carbon::tomorrow();

        $todos = Todo::whereDate('end_date', $tomorrow)
            ->whereNull('completed_at')
            ->get();

        foreach ($todos as $todo) {
            Log::info("Todo ID: {$todo->id}, タイトル: {$todo->title}, 締切: {$todo->end_date}, completed_at: " . ($todo->completed_at ?? 'null'));
        }

        $todosByUser = $todos->groupBy('user_id');

        foreach ($todosByUser as $user_id => $userTodos) {
            $user = User::find($user_id);

            foreach ($userTodos as $key => $todo) {
                //通知をおくる
                $user->notify(new TodoDeadlineNotification($todo));
            }

            //ログに記録
            Log::info("通知送信： ユーザーID{$user_id}に{$userTodos->count()}件");
        }
    }
}
