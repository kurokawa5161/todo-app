# プロジェクト引継ぎドキュメント

## プロジェクト概要

**プロジェクト名**: Laravel Todo App  
**目的**: Laravel学習用の実務レベルTodoアプリケーション  
**開発期間**: 2026年1月 - 2026年4月  
**現在の状況**: フェーズ18完了（セキュリティ強化）  
**開発者**: ckurokawa（is1101520@gmail.com）  

---

## 完了フェーズ一覧

### ✅ フェーズ1-11: 基本機能
- Todo CRUD操作
- カテゴリ・タグ管理
- 親子関係（サブタスク）
- ピン留め・優先度
- 画像アップロード
- 期限通知メール
- タスクスケジューリング
- 検索・フィルタリング
- 保存済み検索条件

### ✅ フェーズ12-13: API・テスト
- RESTful API実装（Laravel Sanctum認証）
- API Resource（レスポンス整形）
- Feature Test / Unit Test
- API ログ記録

### ✅ フェーズ14: チーム機能
- チーム作成・管理
- メンバー招待システム
- チーム単位のTodo管理
- チーム権限管理（Policy）

### ✅ フェーズ15: リアルタイム機能
- Laravel Reverb（WebSocket）
- Todo更新のリアルタイム通知
- コメント通知システム
- ブロードキャスト機能

### ✅ フェーズ16: 外部サービス連携
- Slack通知（データベース保存のみ、実API未接続）
- Google Calendar連携（.icsエクスポート）
- GitHub連携（Webhook・Issue同期）

### ✅ フェーズ17: パフォーマンス最適化
- クエリ最適化（Eager Loading）
- キャッシュ戦略（Category、Tag、SavedSearch）
- ページネーション最適化（動的per_page）
- ※Redis導入はスキップ

### ✅ フェーズ18: セキュリティ強化
- レート制限（ログイン、パスワードリセット、API）
- セッションセキュリティ（暗号化、タイムアウト）
- セキュリティヘッダー（CSP、X-Frame-Options等）
- ファイルアップロード検証強化
- Mass Assignment保護確認

---

## 技術スタック

### バックエンド
- **Laravel**: 11.x
- **PHP**: 8.3
- **Database**: SQLite（開発）/ MySQL 8.0（本番想定）
- **認証**: Laravel Breeze, Laravel Sanctum
- **WebSocket**: Laravel Reverb

### フロントエンド
- **CSS Framework**: Tailwind CSS
- **JavaScript**: Alpine.js（部分的）
- **テンプレート**: Blade

### テスト
- **Pest**: Feature Test / Unit Test
- **PHPUnit**: 一部レガシーテスト

### CI/CD
- **GitHub Actions**: 自動テスト実行（main/developブランチ）
- **Docker**: 開発環境（docker-compose.yml）

### 外部連携
- **GitHub API**: Webhook、Issue同期
- **Slack**: データベース通知（実API未接続）
- **Calendar**: eluceo/ical（.icsエクスポート）

---

## ディレクトリ構造

```
todo-app/
├── app/
│   ├── Events/                  # イベント（TodoCreated, TodoUpdated, TodoDeleted）
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── TodoController.php          # Todo CRUD
│   │   │   ├── DashboardController.php     # 統計ダッシュボード
│   │   │   ├── TeamController.php          # チーム管理
│   │   │   ├── CommentController.php       # コメント機能
│   │   │   ├── CategoryController.php      # カテゴリ管理
│   │   │   ├── TagController.php           # タグ管理
│   │   │   ├── SavedSearchController.php   # 保存済み検索
│   │   │   ├── GitHubWebhookController.php # GitHub Webhook受信
│   │   │   └── Api/                        # API Controllers
│   │   ├── Middleware/
│   │   │   ├── LogApiRequest.php           # APIログ記録
│   │   │   └── SecurityHeaders.php         # セキュリティヘッダー
│   │   └── Requests/
│   │       └── TodoRequest.php             # Todoバリデーション
│   ├── Models/
│   │   ├── Todo.php                        # Todoモデル（$fillable定義済み）
│   │   ├── User.php
│   │   ├── Category.php
│   │   ├── Tag.php
│   │   ├── Comment.php
│   │   ├── Team.php
│   │   ├── TeamInvitation.php
│   │   ├── SavedSearch.php
│   │   └── ApiLog.php
│   ├── Notifications/
│   │   └── TodoSlackNotification.php       # Slack通知（database channel）
│   ├── Policies/
│   │   ├── TodoPolicy.php                  # Todo権限管理
│   │   ├── CategoryPolicy.php
│   │   ├── TagPolicy.php
│   │   ├── CommentPolicy.php
│   │   └── SavedSearchPolicy.php
│   ├── Providers/
│   │   └── AppServiceProvider.php          # レート制限定義、Policy登録
│   └── Services/
│       └── GitHubService.php               # GitHub API連携
├── bootstrap/
│   └── app.php                             # ミドルウェア設定、CSRF除外
├── config/
│   ├── services.php                        # GitHub Token設定
│   └── session.php                         # セッション設定
├── database/
│   ├── migrations/                         # 全テーブルマイグレーション
│   └── seeders/                            # シーダー
├── resources/
│   └── views/
│       ├── todos/
│       │   ├── index.blade.php             # Todo一覧（ページネーション）
│       │   └── edit.blade.php              # Todo編集
│       ├── teams/                          # チーム関連ビュー
│       ├── category/                       # カテゴリ管理
│       ├── tags/                           # タグ管理
│       └── dashboard.blade.php             # 統計ダッシュボード
├── routes/
│   ├── web.php                             # Web routes
│   ├── api.php                             # API routes
│   ├── channels.php                        # Broadcast channels
│   └── console.php                         # Console commands
├── tests/
│   ├── Feature/                            # Feature tests
│   └── Unit/                               # Unit tests
├── .env                                    # 環境変数（セッション暗号化=true）
├── docker-compose.yml                      # Docker設定
├── README.md                               # プロジェクト概要
├── ROADMAP.md                              # 今後の開発計画
├── HANDOFF.md                              # このファイル
└── ARCHITECTURE.md                         # アーキテクチャ設計（別途作成）
```

---

## 重要なファイル・設定

### 1. 環境変数（.env）

```env
# データベース
DB_CONNECTION=sqlite

# セッション（セキュリティ強化済み）
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_EXPIRE_ON_CLOSE=false

# WebSocket（Reverb）
REVERB_APP_ID=583729
REVERB_APP_KEY=ly3ujabviuj5ma4otaib
REVERB_APP_SECRET=fpknangp47wudfrzu4fw
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

# 外部連携（未設定）
SLACK_WEBHOOK_URL=""
GITHUB_TOKEN=""

# メール（開発環境はlog）
MAIL_MAILER=log
```

### 2. レート制限設定（AppServiceProvider.php）

```php
// ログイン試行制限
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->email . $request->ip());
});

// API認証制限
RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(10)->by($request->ip());
});

// パスワードリセット制限
RateLimiter::for('password-reset', function (Request $request) {
    return Limit::perMinute(3)->by($request->email . $request->ip());
});
```

### 3. CSRF除外設定（bootstrap/app.php）

```php
$middleware->validateCsrfTokens(except: [
    '/webhook/github',  // GitHub Webhook用
]);
```

### 4. セキュリティヘッダー（SecurityHeaders.php）

- Content-Security-Policy
- X-Content-Type-Options: nosniff
- X-Frame-Options: SAMEORIGIN
- Referrer-Policy: strict-origin-when-cross-origin
- Permissions-Policy

### 5. キャッシュ設定

以下のデータは3600秒（1時間）キャッシュ：
- ユーザーのカテゴリ一覧
- ユーザーのタグ一覧
- ユーザーの保存済み検索条件

キャッシュキー例: `user_{user_id}_categories`

---

## 開発環境セットアップ

### Herd使用（推奨）

```bash
# リポジトリクローン
git clone https://github.com/kurokawa5161/todo-app.git
cd todo-app

# 依存関係インストール
composer install
npm install

# 環境変数設定
cp .env.example .env
php artisan key:generate

# データベースマイグレーション
php artisan migrate

# ダミーデータ投入
php artisan db:seed

# Reverbサーバー起動（別ターミナル）
php artisan reverb:start

# フロントエンドビルド（別ターミナル）
npm run dev

# Herd経由でアクセス
# http://todo-app.test
```

### Docker使用

```bash
# コンテナ起動
docker-compose up -d

# マイグレーション実行
docker-compose exec app php artisan migrate

# ダミーデータ投入
docker-compose exec app php artisan db:seed

# アクセス
# http://localhost:8080
```

---

## テスト実行

```bash
# 全テスト実行
php artisan test

# 特定のテストのみ
php artisan test --filter TodoTest

# カバレッジレポート生成
php artisan test --coverage
```

---

## GitHub Actions（CI/CD）

### 自動テストトリガー
- `main`ブランチへのpush
- `develop`ブランチへのpush
- プルリクエスト作成時

### 実行内容
1. PHP 8.3セットアップ
2. 依存関係インストール（composer install）
3. .envファイル準備
4. データベースマイグレーション
5. テスト実行（php artisan test）

### 設定ファイル
`.github/workflows/laravel.yml`

---

## API仕様

### エンドポイント一覧

#### 認証
- `POST /api/login` - ログイン（tokenレスポンス）
- `POST /api/logout` - ログアウト

#### Todo操作
- `GET /api/todos` - Todo一覧取得
- `GET /api/todos/{id}` - Todo詳細取得
- `POST /api/todos` - Todo作成
- `PUT /api/todos/{id}` - Todo更新
- `DELETE /api/todos/{id}` - Todo削除
- `PATCH /api/todos/{id}/toggle` - 完了/未完了切替
- `PATCH /api/todos/{id}/pin` - ピン留め切替

### 認証方式
Laravel Sanctum（Bearer Token）

```bash
# リクエスト例
curl http://localhost/api/todos \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

---

## 既知の問題・制約事項

### 1. Redis未導入
- フェーズ17でRedis導入をスキップ
- キャッシュストアは`file`を使用
- 今後の高負荷対応時にRedis導入推奨

### 2. Slack連携未完了
- Slack通知はデータベース保存のみ
- 実際のSlack API連携は未実装
- `SLACK_WEBHOOK_URL`が未設定

### 3. GitHub Token未設定
- GitHub Issue閉鎖機能は動作しない
- `GITHUB_TOKEN`を設定すれば有効化

### 4. 本番環境未構築
- 開発環境のみ
- ステージング・本番環境は未構築
- デプロイ戦略は未策定

### 5. テストカバレッジ
- 現在のカバレッジ: 約50-60%（推定）
- 目標80%には未達成
- 特にUnit Testが不足

### 6. N+1問題
- 一部のビューで未解消の可能性
- Laravel Telescopeで継続監視推奨

### 7. ファイルストレージ
- 画像は`storage/app/public/todos`に保存
- 本番環境ではS3等の外部ストレージ推奨

---

## データベース設計

### 主要テーブル

#### users
- id, name, email, password
- created_at, updated_at

#### todos
- id, user_id, title, content
- start_date, end_date, completed_at
- category_id, priority, parent_id
- is_pinned, image_path, team_id
- github_issue_url
- created_at, updated_at

#### categories
- id, user_id, name, color
- created_at, updated_at

#### tags
- id, user_id, name
- created_at, updated_at

#### todo_tag（中間テーブル）
- todo_id, tag_id

#### comments
- id, todo_id, user_id, body
- created_at, updated_at

#### teams
- id, name, owner_id
- created_at, updated_at

#### team_user（中間テーブル）
- team_id, user_id, role
- created_at, updated_at

#### team_invitations
- id, team_id, email, token
- created_at, updated_at

#### saved_searches
- id, user_id, name, filters
- created_at, updated_at

#### api_logs
- id, user_id, method, endpoint
- status_code, response_time
- created_at, updated_at

---

## セキュリティ対策まとめ

### 認証・認可
- ✅ Laravel Breeze（Web認証）
- ✅ Laravel Sanctum（API認証）
- ✅ Policy（Todo、Category、Tag、Comment、SavedSearch）
- ✅ レート制限（ログイン、API、パスワードリセット）
- ✅ セッション暗号化・タイムアウト

### XSS対策
- ✅ Bladeエスケープ（`{{ }}`使用、`{!! !!}`なし）
- ✅ CSRFトークン（全フォームに@csrf）
- ✅ Content Security Policy

### SQLインジェクション対策
- ✅ Eloquent ORM使用（自動エスケープ）
- ✅ `DB::raw()`未使用
- ✅ `selectRaw()`は固定文字列のみ

### Mass Assignment対策
- ✅ 全モデルに`$fillable`定義
- ✅ `user_id`は$fillableから除外（手動代入）

### ファイルアップロード対策
- ✅ MIMEタイプ検証（mimetypes）
- ✅ ファイルサイズ制限（max:2048KB）
- ✅ 画像寸法制限（dimensions:max_width=4000,max_height=4000）

### セキュリティヘッダー
- ✅ X-Content-Type-Options: nosniff
- ✅ X-Frame-Options: SAMEORIGIN
- ✅ Referrer-Policy: strict-origin-when-cross-origin
- ✅ Permissions-Policy

---

## パフォーマンス最適化状況

### 実装済み
- ✅ Eager Loading（`with(['category', 'tags', 'children'])`）
- ✅ クエリキャッシュ（Category、Tag、SavedSearch）
- ✅ ページネーション（動的per_page: 5/10/20/50件）
- ✅ インデックス（主要カラム）

### 未実装（今後の課題）
- ❌ Redis導入
- ❌ CDN導入
- ❌ 画像最適化（WebP変換、圧縮）
- ❌ Laravel Octane
- ❌ データベースパーティショニング

---

## 運用コマンド

### 日次タスク

```bash
# 期限通知メール送信（毎朝9時に自動実行）
php artisan app:send-deadline-notifications
```

### キャッシュクリア

```bash
# 全キャッシュクリア
php artisan cache:clear

# 設定キャッシュクリア
php artisan config:clear

# ビューキャッシュクリア
php artisan view:clear

# ルートキャッシュクリア
php artisan route:clear
```

### 本番環境最適化

```bash
# 設定キャッシュ
php artisan config:cache

# ルートキャッシュ
php artisan route:cache

# ビューキャッシュ
php artisan view:cache
```

---

## トラブルシューティング

### エラー: Target class [view] does not exist

**原因**: キャッシュ不整合

**解決方法**:
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### エラー: Class 'Limit' not found

**原因**: use文不足

**解決方法**:
```php
use Illuminate\Cache\RateLimiting\Limit;
```

### エラー: SQLSTATE[HY000]: General error: 1 no such table

**原因**: マイグレーション未実行

**解決方法**:
```bash
php artisan migrate:fresh --seed
```

### エラー: Driver [slack] not supported

**原因**: Laravel 12でSlackチャンネル未サポート

**解決方法**:
```php
// Notificationのvia()メソッドを修正
public function via(object $notifiable): array
{
    return ['database'];  // 'slack'を削除
}
```

### WebSocketが動作しない

**原因**: Reverbサーバー未起動

**解決方法**:
```bash
php artisan reverb:start
```

### GitHub Webhookが動作しない

**原因**: ngrok等のトンネル未設定（ローカル開発時）

**解決方法**:
```bash
# ngrokでトンネル作成
ngrok http 80

# GitHub Webhookに ngrok URL を設定
# https://xxxx.ngrok.io/webhook/github
```

---

## Git運用ルール

### ブランチ戦略
- `main`: 本番リリース用（保護ブランch）
- `develop`: 開発用
- `feature/*`: 機能開発用
- `fix/*`: バグ修正用

### コミットメッセージ規約

```
<type>: <subject>

<body>

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>
```

**Type**:
- `feat`: 新機能
- `fix`: バグ修正
- `refactor`: リファクタリング
- `test`: テスト追加
- `docs`: ドキュメント更新
- `style`: コードスタイル修正
- `perf`: パフォーマンス改善
- `chore`: ビルド・設定変更

**例**:
```
feat: フェーズ18完了（セキュリティ強化）

- レート制限実装（ログイン5回/分、パスワードリセット3回/分）
- セッションセキュリティ強化（暗号化、120分タイムアウト）
- セキュリティヘッダー追加（CSP、X-Frame-Options等）

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>
```

---

## 今後の開発（ROADMAP.md参照）

### 高優先度
1. **パフォーマンス最適化**（フェーズ25）
   - Redis導入
   - N+1クエリ完全解消
   - Laravel Telescope導入

2. **テストカバレッジ向上**（フェーズ26）
   - 目標80%以上
   - E2Eテスト追加

3. **デプロイ・インフラ**（フェーズ29）
   - ステージング環境構築
   - 自動デプロイパイプライン

### 詳細は [ROADMAP.md](ROADMAP.md) を参照

---

## 連絡先・リソース

### 開発者
- **名前**: ckurokawa
- **Email**: is1101520@gmail.com
- **GitHub**: https://github.com/kurokawa5161/todo-app

### リポジトリ
- **URL**: https://github.com/kurokawa5161/todo-app
- **Issue**: https://github.com/kurokawa5161/todo-app/issues

### 参考ドキュメント
- [README.md](README.md) - プロジェクト概要
- [ROADMAP.md](ROADMAP.md) - 今後の開発計画
- [ARCHITECTURE.md](ARCHITECTURE.md) - アーキテクチャ設計

---

## 引継ぎチェックリスト

### 開発環境セットアップ
- [ ] リポジトリクローン完了
- [ ] 依存関係インストール完了（composer/npm）
- [ ] .env設定完了
- [ ] データベースマイグレーション実行完了
- [ ] ダミーデータ投入完了
- [ ] ローカル環境で動作確認完了

### ドキュメント確認
- [ ] README.md 確認完了
- [ ] HANDOFF.md（このファイル）確認完了
- [ ] ROADMAP.md 確認完了
- [ ] ARCHITECTURE.md 確認完了

### コード理解
- [ ] ディレクトリ構造理解
- [ ] 主要コントローラー確認（TodoController, TeamController）
- [ ] モデル・リレーション確認
- [ ] Policy・認可ロジック確認
- [ ] ミドルウェア・セキュリティ設定確認

### 動作確認
- [ ] ログイン・ログアウト
- [ ] Todo CRUD操作
- [ ] チーム作成・招待
- [ ] リアルタイム通知
- [ ] API動作確認
- [ ] テスト実行確認

### 次のステップ
- [ ] 開発計画（ROADMAP.md）の優先度確認
- [ ] 最初に着手するフェーズの選定
- [ ] 必要に応じてIssue作成

---

**引継ぎ日**: 2026-04-26  
**引継ぎ者**: Claude Sonnet 4.5  
**プロジェクト状況**: フェーズ18完了、本番環境未構築

お疲れさまでした！ご不明点があれば、Issueまたはメールでお問い合わせください。
