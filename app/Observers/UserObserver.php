<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $user->notificationSetting()->create([
            'reminder_days' => [1, 3, 7],
            'weekly_report_enabled' => true,
            'task_assigned_enabled' => true,
            'comment_email_enabled' => true,
            'push_enabled' => true,
            'weekly_report_day' => 'monday',
            'weekly_report_time' => '09:00',
        ]);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
