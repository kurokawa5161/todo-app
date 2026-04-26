<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use App\Notifications\TodoDeadlineNotification;
use App\Notifications\TodoSlackNotification;

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
    protected $description = '期限前のTodoについて通知を送信（カスタム日数対応）';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::with('notificationSetting')->get();

        foreach ($users as $user) {
            //通知設定から日数配列を取得（デフォルト：[3]）
            $reminderDays = $user->notificationSetting?->reminder_days ?? [3];

            foreach ($reminderDays as $days) {
                //今日から$days日後の日付
                $reminderDate = Carbon::today()->addDays($days);

                $todos = $user->todos()
                    ->whereDate('end_date', $reminderDate)
                    ->whereNull('completed_at')
                    ->get();

                foreach ($todos as $todo) {
                    //通知をおくる
                    $user->notify(new TodoDeadlineNotification($todo, $days));
                    $user->notify(new TodoSlackNotification($todo, 'due_soon'));
                }
                //ログに記録
                if ($todos->count() > 0) {
                    Log::info("通知送信： ユーザーID{$user->id}に{$todos->count()}件（{$days}日前通知）");
                }
            }
        }
        return 0;
    }
}
