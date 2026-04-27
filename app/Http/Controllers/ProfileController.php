<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function updateReminder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reminder_days_before' => 'integer|nullable|in:1,2,3,7'
        ]);
        $request->user()->update($validated);
        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * 通知設定画面を表示
     */
    public function editNotifications(Request $request)
    {
        $setting = $request->user()->notificationSetting;

        // NotificationSettingが存在しない場合は作成
        if (!$setting) {
            $setting = $request->user()->notificationSetting()->create([
                'reminder_days' => [1, 3, 7],
                'weekly_report_enabled' => true,
                'task_assigned_enabled' => true,
                'comment_email_enabled' => true,
                'push_enabled' => true,
                'weekly_report_day' => 'monday',
                'weekly_report_time' => '09:00',
            ]);
        }

        return view('profile.notifications', [
            'user' => $request->user(),
            'setting' => $setting,
        ]);
    }

    /**
     * 通知設定を更新
     */
    public function updateNotifications(Request $request)
    {
        $validated = $request->validate([
            'push_enabled' => 'boolean',
            'task_assigned_enabled' => 'boolean',
            'comment_email_enabled' => 'boolean',
            'weekly_report_enabled' => 'boolean',
        ]);

        $setting = $request->user()->notificationSetting;

        if ($setting) {
            $setting->update([
                'push_enabled' => $request->has('push_enabled'),
                'task_assigned_enabled' => $request->has('task_assigned_enabled'),
                'comment_email_enabled' => $request->has('comment_email_enabled'),
                'weekly_report_enabled' => $request->has('weekly_report_enabled'),
            ]);
        }

        return redirect()->route('profile.notifications.edit')
            ->with('status', 'notifications-updated');
    }
}
