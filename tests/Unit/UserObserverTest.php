<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_ユーザー作成時に通知設定が自動作成される()
    {
        $user = User::factory()->create();

        // notificationSettingが作成されていることを確認
        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $user->id,
            'weekly_report_enabled' => true,
            'task_assigned_enabled' => true,
            'comment_email_enabled' => true,
            'push_enabled' => true,
            'weekly_report_day' => 'monday',
            'weekly_report_time' => '09:00'
        ]);
    }

    public function test_通知設定のデフォルト値が正しく設定される()
    {
        $user = User::factory()->create();

        $notificationSetting = $user->notificationSetting;

        $this->assertNotNull($notificationSetting);
        $this->assertEquals([1, 3, 7], $notificationSetting->reminder_days);
        $this->assertTrue($notificationSetting->weekly_report_enabled);
        $this->assertTrue($notificationSetting->task_assigned_enabled);
        $this->assertTrue($notificationSetting->comment_email_enabled);
        $this->assertTrue($notificationSetting->push_enabled);
        $this->assertEquals('monday', $notificationSetting->weekly_report_day);
        $this->assertEquals('09:00', $notificationSetting->weekly_report_time);
    }

    public function test_複数ユーザー作成時にそれぞれ通知設定が作成される()
    {
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $this->assertDatabaseHas('notification_settings', [
                'user_id' => $user->id
            ]);
        }
    }
}
