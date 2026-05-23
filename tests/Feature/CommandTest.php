<?php

use App\Models\User;
use App\Models\Todo;
use App\Models\NotificationSetting;
use App\Notifications\TodoDeadlineNotification;
use App\Notifications\WeeklyReportNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

uses(RefreshDatabase::class);

/**
 * SendDeadlineNotifications Command Tests
 */
test('deadline notification command sends notifications for todos due in 3 days', function () {
    Notification::fake();

    $user = User::factory()->create();
    NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'reminder_days' => [3],
    ]);

    // 3日後が期限のTodo
    $todoDueIn3Days = Todo::factory()->create([
        'user_id' => $user->id,
        'end_date' => Carbon::today()->addDays(3),
        'completed_at' => null,
    ]);

    // 5日後が期限のTodo（通知されない）
    $todoDueIn5Days = Todo::factory()->create([
        'user_id' => $user->id,
        'end_date' => Carbon::today()->addDays(5),
        'completed_at' => null,
    ]);

    $this->artisan('app:send-deadline-notifications')->assertExitCode(0);

    // 3日後のTodoのみ通知される（通知が1回送られることを確認）
    Notification::assertSentTo($user, TodoDeadlineNotification::class, 1);
});

test('deadline notification command supports custom reminder days', function () {
    Notification::fake();

    $user = User::factory()->create();
    NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'reminder_days' => [1, 3, 7], // 1日、3日、7日前に通知
    ]);

    // 1日後が期限のTodo
    $todoDueIn1Day = Todo::factory()->create([
        'user_id' => $user->id,
        'end_date' => Carbon::today()->addDays(1),
        'completed_at' => null,
    ]);

    // 7日後が期限のTodo
    $todoDueIn7Days = Todo::factory()->create([
        'user_id' => $user->id,
        'end_date' => Carbon::today()->addDays(7),
        'completed_at' => null,
    ]);

    $this->artisan('app:send-deadline-notifications')->assertExitCode(0);

    // 1日後のTodo、7日後のTodoが通知される
    Notification::assertSentTo($user, TodoDeadlineNotification::class, 2);
});

test('deadline notification command does not send for completed todos', function () {
    Notification::fake();

    $user = User::factory()->create();
    NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'reminder_days' => [3],
    ]);

    // 3日後が期限だが完了済みのTodo
    $completedTodo = Todo::factory()->create([
        'user_id' => $user->id,
        'end_date' => Carbon::today()->addDays(3),
        'completed_at' => Carbon::now(),
    ]);

    $this->artisan('app:send-deadline-notifications')->assertExitCode(0);

    // 完了済みなので通知されない
    Notification::assertNothingSent();
});

test('deadline notification command uses default 3 days if no setting', function () {
    Notification::fake();

    $user = User::factory()->create();
    // NotificationSettingなし（デフォルト3日）

    $todoDueIn3Days = Todo::factory()->create([
        'user_id' => $user->id,
        'end_date' => Carbon::today()->addDays(3),
        'completed_at' => null,
    ]);

    $this->artisan('app:send-deadline-notifications')->assertExitCode(0);

    // デフォルト3日で通知される
    Notification::assertSentTo($user, TodoDeadlineNotification::class);
});

/**
 * SendWeeklyReports Command Tests
 */
test('weekly report command sends reports to users with enabled setting', function () {
    Notification::fake();

    $userWithReport = User::factory()->create();
    NotificationSetting::factory()->create([
        'user_id' => $userWithReport->id,
        'weekly_report_enabled' => true,
    ]);

    $userWithoutReport = User::factory()->create();
    NotificationSetting::factory()->create([
        'user_id' => $userWithoutReport->id,
        'weekly_report_enabled' => false,
    ]);

    $this->artisan('notifications:send-weekly-reports')->assertExitCode(0);

    // 有効なユーザーのみ通知される
    Notification::assertSentTo($userWithReport, WeeklyReportNotification::class);
    Notification::assertNotSentTo($userWithoutReport, WeeklyReportNotification::class);
});

test('weekly report command calculates correct statistics', function () {
    Notification::fake();

    $user = User::factory()->create();
    NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'weekly_report_enabled' => true,
    ]);

    $lastWeekStart = Carbon::now()->subWeek()->startOfWeek();
    $lastWeekEnd = Carbon::now()->subWeek()->endOfWeek();

    // 先週完了したTodo（2件）
    Todo::factory()->count(2)->create([
        'user_id' => $user->id,
        'completed_at' => $lastWeekStart->copy()->addDays(2),
    ]);

    // 未完了のTodo（3件）
    Todo::factory()->count(3)->create([
        'user_id' => $user->id,
        'completed_at' => null,
        'end_date' => Carbon::now()->addDays(10),
    ]);

    // 今週期限のTodo（1件）
    Todo::factory()->create([
        'user_id' => $user->id,
        'completed_at' => null,
        'end_date' => Carbon::now()->addDays(3),
    ]);

    $this->artisan('notifications:send-weekly-reports')->assertExitCode(0);

    Notification::assertSentTo($user, WeeklyReportNotification::class, function ($notification) {
        return $notification->stats['completed'] === 2
            && $notification->stats['pending'] === 4 // 3 + 1 = 4
            && $notification->stats['upcoming'] === 1;
    });
});

test('weekly report command includes upcoming todos data', function () {
    Notification::fake();

    $user = User::factory()->create();
    NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'weekly_report_enabled' => true,
    ]);

    // 今週期限のTodo
    $upcomingTodo = Todo::factory()->create([
        'user_id' => $user->id,
        'title' => 'Upcoming Task',
        'completed_at' => null,
        'end_date' => Carbon::now()->addDays(3),
    ]);

    $this->artisan('notifications:send-weekly-reports')->assertExitCode(0);

    // 週次レポートが送信されたことを確認
    Notification::assertSentTo($user, WeeklyReportNotification::class);
});

test('weekly report command does not send if setting is null', function () {
    Notification::fake();

    $user = User::factory()->create();
    // NotificationSettingなし（デフォルトで無効）

    $this->artisan('notifications:send-weekly-reports')->assertExitCode(0);

    // 設定なしなので通知されない
    Notification::assertNothingSent();
});
