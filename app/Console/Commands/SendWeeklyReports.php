<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Todo;
use App\Notifications\WeeklyReportNotification;
use Carbon\Carbon;

class SendWeeklyReports extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-weekly-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '週次レポートをユーザーに送信';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::with('notificationSetting')->get();

        foreach ($users as $user) {
            //設定確認
            if (!$user->notificationSetting?->weekly_report_enabled) {
                continue;
            }

            //先週の統計を計算
            $lastWeekStart = Carbon::now()->subweek()->startOfWeek();
            $lastWeekEnd = Carbon::now()->subweek()->endOfWeek();

            $stats = [
                'completed' => Todo::where('user_id', $user->id)
                    ->whereNotNull('completed_at')
                    ->whereBetween('completed_at', [$lastWeekStart, $lastWeekEnd])
                    ->count(),
                'pending' => Todo::where('user_id', $user->id)
                    ->whereNull('completed_at')
                    ->count(),
                'upcoming' => Todo::where('user_id', $user->id)
                    ->whereNull('completed_at')
                    ->whereBetween('end_date', [Carbon::now(), carbon::now()->addWeek()])
                    ->count(),
            ];

            //今週期限のTodo
            $upCoimgTodos = Todo::where('user_id', $user->id)
                ->whereNull('completed_at')
                ->whereBetween('end_date', [Carbon::now(), carbon::now()->addWeek()])
                ->get()
                ->map(fn($todo) => [
                    'title' => $todo->title,
                    'end_date' => $todo->end_dat?e->format('Y-m-d'),
                ])
                ->toArray();

            //通知送信
            $user->notify(new WeeklyReportNotification($stats, $upCoimgTodos));
            $this->info("週次レポート送信：{$user->email}");
        }
        return 0;
    }
}
