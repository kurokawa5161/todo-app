# フェーズ19B 引継ぎ内容

**実装日**: 2026-04-27  
**完了日**: 2026-04-27  
**実装者**: User + Claude Code  
**ステータス**: 完了 ✅

---

## 📋 実装概要

フェーズ19Bでは、ブラウザプッシュ通知とPWA（Progressive Web App）機能を実装しました。これにより、ユーザーはブラウザを閉じていても通知を受け取れるようになり、アプリをホーム画面にインストールできるようになりました。

---

## ✅ 完了した機能

### 1. プッシュ通知システム

**パッケージ導入**
- `laravel-notification-channels/webpush` インストール済み
- VAPID鍵生成・設定完了

**実装内容**
- Service Worker実装（`public/service-worker.js`）
- PushSubscriptionController作成（購読管理）
- TodoAssignedNotificationにWebPushChannel追加
- プッシュ通知購読テーブル（push_subscriptions）

**技術ポイント**
- VAPID（Voluntary Application Server Identification）認証
- Web Push Protocol
- Service Workerによるバックグラウンド処理

### 2. PWA対応

**実装内容**
- PWA Manifest作成（`public/manifest.json`）
- Service Worker登録（`resources/js/app.js`）
- Apple Touch Icon設定
- テーマカラー設定（#4f46e5）

**機能**
- ホーム画面にアプリ追加可能
- スタンドアロンモード対応
- オフライン対応（基本的なキャッシュ）

### 3. NotificationSetting自動作成

**実装内容**
- UserObserver作成（`app/Observers/UserObserver.php`）
- 新規ユーザー登録時にNotificationSettingを自動生成
- AppServiceProviderでObserver登録

**デフォルト設定**
```php
[
    'reminder_days' => [1, 3, 7],
    'weekly_report_enabled' => true,
    'task_assigned_enabled' => true,
    'comment_email_enabled' => true,
    'push_enabled' => true,
    'weekly_report_day' => 'monday',
    'weekly_report_time' => '09:00',
]
```

### 4. 通知設定UI

**実装内容**
- 通知設定画面（`/profile/notifications`）
- ProfileControllerにメソッド追加
  - `editNotifications()` - 設定画面表示
  - `updateNotifications()` - 設定更新
- 4種類の通知ON/OFF制御
  - プッシュ通知
  - タスク割り当て通知（メール）
  - コメント通知（メール）
  - 週次レポート

**UI/UX**
- チェックボックスで簡単切り替え
- 保存成功メッセージ（Alpine.js、2秒後自動消去）
- ダークモード対応

### 5. ナビゲーション統合

**実装内容**
- ナビゲーションバーに「⚙️ 設定」リンク追加
- プロフィールページに通知設定セクション追加
- モバイル対応（レスポンシブメニュー）

---

## 🔑 環境設定

### HTTPS要件

**重要**: Service WorkerとWeb PushはHTTPS環境が必須です（localhost除く）

```bash
# Laravel Herdで有効化済み
herd secure todo-app

# アクセスURL
https://todo-app.test
```

### VAPID鍵設定

**.env に追加済み**
```env
VAPID_PUBLIC_KEY=BL5hGRXQey4OvgFkBIaaTIvNeLKpBhwGFMBCJpGzZKJQsi02zupBwL6FY8qfsMGD7T2IwePUqaf0xhKrZehBcXY
VAPID_PRIVATE_KEY=jGZ7AOCFsfurt4bTy9ec-wh6NXDlFD2INU_iav7TY08
VAPID_SUBJECT=mailto:is1101520@gmail.com
```

**生成方法（再生成が必要な場合）**
```bash
# Windows OpenSSL問題回避のため、npx使用
npx web-push generate-vapid-keys
```

### 必要なコマンド

**開発環境起動**
```bash
# キューワーカー（必須）
php artisan queue:work

# Reverbサーバー（リアルタイム通知用）
php artisan reverb:start

# HTTPSアクセス（必須）
https://todo-app.test
```

---

## 📂 実装ファイル一覧

### 新規作成ファイル

**バックエンド**
```
app/Http/Controllers/PushSubscriptionController.php
app/Observers/UserObserver.php
database/migrations/2026_04_27_xxxxxx_add_push_enabled_to_notification_settings_table.php
```

**フロントエンド**
```
public/service-worker.js
public/manifest.json
resources/views/profile/notifications.blade.php
```

### 修正ファイル

**設定・環境**
```
.env                                  # VAPID鍵追加
```

**モデル**
```
app/Models/User.php                   # HasPushSubscriptions追加
app/Models/NotificationSetting.php    # push_enabled追加
```

**通知**
```
app/Notifications/TodoAssignedNotification.php  # WebPushChannel追加
```

**コントローラー**
```
app/Http/Controllers/ProfileController.php
# editNotifications(), updateNotifications() 追加
```

**プロバイダー**
```
app/Providers/AppServiceProvider.php  # UserObserver登録
```

**ビュー**
```
resources/views/layouts/app.blade.php         # PWAメタタグ追加
resources/views/layouts/navigation.blade.php  # 設定リンク追加
resources/views/profile/edit.blade.php        # 通知設定セクション追加
```

**JavaScript**
```
resources/js/app.js  # Service Worker登録・プッシュ購読
```

**ルート**
```
routes/web.php  # 通知設定・プッシュ購読ルート追加
```

---

## 🔍 動作確認手順

### 1. 環境確認

```bash
# HTTPSでアクセス
https://todo-app.test

# DevToolsでService Worker確認
F12 → Application → Service Workers
→ "service-worker.js" が "activated and is running" になっていること
```

### 2. 通知権限確認

```bash
# ブラウザのアドレスバー左側のアイコンをクリック
# 通知権限が「許可」になっていることを確認

# または DevToolsのコンソールで確認
Notification.permission
// "granted" が返ってくること
```

### 3. プッシュ通知購読確認

```bash
php artisan tinker

# 購読情報確認
User::find(1)->pushSubscriptions

# 結果例:
# Illuminate\Database\Eloquent\Collection {#...
#   all: [
#     NotificationChannels\WebPush\PushSubscription {#...
#       endpoint: "https://fcm.googleapis.com/fcm/send/...",
#       ...
#     },
#   ],
# }
```

### 4. 通知設定UI確認

```bash
# 1. ナビゲーションバーの「⚙️ 設定」をクリック
# 2. 「通知設定を管理」ボタンをクリック
# 3. /profile/notifications にアクセス
# 4. チェックボックスを切り替え
# 5. 「保存」ボタンをクリック
# 6. 「保存しました。」メッセージが2秒間表示されることを確認
```

### 5. NotificationSetting自動作成確認

```bash
php artisan tinker

# 新規ユーザー作成
$user = User::factory()->create([
    'email' => 'test-new@example.com',
    'password' => bcrypt('password')
]);

# NotificationSettingが自動作成されているか確認
$user->notificationSetting

# 結果:
# App\Models\NotificationSetting {#...
#   user_id: ...,
#   push_enabled: true,
#   task_assigned_enabled: true,
#   ...
# }
```

### 6. WebPushChannel動作確認

```bash
php artisan tinker

# テスト通知送信
$user = User::find(1);
$todo = Todo::find(1);
$assignedBy = User::find(2);

$user->notify(new \App\Notifications\TodoAssignedNotification($todo, $assignedBy));

# キューワーカーのログ確認
# [日時] Processing: Illuminate\Notifications\SendQueuedNotifications
# [日時] Processed:  Illuminate\Notifications\SendQueuedNotifications
```

---

## ⚠️ 既知の課題・制限事項

### 1. プッシュ通知の実際の配信

**状況**
- Laravel側の設定は完了
- プッシュ通知購読も作成されている
- WebPushChannelは正常に動作
- しかし、ブラウザに通知が表示されない

**原因**
- Firebase Cloud Messaging（FCM）との統合が必要
- 現在はVAPID認証のみで、FCMエンドポイントとの連携が未完成

**影響**
- 通知送信処理は成功するが、実際の配信は未検証
- データベースには通知レコードが保存される
- メール・ブロードキャストチャンネルは正常動作

**対策**
- FCM設定・デバッグが必要
- または他のプッシュサービス（OneSignal等）の検討

### 2. PWAアイコン

**状況**
- manifest.jsonのiconsが空配列

**影響**
- PWAインストール時のアイコンが表示されない
- 機能的には問題なし

**対策**
```bash
# 192x192、512x512のアイコン画像を作成
# public/ ディレクトリに配置
# manifest.jsonのiconsセクションを更新
```

### 3. Service Workerキャッシュ

**状況**
- faviconのみキャッシュ（404エラー回避のため）

**影響**
- オフライン対応が限定的
- CSS/JSファイルはキャッシュされない

**対策**
- 必要に応じてキャッシュ対象を拡張
- ただし、存在しないファイルを指定するとエラーになるため注意

---

## 🐛 実装中に修正したバグ

### 1. service-worker.js のタイポ（20箇所以上）
- CACHE_VERSON → CACHE_VERSION
- CACHE_ANME → CACHE_NAME
- /favison.co → /favicon.ico
- cache.keys() → caches.keys()
- Primise → Promise
- その他多数

### 2. TodoAssignedNotification
- 重複useステートメント削除
- "新しタスク" → "新しいタスク"

### 3. PushSubscriptionController
- `use Illuminate\Support\Facades\log;` → `Log`
- `auth()->id()` 修正

### 4. UserObserver
- `notificationSettings()` → `notificationSetting()`（単数形）

### 5. ProfileController
- `editNotiications` → `editNotifications`
- `$request()` → `$request->user()`
- `profile.notification` → `profile.notifications`

### 6. ステータス名不一致
- コントローラー: `notification-updated`
- ビュー: `notifications-updated`
- → `notifications-updated` に統一

---

## 🚀 次のステップ候補

### 優先度：高

**フェーズ19C: 他の通知タイプへのプッシュ通知追加**

現在、TodoAssignedNotificationのみWebPushChannel対応済みです。以下の通知にも追加を推奨：

1. **TodoCommentNotification**（コメント通知）
```php
// app/Notifications/TodoCommentNotification.php に追加
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

public function via($notifiable): array
{
    $setting = $notifiable->notificationSetting;
    $channels = ['database', 'broadcast'];
    
    if ($setting && $setting->comment_email_enabled) {
        $channels[] = 'mail';
    }
    
    if ($setting && $setting->push_enabled) {
        $channels[] = WebPushChannel::class;
    }
    
    return $channels;
}

public function toWebPush($notifiable): WebPushMessage
{
    return (new WebPushMessage)
        ->title('新しいコメント')
        ->body("{$this->comment->user->name}さんが「{$this->todo->title}」にコメントしました")
        ->icon('/favicon.ico')
        ->data([
            'todo_id' => $this->todo->id,
            'comment_id' => $this->comment->id,
            'url' => route('todos.edit', $this->todo),
        ])
        ->tag('todo-comment-' . $this->comment->id);
}
```

2. **TodoDeadlineNotification**（期限通知）
3. **WeeklyReportNotification**（週次レポート）

### 優先度：中

**PWAアイコン作成**
- 192x192、512x512のアイコン画像作成
- manifest.jsonに追加
- ホーム画面追加時の見栄え向上

**FCM統合デバッグ**
- Firebase Console設定
- FCMサーバーキー取得
- 実際のプッシュ通知配信テスト

### 優先度：低

**Service Workerキャッシュ拡張**
- CSS/JSファイルをキャッシュ対象に追加
- オフライン対応強化
- キャッシュ戦略の最適化

---

## 📚 技術情報

### Service Worker ライフサイクル

```javascript
// 1. インストール
self.addEventListener('install', (event) => {
  // キャッシュ作成
  event.waitUntil(caches.open(CACHE_NAME).then(...));
  self.skipWaiting(); // すぐにアクティブ化
});

// 2. アクティベート
self.addEventListener('activate', (event) => {
  // 古いキャッシュ削除
  event.waitUntil(caches.keys().then(...));
  self.clients.claim(); // すぐに制御開始
});

// 3. Fetch（リクエスト処理）
self.addEventListener('fetch', (event) => {
  // キャッシュファースト戦略
  event.respondWith(caches.match(...));
});

// 4. Push（プッシュ通知受信）
self.addEventListener('push', (event) => {
  // 通知表示
  event.waitUntil(self.registration.showNotification(...));
});

// 5. NotificationClick（通知クリック）
self.addEventListener('notificationclick', (event) => {
  // ページ遷移
  event.waitUntil(clients.openWindow(...));
});
```

### WebPushMessage API

```php
(new WebPushMessage)
    ->title('タイトル')                    // 必須
    ->body('本文')                         // 必須
    ->icon('/icon.png')                    // アイコン
    ->badge('/badge.png')                  // バッジアイコン
    ->data(['key' => 'value'])             // カスタムデータ
    ->tag('unique-tag')                    // 重複通知防止
    ->renotify()                           // 同じtagでも再通知
    ->requireInteraction()                 // ユーザー操作まで表示
    ->vibrate([200, 100, 200])             // バイブレーション
    ->actions([...])                       // アクションボタン
```

### 通知の流れ

```
1. ユーザーログイン
   ↓
2. Service Worker登録（app.js）
   ↓
3. 通知権限リクエスト（Notification.requestPermission）
   ↓
4. 許可された場合、プッシュ通知購読作成
   ↓
5. PushSubscriptionController::store() でDB保存
   ↓
6. タスク割り当て時、TodoAssignedNotification送信
   ↓
7. WebPushChannelが toWebPush() を呼び出し
   ↓
8. laravel-notification-channels/webpushがリクエスト送信
   ↓
9. (FCM経由でブラウザに配信) ← 現在ここが未完成
   ↓
10. Service Workerのpushイベント発火
   ↓
11. self.registration.showNotification() で通知表示
```

---

## 🔗 参考資料

### 公式ドキュメント
- [laravel-notification-channels/webpush](https://github.com/laravel-notification-channels/webpush)
- [Service Worker API - MDN](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API)
- [Push API - MDN](https://developer.mozilla.org/en-US/docs/Web/API/Push_API)
- [Web App Manifest - MDN](https://developer.mozilla.org/en-US/docs/Web/Manifest)
- [Notification API - MDN](https://developer.mozilla.org/en-US/docs/Web/API/Notifications_API)

### 実装ガイド
- [docs/PHASE_19B_IMPLEMENTATION_GUIDE.md](PHASE_19B_IMPLEMENTATION_GUIDE.md) - 詳細な実装手順

---

## 📞 サポート・問い合わせ

### トラブルシューティング

**通知が表示されない**
1. HTTPSでアクセスしているか確認
2. 通知権限が許可されているか確認
3. Service Workerが登録されているか確認（DevTools）
4. プッシュ通知購読が作成されているか確認（Tinker）
5. キューワーカーが起動しているか確認

**Service Workerが更新されない**
1. ハードリロード（Ctrl+Shift+R）
2. DevTools → Application → Service Workers → Unregister
3. ブラウザキャッシュクリア

**チェックボックスの変更が保存されない**
1. ネットワークタブでリクエスト確認
2. Laravel.logでエラー確認
3. CSRF トークンの有効性確認

---

**引継ぎ作成日**: 2026-04-28  
**実装完了日**: 2026-04-27  
**対象フェーズ**: 19B  
**前提条件**: フェーズ19A完了（メール通知強化）  
**次フェーズ**: フェーズ19C（他の通知タイプへのプッシュ通知追加）
