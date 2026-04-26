# アーキテクチャ設計書

## システム概要

Laravel Todo Appは、MVC + Service層を持つモノリシックアーキテクチャのWebアプリケーションです。

---

## アーキテクチャパターン

### 1. レイヤー構造

```
┌─────────────────────────────────────────┐
│           Presentation Layer            │
│  (Blade Views, API Resources, Routes)   │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────▼───────────────────────┐
│          Application Layer              │
│   (Controllers, Middleware, Requests)   │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────▼───────────────────────┐
│           Domain Layer                  │
│  (Models, Policies, Events, Services)   │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────▼───────────────────────┐
│        Infrastructure Layer             │
│  (Database, Cache, External APIs)       │
└─────────────────────────────────────────┘
```

### 2. MVCパターン + Service層

- **Model**: データアクセス、ビジネスロジック（一部）
- **View**: Blade テンプレート、JSON レスポンス
- **Controller**: リクエスト処理、レスポンス返却
- **Service**: 複雑なビジネスロジック（例: GitHubService）

---

## ディレクトリ構造とレイヤーマッピング

### Presentation Layer

```
resources/views/          # Blade テンプレート
├── todos/               # Todo関連ビュー
├── teams/               # チーム関連ビュー
├── dashboard.blade.php  # 統計ダッシュボード
└── layouts/             # レイアウトテンプレート

app/Http/Resources/       # API レスポンス整形
└── TodoResource.php
```

### Application Layer

```
app/Http/Controllers/     # コントローラー
├── TodoController.php
├── TeamController.php
├── DashboardController.php
├── Api/                 # API コントローラー
│   └── TodoController.php
└── Auth/                # 認証コントローラー

app/Http/Middleware/      # ミドルウェア
├── LogApiRequest.php     # APIログ記録
└── SecurityHeaders.php   # セキュリティヘッダー

app/Http/Requests/        # バリデーション
└── TodoRequest.php

routes/                   # ルーティング
├── web.php
├── api.php
├── channels.php
└── console.php
```

### Domain Layer

```
app/Models/               # ドメインモデル
├── Todo.php
├── User.php
├── Team.php
├── Category.php
├── Tag.php
└── Comment.php

app/Policies/             # 認可ロジック
├── TodoPolicy.php
├── CategoryPolicy.php
└── TeamPolicy.php

app/Events/               # ドメインイベント
├── TodoCreated.php
├── TodoUpdated.php
└── TodoDeleted.php

app/Notifications/        # 通知
└── TodoSlackNotification.php

app/Services/             # ドメインサービス
└── GitHubService.php
```

### Infrastructure Layer

```
database/
├── migrations/           # データベーススキーマ
└── seeders/             # 初期データ

config/
├── database.php         # DB設定
├── cache.php            # キャッシュ設定
├── services.php         # 外部サービス設定
└── session.php          # セッション設定
```

---

## データフロー

### 1. Web リクエストフロー

```
Browser
   │
   ▼
Route (web.php)
   │
   ▼
Middleware
   │  ├─ Authenticate
   │  ├─ SecurityHeaders
   │  └─ VerifyCsrfToken
   │
   ▼
Controller (TodoController)
   │  ├─ Authorization (Policy)
   │  └─ Validation (TodoRequest)
   │
   ▼
Model (Todo)
   │  ├─ Query Builder / Eloquent
   │  └─ Database
   │
   ▼
Event (TodoCreated)
   │  ├─ Notification
   │  └─ Broadcast
   │
   ▼
View (Blade)
   │
   ▼
Browser
```

### 2. API リクエストフロー

```
API Client
   │
   ▼
Route (api.php)
   │
   ▼
Middleware
   │  ├─ Sanctum Auth
   │  ├─ Throttle (Rate Limit)
   │  └─ LogApiRequest
   │
   ▼
API Controller (Api\TodoController)
   │  ├─ Authorization (Policy)
   │  └─ Validation (TodoRequest)
   │
   ▼
Model (Todo)
   │  └─ Database
   │
   ▼
API Resource (TodoResource)
   │
   ▼
JSON Response
   │
   ▼
API Client
```

### 3. リアルタイム通知フロー

```
User Action (Todo作成)
   │
   ▼
Controller
   │
   ▼
Event::dispatch(TodoCreated)
   │
   ▼
EventListener
   │  ├─ Notification
   │  │     └─ Database
   │  └─ Broadcast
   │        └─ Reverb (WebSocket)
   │
   ▼
Frontend (JavaScript)
   │  └─ Echo.channel().listen()
   │
   ▼
UI Update (Toast通知)
```

---

## データベース設計

### ER図（主要テーブル）

```
┌──────────┐       ┌──────────┐       ┌──────────┐
│  users   │──────<│  todos   │>──────│categories│
└──────────┘       └──────────┘       └──────────┘
     │                  │ │
     │                  │ └──────────┐
     │                  │            │
     │                  ▼            ▼
     │             ┌──────────┐ ┌──────────┐
     │             │ comments │ │todo_tag  │
     │             └──────────┘ └──────────┘
     │                              │
     │                              ▼
     │                         ┌──────────┐
     │                         │   tags   │
     │                         └──────────┘
     │
     └────────<┌──────────┐
               │team_user │
               └──────────┘
                    │
                    ▼
               ┌──────────┐
               │  teams   │
               └──────────┘
```

### リレーション設計

#### User (1) ─ (N) Todo
- `todos.user_id` → `users.id`
- User hasMany Todos
- Todo belongsTo User

#### Todo (N) ─ (1) Category
- `todos.category_id` → `categories.id`
- Todo belongsTo Category
- Category hasMany Todos

#### Todo (N) ─ (N) Tag
- 中間テーブル: `todo_tag`
- Todo belongsToMany Tags
- Tag belongsToMany Todos

#### Todo (1) ─ (N) Comment
- `comments.todo_id` → `todos.id`
- Todo hasMany Comments
- Comment belongsTo Todo

#### Todo (1) ─ (N) Todo（親子関係）
- `todos.parent_id` → `todos.id`
- Todo hasMany Children (自己参照)
- Todo belongsTo Parent (自己参照)

#### Team (N) ─ (N) User
- 中間テーブル: `team_user`
- Team belongsToMany Users
- User belongsToMany Teams

#### Team (1) ─ (N) Todo
- `todos.team_id` → `teams.id`
- Team hasMany Todos
- Todo belongsTo Team

---

## 認証・認可設計

### 認証フロー

#### Web認証（Laravel Breeze）

```
1. ユーザーがログインフォーム送信
   ↓
2. AuthenticatedSessionController::store()
   ├─ Throttle Middleware (5回/分制限)
   ├─ 認証情報検証
   └─ セッション作成（暗号化、120分タイムアウト）
   ↓
3. ダッシュボードへリダイレクト
```

#### API認証（Laravel Sanctum）

```
1. クライアントがログインリクエスト (POST /api/login)
   ↓
2. 認証情報検証
   ↓
3. Personal Access Token生成
   ↓
4. トークン返却
   ↓
5. 以降のリクエストにBearerトークンを付与
   ↓
6. Sanctum Middlewareがトークン検証
```

### 認可設計（Policy）

#### TodoPolicy

```php
public function view(User $user, Todo $todo): bool
{
    // 個人Todo: 自分のTodoのみ閲覧可
    if (!$todo->team_id) {
        return $user->id === $todo->user_id;
    }
    
    // チームTodo: チームメンバーなら閲覧可
    return $user->teams()->where('teams.id', $todo->team_id)->exists();
}

public function update(User $user, Todo $todo): bool
{
    // 個人Todoは作成者のみ編集可
    return $user->id === $todo->user_id;
}
```

#### TeamPolicy

```php
public function createTeamTodo(User $user, Team $team): bool
{
    // チームメンバーならTodo作成可
    return $team->users()->where('users.id', $user->id)->exists();
}

public function updateTeamTodo(User $user, Team $team, Todo $todo): bool
{
    // チームメンバー かつ Todoの作成者なら編集可
    return $team->users()->where('users.id', $user->id)->exists()
        && $todo->user_id === $user->id;
}
```

---

## キャッシュ戦略

### キャッシュ対象

1. **ユーザーのカテゴリ一覧**
   - キー: `user_{user_id}_categories`
   - TTL: 3600秒（1時間）
   - 無効化: カテゴリ作成・更新・削除時

2. **ユーザーのタグ一覧**
   - キー: `user_{user_id}_tags`
   - TTL: 3600秒（1時間）
   - 無効化: タグ作成・更新・削除時

3. **保存済み検索条件**
   - キー: `user_{user_id}_saved_searches`
   - TTL: 3600秒（1時間）
   - 無効化: 検索条件保存・削除時

### キャッシュパターン

```php
// キャッシュ取得・生成
$categories = Cache::remember(
    'user_' . auth()->id() . '_categories',
    3600,
    function () {
        return auth()->user()->categories()
            ->orderBy('created_at', 'asc')
            ->get();
    }
);

// キャッシュ削除
Cache::forget('user_' . auth()->id() . '_categories');
```

---

## イベント駆動設計

### イベントフロー

```
Controller Action
   │
   ▼
event(new TodoCreated($todo))
   │
   ├──▶ Notification
   │     └─ database チャンネル
   │         └─ notifications テーブルに保存
   │
   ├──▶ Broadcast
   │     └─ Reverb (WebSocket)
   │         └─ Frontend Echo.listen()
   │
   └──▶ Log
         └─ laravel.log
```

### 実装例

```php
// Controller
event(new TodoCreated($todo));
$todo->user->notify(new TodoSlackNotification($todo, 'created'));

// Event
class TodoCreated implements ShouldBroadcast
{
    public function broadcastOn(): array
    {
        return [
            new Channel('todos'),
            new PrivateChannel('user.' . $this->todo->user_id),
        ];
    }
}

// Frontend
Echo.private(`user.${userId}`)
    .listen('TodoCreated', (e) => {
        // トースト通知表示
    });
```

---

## API設計

### RESTful API原則

| メソッド | エンドポイント | 説明 |
|---------|---------------|------|
| GET | /api/todos | Todo一覧取得 |
| GET | /api/todos/{id} | Todo詳細取得 |
| POST | /api/todos | Todo作成 |
| PUT | /api/todos/{id} | Todo更新 |
| DELETE | /api/todos/{id} | Todo削除 |
| PATCH | /api/todos/{id}/toggle | 完了切替 |
| PATCH | /api/todos/{id}/pin | ピン留め切替 |

### レスポンス形式（API Resource）

```json
{
  "data": {
    "id": 1,
    "title": "タスク名",
    "content": "タスク説明",
    "start_date": "2026-04-01T00:00:00.000000Z",
    "end_date": "2026-04-30T23:59:59.000000Z",
    "completed_at": null,
    "priority": 2,
    "is_pinned": false,
    "category": {
      "id": 1,
      "name": "仕事"
    },
    "tags": [
      {"id": 1, "name": "重要"},
      {"id": 2, "name": "緊急"}
    ],
    "created_at": "2026-04-26T10:00:00.000000Z",
    "updated_at": "2026-04-26T10:00:00.000000Z"
  }
}
```

### エラーレスポンス

```json
{
  "message": "Unauthenticated.",
  "errors": {
    "title": ["タイトルは必須です。"]
  }
}
```

---

## セキュリティ設計

### 多層防御（Defense in Depth）

```
Layer 1: ネットワーク層
├─ HTTPS（SSL/TLS）
└─ Firewall

Layer 2: アプリケーション層
├─ CSRF保護（@csrf）
├─ XSS対策（Bladeエスケープ）
├─ SQLインジェクション対策（Eloquent ORM）
└─ セキュリティヘッダー（CSP, X-Frame-Options等）

Layer 3: 認証・認可層
├─ レート制限（Throttle）
├─ セッション暗号化・タイムアウト
├─ Policy（認可）
└─ Mass Assignment保護（$fillable）

Layer 4: データ層
├─ 入力バリデーション（TodoRequest）
├─ ファイルアップロード検証
└─ データベース暗号化（option）
```

### セキュリティヘッダー

```php
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self' ws: wss:;

X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
```

---

## パフォーマンス設計

### N+1問題対策

```php
// ❌ N+1問題あり
$todos = Todo::all();
foreach ($todos as $todo) {
    echo $todo->category->name;  // N回クエリ発行
}

// ✅ Eager Loading
$todos = Todo::with(['category', 'tags', 'children'])->get();
foreach ($todos as $todo) {
    echo $todo->category->name;  // 1回のクエリ
}
```

### クエリ最適化

```php
// カウント取得の最適化
$counts = auth()->user()->todos()->selectRaw(
    'COUNT(*) as total,
    COUNT(CASE WHEN completed_at IS NULL THEN 1 END) as active,
    COUNT(CASE WHEN completed_at IS NOT NULL THEN 1 END) as done'
)->whereNull('parent_id')->first();
```

### ページネーション

```php
// 動的per_page
$perPage = $request->input('per_page', 10);
$items = $query->paginate($perPage)
    ->appends($request->except('page'));  // クエリパラメータ保持
```

---

## 外部サービス連携設計

### 1. GitHub連携

#### Webhook受信フロー

```
GitHub
   │ Issue opened
   ▼
POST /webhook/github
   │ X-GitHub-Event: issues
   │ X-Hub-Signature-256: xxx
   ▼
GitHubWebhookController
   │ ├─ イベント検証
   │ └─ ペイロード解析
   ▼
GitHubService::createTodoFromIssue()
   │ ├─ Todoモデル作成
   │ │   ├─ title ← issue.title
   │ │   ├─ content ← issue.body
   │ │   ├─ priority ← labels判定
   │ │   └─ github_issue_url ← issue.html_url
   │ └─ ログ記録
   ▼
Todo作成完了
```

#### Issue閉鎖フロー

```
User (Todo完了)
   ▼
TodoController::toggle()
   │ completed_at = now()
   ▼
GitHubService::closeIssue()
   │ ├─ IssueURL解析
   │ ├─ GitHub API呼び出し
   │ │   PATCH /repos/{owner}/{repo}/issues/{number}
   │ │   { "state": "closed" }
   │ └─ ログ記録
   ▼
GitHub Issue状態更新
```

### 2. Slack連携（未完了）

```
Controller Action
   ▼
$user->notify(new TodoSlackNotification($todo, 'created'))
   ▼
via() → ['database']  # 現在はDBのみ
   ▼
notifications テーブルに保存

# 今後の実装
via() → ['slack', 'database']
   ▼
SlackAPI経由で通知送信
```

### 3. Google Calendar連携（.icsエクスポート）

```
User (カレンダーエクスポートボタン)
   ▼
GET /todos/{id}/export-calendar
   ▼
TodoController::exportCalendar()
   │ ├─ eluceo/ical使用
   │ ├─ Eventオブジェクト生成
   │ └─ .icsファイル生成
   ▼
Content-Type: text/calendar
Content-Disposition: attachment; filename="todo-1.ics"
   ▼
ブラウザダウンロード
   ▼
ユーザーがGoogleカレンダーにインポート
```

---

## テスト戦略

### テストピラミッド

```
        ╱╲
       ╱E2E╲          少数（手動・自動）
      ╱──────╲
     ╱ Feature╲        中程度（自動）
    ╱──────────╲
   ╱   Unit     ╲      多数（自動）
  ╱──────────────╲
```

### テスト種別

#### 1. Unit Test
- **対象**: Model、Service、Policy
- **目的**: 単一クラス・メソッドの動作検証
- **例**: GitHubService::getPriorityFromLabels()

```php
it('returns priority 1 for high label', function () {
    $service = new GitHubService();
    $priority = $service->getPriorityFromLabels([
        ['name' => 'high']
    ]);
    expect($priority)->toBe(1);
});
```

#### 2. Feature Test
- **対象**: Controller、API、認証フロー
- **目的**: エンドポイント全体の動作検証
- **例**: Todo CRUD操作

```php
it('can create a todo', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)->post('/todos', [
        'title' => 'テストTodo',
        'end_date' => now()->addDays(7),
    ]);
    
    $response->assertRedirect('/todos');
    $this->assertDatabaseHas('todos', [
        'title' => 'テストTodo',
        'user_id' => $user->id,
    ]);
});
```

#### 3. Browser Test（未実装）
- **対象**: UI/UXフロー
- **ツール**: Laravel Dusk
- **例**: ログイン → Todo作成 → 完了切替

---

## デプロイ戦略（未実装）

### 環境構成

```
┌──────────────┐
│ Development  │  ← ローカル（Herd/Docker）
└──────────────┘
        │
        ▼
┌──────────────┐
│  Staging     │  ← テスト環境（AWS/GCP）
└──────────────┘
        │
        ▼
┌──────────────┐
│ Production   │  ← 本番環境（AWS/GCP）
└──────────────┘
```

### CI/CDパイプライン

```
GitHub Push (main)
   │
   ▼
GitHub Actions
   ├─ Composer Install
   ├─ NPM Install
   ├─ PHPUnit/Pest
   ├─ PHPStan
   └─ PHP CS Fixer
   │
   ▼ (成功時)
Auto Deploy to Staging
   │
   ▼ (手動承認)
Deploy to Production
```

---

## 監視・ログ設計（未実装）

### ログレベル

```
Emergency → システムダウン
Alert     → 即座の対応が必要
Critical  → 重大なエラー
Error     → エラー（復旧可能）
Warning   → 警告
Notice    → 通常の重要イベント
Info      → 情報メッセージ
Debug     → デバッグ情報
```

### ログ出力先

- **開発**: `storage/logs/laravel.log`
- **本番（予定）**: CloudWatch Logs / ELK Stack

### 監視項目（予定）

- レスポンスタイム（P50, P95, P99）
- エラー率
- データベースクエリ時間
- キャッシュヒット率
- CPU/メモリ使用率

---

## スケーラビリティ設計

### 水平スケーリング対応

```
┌──────────────┐
│ Load Balancer│
└───────┬──────┘
        │
   ┌────┴────┐
   ▼         ▼
┌─────┐   ┌─────┐
│App 1│   │App 2│  ← アプリケーションサーバー（複数）
└──┬──┘   └──┬──┘
   └────┬────┘
        ▼
   ┌─────────┐
   │  Redis  │  ← セッション・キャッシュ共有
   └─────────┘
        │
        ▼
   ┌─────────┐
   │  MySQL  │  ← データベース（Read Replica対応）
   └─────────┘
```

### ボトルネック対策（今後）

1. **データベース**
   - Read Replica導入
   - インデックス最適化
   - パーティショニング

2. **キャッシュ**
   - Redis導入
   - クエリキャッシュ拡充

3. **静的ファイル**
   - CDN導入（CloudFlare/CloudFront）
   - 画像最適化（WebP変換）

4. **アプリケーション**
   - Laravel Octane（option）
   - Job Queue（非同期処理）

---

## 技術的負債

### 現在の負債

1. **Redis未導入**
   - 影響: キャッシュがファイルベース、スケーラビリティ低下
   - 対策: フェーズ25で導入予定

2. **テストカバレッジ不足**
   - 影響: リグレッションリスク
   - 対策: フェーズ26で80%目標

3. **N+1問題（一部）**
   - 影響: パフォーマンス低下
   - 対策: Laravel Telescopeで継続監視

4. **ドキュメント不足**
   - 影響: 開発効率低下
   - 対策: フェーズ28でAPI仕様書作成

5. **本番環境未構築**
   - 影響: デプロイできない
   - 対策: フェーズ29でインフラ構築

---

## 設計原則・ベストプラクティス

### SOLID原則

- **S**ingle Responsibility: 1クラス1責務
- **O**pen/Closed: 拡張に開いて、修正に閉じている
- **L**iskov Substitution: 派生型は基本型と置換可能
- **I**nterface Segregation: クライアントに不要なインターフェースを強制しない
- **D**ependency Inversion: 抽象に依存し、実装に依存しない

### Laravel Best Practices

1. **Eloquent優先**
   - Query Builderより可読性・保守性が高い

2. **Fat Model, Skinny Controller**
   - ビジネスロジックはModelかServiceへ

3. **DRY（Don't Repeat Yourself）**
   - 重複コード削減

4. **Convention over Configuration**
   - Laravel規約に従う

5. **Eager Loading**
   - N+1問題回避

---

## 参考資料

- [Laravel公式ドキュメント](https://laravel.com/docs)
- [Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices)
- [Clean Architecture](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)
- [RESTful API設計](https://restfulapi.net/)
- [The Twelve-Factor App](https://12factor.net/)

---

**最終更新**: 2026-04-26  
**バージョン**: 1.0
