<?php

namespace Database\Factories;

use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationSetting>
 */
class NotificationSettingFactory extends Factory
{
    protected $model = NotificationSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reminder_days' => [1, 3, 7],
            'weekly_report_enabled' => true,
            'task_assigned_enabled' => true,
            'comment_email_enabled' => true,
            'push_enabled' => true,
            'weekly_report_day' => 'monday',
            'weekly_report_time' => '09:00',
        ];
    }
}
