<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PushSubscriptionController extends Controller
{
    /**
     * プッシュ通知購読情報を保存
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|url',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string'
        ]);

        // laravel-notification-channels/webpushが自動で処理
        auth()->user()->updatePushSubscription(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth'],
        );

        Log::info('Push subscription created', [
            'user_id' => auth()->id(),
            'endpoint' => $validated['endpoint'],
        ]);

        return response()->json(['message' => 'Subscription saved']);
    }

    /**
     * プッシュ通知購読を削除
     */
    public function destroy(Request $request)
    {
        $endpoint = $request->input('endpoint');

        auth()->user()->deletePushSubscription($endpoint);

        Log::info('Push subscription deleted', [
            'user_id' => auth()->id(),
            'endpoint' => $endpoint
        ]);

        return response()->json(['message' => 'Subscription deleted']);
    }
}
