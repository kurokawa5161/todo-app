<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Todo;
use App\Models\Comment;
use App\Notifications\TodoCommentNotification;
use App\Notifications\TodoDeadlineNotification;
use App\Notifications\WeeklyReportNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use NotificationChannels\WebPush\WebPushChannel;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // TodoCommentNotification Tests
    // ========================================

    public function test_TodoCommentNotification_viaメソッドが正しいチャンネルを返す()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'todo_id' => $todo->id
        ]);

        $notification = new TodoCommentNotification($todo, $comment);
        $channels = $notification->via($user);

        $this->assertContains('database', $channels);
        $this->assertContains('mail', $channels);
        $this->assertContains(WebPushChannel::class, $channels);
    }

    public function test_TodoCommentNotification_メール無効時はmailチャンネルが含まれない()
    {
        $user = User::factory()->create();
        $user->notificationSetting()->create([
            'comment_email_enabled' => false,
            'push_enabled' => true
        ]);

        $todo = Todo::factory()->create(['user_id' => $user->id]);
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'todo_id' => $todo->id
        ]);

        $notification = new TodoCommentNotification($todo, $comment);
        $channels = $notification->via($user);

        $this->assertContains('database', $channels);
        $this->assertNotContains('mail', $channels);
        $this->assertContains(WebPushChannel::class, $channels);
    }

    public function test_TodoCommentNotification_toDatabaseが正しいデータを返す()
    {
        $commenter = User::factory()->create(['name' => 'コメント者']);
        $user = User::factory()->create();
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'title' => 'テストTodo'
        ]);
        $comment = Comment::factory()->create([
            'user_id' => $commenter->id,
            'todo_id' => $todo->id,
            'body' => 'テストコメント'
        ]);

        $notification = new TodoCommentNotification($todo, $comment);
        $data = $notification->toDatabase($user);

        $this->assertEquals($todo->id, $data['todo_id']);
        $this->assertEquals('テストTodo', $data['todo_title']);
        $this->assertEquals($comment->id, $data['comment_id']);
        $this->assertEquals('テストコメント', $data['comment_content']);
        $this->assertEquals('コメント者', $data['commenter_name']);
        $this->assertStringContainsString('コメント者', $data['message']);
        $this->assertStringContainsString('テストTodo', $data['message']);
    }

    public function test_TodoCommentNotification_toMailが正しいメールメッセージを返す()
    {
        $commenter = User::factory()->create(['name' => 'コメント者']);
        $user = User::factory()->create(['name' => 'Todo所有者']);
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'title' => 'テストTodo'
        ]);
        $comment = Comment::factory()->create([
            'user_id' => $commenter->id,
            'todo_id' => $todo->id,
            'body' => 'テストコメント'
        ]);

        $notification = new TodoCommentNotification($todo, $comment);
        $mailMessage = $notification->toMail($user);

        $this->assertEquals("コメント通知 - テストTodo", $mailMessage->subject);
        $this->assertStringContainsString('こんにちはTodo所有者さん', $mailMessage->greeting);
        $this->assertStringContainsString('コメント者', $mailMessage->introLines[0]);
        $this->assertStringContainsString('テストTodo', $mailMessage->introLines[2]);
    }

    // ========================================
    // TodoDeadlineNotification Tests
    // ========================================

    public function test_TodoDeadlineNotification_viaメソッドが正しいチャンネルを返す()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $notification = new TodoDeadlineNotification($todo, 3);
        $channels = $notification->via($user);

        $this->assertContains('database', $channels);
        $this->assertContains('mail', $channels);
        $this->assertContains(WebPushChannel::class, $channels);
    }

    public function test_TodoDeadlineNotification_toMailが正しいメールメッセージを返す()
    {
        $user = User::factory()->create(['name' => 'テストユーザー']);
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'title' => 'テストTodo',
            'end_date' => now()->addDays(3)
        ]);

        $notification = new TodoDeadlineNotification($todo, 3);
        $mailMessage = $notification->toMail($user);

        $this->assertEquals('Todoの期限が近づいています（3日後）', $mailMessage->subject);
        $this->assertStringContainsString('こんにちは、テストユーザーさん', $mailMessage->greeting);
        $this->assertStringContainsString('3日後', $mailMessage->introLines[0]);
        $this->assertStringContainsString('テストTodo', $mailMessage->introLines[2]);
    }

    public function test_TodoDeadlineNotification_toArrayが正しいデータを返す()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'title' => 'テストTodo',
            'end_date' => '2026-06-01'
        ]);

        $notification = new TodoDeadlineNotification($todo, 7);
        $data = $notification->toArray($user);

        $this->assertEquals($todo->id, $data['todo_id']);
        $this->assertEquals('テストTodo', $data['todo_title']);
        $this->assertEquals('2026-06-01', $data['end_date']);
        $this->assertEquals(7, $data['days_before']);
    }

    public function test_TodoDeadlineNotification_daysBefore1日のメールメッセージ()
    {
        $user = User::factory()->create(['name' => 'テストユーザー']);
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'title' => 'テストTodo',
            'end_date' => now()->addDay()
        ]);

        $notification = new TodoDeadlineNotification($todo, 1);
        $mailMessage = $notification->toMail($user);

        $this->assertEquals('Todoの期限が近づいています（1日後）', $mailMessage->subject);
        $this->assertStringContainsString('1日後', $mailMessage->introLines[0]);
    }

    // ========================================
    // WeeklyReportNotification Tests
    // ========================================

    public function test_WeeklyReportNotification_viaメソッドが正しいチャンネルを返す()
    {
        $user = User::factory()->create();

        $stats = [
            'completed' => 10,
            'pending' => 5,
            'upcoming' => 3
        ];
        $upcomingTodos = [];

        $notification = new WeeklyReportNotification($stats, $upcomingTodos);
        $channels = $notification->via($user);

        $this->assertContains('database', $channels);
        $this->assertContains('mail', $channels);
        $this->assertContains(WebPushChannel::class, $channels);
    }

    public function test_WeeklyReportNotification_週次レポート無効時はmailチャンネルが含まれない()
    {
        $user = User::factory()->create();
        $user->notificationSetting()->create([
            'weekly_report_enabled' => false,
            'push_enabled' => true
        ]);

        $stats = [
            'completed' => 10,
            'pending' => 5,
            'upcoming' => 3
        ];
        $upcomingTodos = [];

        $notification = new WeeklyReportNotification($stats, $upcomingTodos);
        $channels = $notification->via($user);

        $this->assertContains('database', $channels);
        $this->assertNotContains('mail', $channels);
        $this->assertContains(WebPushChannel::class, $channels);
    }

    public function test_WeeklyReportNotification_toMailが正しいメールメッセージを返す()
    {
        $user = User::factory()->create(['name' => 'テストユーザー']);

        $stats = [
            'completed' => 10,
            'pending' => 5,
            'upcoming' => 3
        ];
        $upcomingTodos = [];

        $notification = new WeeklyReportNotification($stats, $upcomingTodos);
        $mailMessage = $notification->toMail($user);

        $this->assertStringContainsString('週次レポート', $mailMessage->subject);
        $this->assertStringContainsString('こんにちはテストユーザーさん', $mailMessage->greeting);
        $this->assertStringContainsString('先週の活動サマリ', $mailMessage->introLines[0]);
        $this->assertStringContainsString('完了：10件', $mailMessage->introLines[3]);
        $this->assertStringContainsString('未完了：5件', $mailMessage->introLines[4]);
        $this->assertStringContainsString('今週期限：3件', $mailMessage->introLines[5]);
    }

    public function test_WeeklyReportNotification_今週期限のTodo情報が含まれる()
    {
        $user = User::factory()->create(['name' => 'テストユーザー']);

        $stats = [
            'completed' => 10,
            'pending' => 5,
            'upcoming' => 2
        ];
        $upcomingTodos = [
            ['title' => 'Todo1', 'end_date' => '2026-06-01'],
            ['title' => 'Todo2', 'end_date' => '2026-06-02']
        ];

        $notification = new WeeklyReportNotification($stats, $upcomingTodos);
        $mailMessage = $notification->toMail($user);

        $this->assertStringContainsString('今週期限のTodo', $mailMessage->introLines[7]);
        $this->assertStringContainsString('Todo1', $mailMessage->introLines[8]);
        $this->assertStringContainsString('2026-06-01', $mailMessage->introLines[8]);
        $this->assertStringContainsString('Todo2', $mailMessage->introLines[9]);
        $this->assertStringContainsString('2026-06-02', $mailMessage->introLines[9]);
    }

    public function test_WeeklyReportNotification_toWebPushが正しいメッセージを返す()
    {
        $user = User::factory()->create();

        $stats = [
            'completed' => 10,
            'pending' => 5,
            'upcoming' => 3
        ];
        $upcomingTodos = [];

        $notification = new WeeklyReportNotification($stats, $upcomingTodos);
        $webPushMessage = $notification->toWebPush($user);

        // WebPushMessageの内容をReflectionで確認
        $reflection = new \ReflectionClass($webPushMessage);
        $titleProperty = $reflection->getProperty('title');
        $titleProperty->setAccessible(true);
        $bodyProperty = $reflection->getProperty('body');
        $bodyProperty->setAccessible(true);

        $title = $titleProperty->getValue($webPushMessage);
        $body = $bodyProperty->getValue($webPushMessage);

        $this->assertStringContainsString('週次レポート', $title);
        $this->assertStringContainsString('完了：10件', $body);
        $this->assertStringContainsString('未完了：5件', $body);
        $this->assertStringContainsString('今週期限：3件', $body);
    }

    // ========================================
    // ShouldQueue Interface Tests
    // ========================================

    public function test_TodoCommentNotification_ShouldQueueインターフェースを実装()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'todo_id' => $todo->id
        ]);

        $notification = new TodoCommentNotification($todo, $comment);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $notification);
    }

    public function test_TodoDeadlineNotification_ShouldQueueインターフェースを実装()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $notification = new TodoDeadlineNotification($todo, 3);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $notification);
    }

    public function test_WeeklyReportNotification_ShouldQueueインターフェースを実装()
    {
        $stats = ['completed' => 10, 'pending' => 5, 'upcoming' => 3];
        $upcomingTodos = [];

        $notification = new WeeklyReportNotification($stats, $upcomingTodos);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $notification);
    }
}
