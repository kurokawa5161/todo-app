# フェーズ19B実装ガイド：プッシュ通知（PWA）

## 📋 実装概要

ブラウザプッシュ通知を実装し、PWA対応することで、ユーザーがブラウザを閉じていても通知を受け取れるようにします。

---

## 🎯 実装目標

- [x] Web Pushライブラリ導入
- [x] VAPID鍵生成・管理
- [x] Service Worker実装
- [x] PWA Manifest作成
- [x] プッシュ通知購読機能
- [x] 通知送信ロジック実装
- [x] NotificationSetting連携
- [x] UserObserverによる自動設定作成
- [x] 通知設定UI実装
- [x] ナビゲーション統合

---

## 📦 ステップ1: パッケージインストール

### Laravelパッケージ

```bash
composer require laravel-notification-channels/webpush
```

このパッケージは以下を提供します：
- VAPID鍵管理
- プッシュ通知チャンネル
- 購読管理テーブル

### NPMパッケージ（オプション）

```bash
npm install workbox-build --save-dev
```

---

## 🔑 ステップ2: VAPID鍵生成

### 鍵生成コマンド

```bash
php artisan vendor:publish --provider="NotificationChannels\WebPush\WebPushServiceProvider" --tag="config"

php artisan webpush:vapid
```

### .envに追加

```env
VAPID_PUBLIC_KEY=BKxxxxxxxxxxxxxxxxxxx
VAPID_PRIVATE_KEY=xxxxxxxxxxxxxxxxxxxxxx
VAPID_SUBJECT=mailto:is1101520@gmail.com
```

---

## 🗄️ ステップ3: データベース準備

### マイグレーション実行

```bash
php artisan vendor:publish --provider="NotificationChannels\WebPush\WebPushServiceProvider" --tag="migrations"

php artisan migrate
```

作成されるテーブル：
- `push_subscriptions` - プッシュ通知購読情報

### notification_settingsテーブル拡張

新規マイグレーション作成：

```bash
php artisan make:migration add_push_enabled_to_notification_settings_table
```

```php
// database/migrations/xxxx_add_push_enabled_to_notification_settings_table.php
public function up(): void
{
    Schema::table('notification_settings', function (Blueprint $table) {
        $table->boolean('push_enabled')->default(true)->after('comment_email_enabled');
    });
}

public function down(): void
{
    Schema::table('notification_settings', function (Blueprint $table) {
        $table->dropColumn('push_enabled');
    });
}
```

```bash
php artisan migrate
```

---

## 📝 ステップ4: Modelとリレーション設定

### app/Models/User.php

```php
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    use HasPushSubscriptions; // 追加

    // 既存のコード...
}
```

### app/Models/NotificationSetting.php

```php
protected $fillable = [
    'user_id',
    'reminder_days',
    'weekly_report_enabled',
    'task_assigned_enabled',
    'comment_email_enabled',
    'push_enabled', // 追加
    'weekly_report_day',
    'weekly_report_time',
];

protected $casts = [
    'reminder_days' => 'array',
    'weekly_report_enabled' => 'boolean',
    'task_assigned_enabled' => 'boolean',
    'comment_email_enabled' => 'boolean',
    'push_enabled' => 'boolean', // 追加
];
```

---

## 🔔 ステップ5: Notification修正

### app/Notifications/TodoAssignedNotification.php

```php
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TodoAssignedNotification extends Notification
{
    public function via($notifiable): array
    {
        $setting = $notifiable->notificationSetting;
        $channels = ['database', 'broadcast'];
        
        // メール通知
        if ($setting && $setting->task_assigned_enabled) {
            $channels[] = 'mail';
        }
        
        // プッシュ通知（追加）
        if ($setting && $setting->push_enabled) {
            $channels[] = WebPushChannel::class;
        }
        
        return $channels;
    }

    // 既存のメソッド...

    /**
     * Web Push通知の内容
     */
    public function toWebPush($notifiable): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('新しいタスクが割り当てられました')
            ->body("{$this->assignedBy->name}さんがタスク「{$this->todo->title}」を割り当てました")
            ->icon('/favicon.ico')
            ->badge('/badge-icon.png') // 通知バッジ用小アイコン（オプション）
            ->data([
                'todo_id' => $this->todo->id,
                'url' => route('todos.edit', $this->todo),
                'timestamp' => now()->toIso8601String(),
            ])
            ->tag('todo-assigned-' . $this->todo->id) // 重複通知を防ぐ
            ->renotify(); // 同じtagでも再通知
    }
}
```

### app/Notifications/TodoCommentNotification.php

同様にWebPushメソッド追加：

```php
public function toWebPush($notifiable): WebPushMessage
{
    return (new WebPushMessage)
        ->title('新しいコメント')
        ->body("{$this->comment->user->name}さんが「{$this->todo->title}」にコメントしました")
        ->icon('/favicon.ico')
        ->data([
            'todo_id' => $this->todo->id,
            'comment_id' => $this->comment->id,
            'url' => route('todos.edit', $this->todo) . '#comment-' . $this->comment->id,
        ])
        ->tag('todo-comment-' . $this->comment->id);
}
```

---

## 🌐 ステップ6: Service Worker作成

### public/service-worker.js

```javascript
// Service Workerバージョン（更新時にインクリメント）
const CACHE_VERSION = 'v1.0.0';
const CACHE_NAME = `todo-app-${CACHE_VERSION}`;

// キャッシュするリソース
const urlsToCache = [
  '/',
  '/css/app.css',
  '/js/app.js',
  '/favicon.ico',
];

// インストール時：キャッシュ作成
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(urlsToCache);
    })
  );
  self.skipWaiting(); // すぐにアクティブ化
});

// アクティベート時：古いキャッシュ削除
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => name !== CACHE_NAME)
          .map((name) => caches.delete(name))
      );
    })
  );
  self.clients.claim();
});

// Fetch時：キャッシュファースト戦略
self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request).then((response) => {
      return response || fetch(event.request);
    })
  );
});

// プッシュ通知受信時
self.addEventListener('push', (event) => {
  if (!event.data) {
    return;
  }

  const data = event.data.json();
  const options = {
    body: data.body,
    icon: data.icon || '/favicon.ico',
    badge: data.badge || '/badge-icon.png',
    data: data.data,
    tag: data.tag,
    renotify: data.renotify || false,
    requireInteraction: false, // 自動で消える
    vibrate: [200, 100, 200], // バイブレーションパターン（モバイル）
  };

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

// 通知クリック時：該当ページを開く
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const url = event.notification.data?.url || '/';

  event.waitUntil(
    clients.matchAll({ type: 'window' }).then((clientList) => {
      // 既に開いているタブがあればフォーカス
      for (const client of clientList) {
        if (client.url === url && 'focus' in client) {
          return client.focus();
        }
      }
      // なければ新しいタブで開く
      if (clients.openWindow) {
        return clients.openWindow(url);
      }
    })
  );
});
```

---

## 📱 ステップ7: PWA Manifest作成

### public/manifest.json

```json
{
  "name": "Laravel Todo App",
  "short_name": "Todo App",
  "description": "高機能タスク管理アプリケーション",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#4f46e5",
  "orientation": "portrait-primary",
  "icons": [
    {
      "src": "/icon-192x192.png",
      "sizes": "192x192",
      "type": "image/png",
      "purpose": "any maskable"
    },
    {
      "src": "/icon-512x512.png",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "any maskable"
    }
  ],
  "screenshots": [
    {
      "src": "/screenshot-desktop.png",
      "sizes": "1280x720",
      "type": "image/png",
      "form_factor": "wide"
    },
    {
      "src": "/screenshot-mobile.png",
      "sizes": "750x1334",
      "type": "image/png",
      "form_factor": "narrow"
    }
  ]
}
```

**注意**: アイコン画像（192x192, 512x512）を `public/` に配置してください。

---

## 🎨 ステップ8: フロントエンド実装

### resources/views/layouts/app.blade.php（head内に追加）

```html
<head>
    <!-- 既存のコード... -->

    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    
    <!-- テーマカラー -->
    <meta name="theme-color" content="#4f46e5">
    
    <!-- Apple Touch Icon -->
    <link rel="apple-touch-icon" href="/icon-192x192.png">
    
    <!-- VAPID公開鍵 -->
    <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
</head>
```

### resources/js/app.js（末尾に追加）

```javascript
// Service Worker登録
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker
      .register('/service-worker.js')
      .then((registration) => {
        console.log('✅ Service Worker registered:', registration);
      })
      .catch((error) => {
        console.error('❌ Service Worker registration failed:', error);
      });
  });
}

// プッシュ通知購読
async function subscribeToPush() {
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
    console.warn('⚠️ Push notifications not supported');
    return;
  }

  try {
    const registration = await navigator.serviceWorker.ready;
    const vapidPublicKey = document.querySelector('meta[name="vapid-public-key"]').content;

    // 既存の購読を確認
    let subscription = await registration.pushManager.getSubscription();

    if (!subscription) {
      // 新規購読
      subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
      });

      // サーバーに購読情報を送信
      await fetch('/push-subscriptions', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(subscription),
      });

      console.log('✅ Push subscription created');
    }
  } catch (error) {
    console.error('❌ Push subscription failed:', error);
  }
}

// VAPID鍵変換ヘルパー
function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
}

// 通知権限リクエスト
async function requestNotificationPermission() {
  if (!('Notification' in window)) {
    console.warn('⚠️ This browser does not support notifications');
    return;
  }

  if (Notification.permission === 'default') {
    const permission = await Notification.requestPermission();
    if (permission === 'granted') {
      await subscribeToPush();
    }
  } else if (Notification.permission === 'granted') {
    await subscribeToPush();
  }
}

// ページロード時に実行（ログインユーザーのみ）
if (document.querySelector('meta[name="vapid-public-key"]')) {
  requestNotificationPermission();
}
```

---

## 🛣️ ステップ9: ルート・コントローラー追加

### routes/web.php

```php
use App\Http\Controllers\PushSubscriptionController;

Route::middleware(['auth'])->group(function () {
    // 既存のルート...

    // プッシュ通知購読管理
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('/push-subscriptions/{subscription}', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
});
```

### app/Http/Controllers/PushSubscriptionController.php（新規作成）

```php
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
            'keys.auth' => 'required|string',
        ]);

        // laravel-notification-channels/webpushが自動で処理
        auth()->user()->updatePushSubscription(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth']
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
            'endpoint' => $endpoint,
        ]);

        return response()->json(['message' => 'Subscription deleted']);
    }
}
```

---

## ⚙️ ステップ10: 設定ファイル確認

### config/webpush.php（自動生成済み）

```php
return [
    'vapid' => [
        'subject' => env('VAPID_SUBJECT'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],
];
```

---

## 🧪 ステップ11: テスト実装

### tests/Feature/PushNotificationTest.php（新規作成）

```php
<?php

use App\Models\User;
use App\Models\Todo;
use App\Notifications\TodoAssignedNotification;
use Illuminate\Support\Facades\Notification;

it('can subscribe to push notifications', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/push-subscriptions', [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/xxx',
        'keys' => [
            'p256dh' => 'BKxxxxxxxxxxxxxxxxxxx',
            'auth' => 'xxxxxxxxxxxxxxxxxxxxxx',
        ],
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('push_subscriptions', [
        'subscribable_id' => $user->id,
        'subscribable_type' => User::class,
    ]);
});

it('sends push notification when task is assigned', function () {
    Notification::fake();

    $user = User::factory()->create();
    $assignedUser = User::factory()->create();
    $todo = Todo::factory()->create(['user_id' => $user->id]);

    // 担当者設定でプッシュ通知が送信されることを確認
    $assignedUser->notify(new TodoAssignedNotification($todo, $user));

    Notification::assertSentTo($assignedUser, TodoAssignedNotification::class);
});
```

```bash
php artisan test --filter=PushNotificationTest
```

---

## 🔍 ステップ12: 動作確認

### 1. Service Worker登録確認

1. ブラウザで `http://todo-app.test` にアクセス
2. DevTools → Application → Service Workers
3. `service-worker.js` が登録されていることを確認

### 2. 通知権限確認

1. ブラウザのアドレスバー左側のアイコンをクリック
2. 通知権限が「許可」になっていることを確認

### 3. プッシュ通知テスト

```bash
php artisan tinker
```

```php
$user = User::find(1);
$todo = Todo::find(1);
$assignedBy = User::find(2);

$user->notify(new \App\Notifications\TodoAssignedNotification($todo, $assignedBy));
```

ブラウザに通知が表示されることを確認。

---

## 📊 ステップ13: NotificationSetting自動作成

### app/Observers/UserObserver.php（新規作成）

```php
<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * ユーザー作成時にNotificationSettingを自動作成
     */
    public function created(User $user): void
    {
        $user->notificationSetting()->create([
            'reminder_days' => [1, 3, 7],
            'weekly_report_enabled' => true,
            'task_assigned_enabled' => true,
            'comment_email_enabled' => true,
            'push_enabled' => true,
        ]);
    }
}
```

### app/Providers/AppServiceProvider.php

```php
use App\Models\User;
use App\Observers\UserObserver;

public function boot(): void
{
    User::observe(UserObserver::class); // 追加
}
```

### テスト

```php
it('creates notification setting when user is created', function () {
    $user = User::factory()->create();

    $this->assertDatabaseHas('notification_settings', [
        'user_id' => $user->id,
        'push_enabled' => true,
    ]);
});
```

---

## 🎛️ ステップ14: 通知設定UI（オプション）

### resources/views/settings/notifications.blade.php（新規作成）

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            通知設定
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('settings.notifications.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- プッシュ通知 -->
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="push_enabled" value="1"
                                    {{ $setting->push_enabled ? 'checked' : '' }}
                                    class="rounded border-gray-300">
                                <span class="ml-2">プッシュ通知を有効にする</span>
                            </label>
                        </div>

                        <!-- タスク割り当て通知 -->
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="task_assigned_enabled" value="1"
                                    {{ $setting->task_assigned_enabled ? 'checked' : '' }}
                                    class="rounded border-gray-300">
                                <span class="ml-2">タスク割り当て通知</span>
                            </label>
                        </div>

                        <!-- コメント通知 -->
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="comment_email_enabled" value="1"
                                    {{ $setting->comment_email_enabled ? 'checked' : '' }}
                                    class="rounded border-gray-300">
                                <span class="ml-2">コメント通知（メール）</span>
                            </label>
                        </div>

                        <!-- 週次レポート -->
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="weekly_report_enabled" value="1"
                                    {{ $setting->weekly_report_enabled ? 'checked' : '' }}
                                    class="rounded border-gray-300">
                                <span class="ml-2">週次レポート</span>
                            </label>
                        </div>

                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">
                            保存
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

---

## 🚀 ステップ15: デプロイ前チェックリスト

- [x] VAPID鍵が.envに設定されている
- [x] Service Workerが正しく登録される
- [x] manifest.jsonのアイコンパスが正しい
- [x] 通知権限リクエストが動作する
- [x] プッシュ通知購読が作成される
- [x] 通知クリックで正しいページに遷移する（実装済み）
- [x] NotificationSettingで通知ON/OFF切替できる
- [x] キューワーカーが起動している
- [x] HTTPSで動作確認（Laravel Herd: `herd secure todo-app`）

---

## 🐛 トラブルシューティング

### 通知が届かない

1. **Service Worker登録確認**
   - DevTools → Application → Service Workers

2. **購読情報確認**
   ```bash
   php artisan tinker
   User::find(1)->pushSubscriptions
   ```

3. **ログ確認**
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **キューワーカー確認**
   ```bash
   php artisan queue:work --once
   ```

### HTTPSエラー

- **原因**: Service WorkerとWeb PushはHTTPS必須（localhost除く）
- **対策**: 開発環境では`http://localhost`または`http://127.0.0.1`を使用

### CSPエラー

`config/security-headers.php` で `connect-src` に `https://fcm.googleapis.com` 追加

---

## 📚 参考資料

- [laravel-notification-channels/webpush](https://github.com/laravel-notification-channels/webpush)
- [Service Worker API - MDN](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API)
- [Push API - MDN](https://developer.mozilla.org/en-US/docs/Web/API/Push_API)
- [Web App Manifest - MDN](https://developer.mozilla.org/en-US/docs/Web/Manifest)

---

## ✅ 実装完了報告

**実装日**: 2026-04-27  
**ステータス**: 完了

### 実装された機能

1. **プッシュ通知システム**
   - VAPID鍵生成・設定（npx web-push使用）
   - Service Worker登録（public/service-worker.js）
   - プッシュ通知購読管理（PushSubscriptionController）
   - WebPushChannel統合（TodoAssignedNotification）

2. **PWA対応**
   - manifest.json作成
   - Apple Touch Icon設定
   - テーマカラー設定

3. **自動設定作成**
   - UserObserverによるNotificationSetting自動作成
   - 新規ユーザー登録時にデフォルト設定を自動生成

4. **通知設定UI**
   - 通知設定画面実装（/profile/notifications）
   - 4種類の通知ON/OFF制御
   - 保存成功メッセージ表示
   - ナビゲーションメニュー統合

### 発生した問題と解決策

#### 1. VAPID鍵生成エラー（Windows OpenSSL）
- **問題**: `php artisan webpush:vapid` がOpenSSL設定エラーで失敗
- **解決**: `npx web-push generate-vapid-keys` を使用して鍵生成

#### 2. HTTPS要件
- **問題**: HTTPではService WorkerとPush APIが動作しない
- **解決**: `herd secure todo-app` でHTTPSを有効化

#### 3. 通知権限拒否
- **問題**: ブラウザで通知権限が拒否され、設定変更不可
- **解決**: DevTools → Application → Storage → Clear site data で解決

#### 4. Service Workerキャッシュエラー
- **問題**: 存在しないファイルのキャッシュで404エラー
- **解決**: `urlsToCache`を`['/favicon.ico']`のみに簡素化

#### 5. 複数のタイポ修正
- **service-worker.js**: CACHE_VERSON → CACHE_VERSION など20箇所以上
- **TodoAssignedNotification.php**: 重複useステートメント、"新しタスク"
- **PushSubscriptionController.php**: `log` → `Log`、`auth()->id()->deletePushSubscription()`
- **UserObserver.php**: `notificationSettings()` → `notificationSetting()`
- **ProfileController.php**: `editNotiications`、`$request()`、`profile.notification`

#### 6. ステータス名不一致
- **問題**: コントローラー `'notification-updated'` vs ビュー `'notifications-updated'`
- **解決**: コントローラーを `'notifications-updated'` に統一

### 実装の変更点

1. **Service Workerキャッシュ戦略**
   - 当初案: 複数の静的ファイルをキャッシュ
   - 実装: faviconのみキャッシュ（404エラー回避）

2. **manifest.json**
   - 当初案: 192x192, 512x512アイコン
   - 実装: アイコン配列を空に設定（後で追加可能）

3. **通知設定UI**
   - プロフィールページに通知設定セクション追加
   - ナビゲーションバーに「⚙️ 設定」リンク追加

### テスト結果

- ✅ Service Worker登録成功
- ✅ プッシュ通知購読作成成功（2 subscriptions in DB）
- ✅ 通知権限付与成功
- ✅ NotificationSetting自動作成成功
- ✅ 通知設定UI動作確認（チェックボックス切り替え・保存）
- ✅ 保存成功メッセージ表示確認
- ✅ **ブラウザ通知表示成功**（2026-04-28追加修正完了）
  - Chrome（FCM経由）で動作確認
  - Edge（WNS経由）で動作確認
  - 実際のユーザー操作でテスト成功

### 追加修正（2026-04-28）

フェーズ19B実装後、以下の問題を解決し、プッシュ通知を完全に動作させました：

#### 1. Content Encoding設定
- **問題**: `content_encoding` が空でプッシュ通知が送信できない
- **解決**: `PushSubscriptionController` に `aes128gcm` を追加

#### 2. SSL証明書エラー（Windows環境）
- **問題**: cURL error 60 - SSL証明書検証エラー
- **解決**: CA証明書バンドルをダウンロードし、`php.ini` で設定
  ```ini
  curl.cainfo = "C:/Users/is110/cacert.pem"
  ```

#### 3. デバッグ・動作確認
- Service Workerにデバッグログ追加
- pushイベントの発火確認
- 動作確認後、デバッグログ削除
- バージョン更新: v1.0.0 → v1.0.2

### 追加実装（2026-04-28）

#### PWAアイコン作成・追加
- ✅ SVGアイコン作成（`/icons/icon.svg`）
- ✅ manifest.json更新
- ✅ PWAインストールプロンプト動作確認

#### Service Workerキャッシュ拡張
- ✅ 静的リソースのプリキャッシュ（manifest.json、icon.svg）
- ✅ 動的キャッシュ実装（CSS、JS、画像）
- ✅ キャッシュファースト戦略
- ✅ バージョン更新: v1.0.4

### 既知の制限事項

- **broadcastチャンネル**: Reverbサーバー起動まで無効化中（接続不安定のため実装見送り）

### 次のステップ

- ✅ **フェーズ19C完了**: 他の通知タイプへのプッシュ通知追加（2026-04-28完了）
- ✅ **PWAアイコン作成・追加**（2026-04-28完了）
- ✅ **Service Workerキャッシュ拡張**（2026-04-28完了）
- broadcastチャンネルの有効化（Reverbサーバー起動後・優先度低）

---

**作成日**: 2026-04-27  
**初回完了日**: 2026-04-27  
**追加修正完了日**: 2026-04-28（プッシュ通知動作確認完了）  
**完全完了日**: 2026-04-28（PWAアイコン・Service Workerキャッシュ拡張完了）  
**対象フェーズ**: 19B  
**前提条件**: フェーズ19A完了（メール通知強化）  
**実装者**: User + Claude Code
