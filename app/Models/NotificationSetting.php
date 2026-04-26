<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'user_id',
        'reminder_days',
        'weekly_report_enable',
        'task_assigned_enable',
        'comment_email_enable',
        'weekly_report_day',
        'weekly_report_time',
    ];

    protected $casts = [
        'reminder_days' => 'array',
        'weekly_report_enabled' => 'boolean',
        'task_assigned_enabled' => 'boolean',
        'comment_email_enabled' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
