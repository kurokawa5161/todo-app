<?php

use Illuminate\Support\Facades\Broadcast;

// 個人Todoチャンネル（自分のTodoのみ受信）
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// チームTodoチャンネル（Todoイベント用）
Broadcast::channel('team.{teamId}', function ($user, $teamId) {
    return $user->teams()->where('teams.id', $teamId)->exists();
});

// ユーザー通知チャンネル（Laravel標準）
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// チームPresenceチャンネル（オンラインメンバー表示）
Broadcast::channel('team-presence.{teamId}', function ($user, $teamId) {
    if ($user->teams()->where('teams.id', $teamId)->exists()) {
        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }
});
