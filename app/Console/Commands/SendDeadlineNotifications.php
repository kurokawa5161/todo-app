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
        $users = User::whereNotNull('reminder_days_before')->get();

        foreach ($users as $user) {
            //今日から何日後の日付
            $reminderDay = Carbon::today()->addDays($user->reminder_days_before);

            $todos = $user->todos()->whereDate('end_date', $reminderDay)
                ->whereNull('completed_at')
                ->get();

            foreach ($todos as $todo) {
                //通知をおくる
                $user->notify(new TodoDeadlineNotification($todo));
            }
            //ログに記録
            if ($todos->count() > 0) {
                Log::info("通知送信： ユーザーID{$user->id}に{$todos->count()}件");
            }
        }
    }
}
