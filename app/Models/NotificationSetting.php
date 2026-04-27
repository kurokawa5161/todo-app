<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'user_id',
        'reminder_days',
        'weekly_report_enabled',
        'task_assigned_enabled',
        'comment_email_enabled',
        'push_enabled',
        'weekly_report_day',
        'weekly_report_time',
    ];

    protected $casts = [
        'reminder_days' => 'array',
        'weekly_report_enabled' => 'boolean',
        'task_assigned_enabled' => 'boolean',
        'comment_email_enabled' => 'boolean',
        'push_enabled' => 'boolean'
    ];

    protected $attributes = [
        'reminder_days' => '[3]',
        'weekly_report_enabled' => true,
        'task_assigned_enabled' => true,
        'comment_email_enabled' => true,
        'weekly_report_day' => 'monday',
        'weekly_report_time' => '09:00',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
