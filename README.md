# Laravel Todo App

Laravel学習用のTodoアプリケーション。**Phase 23完了**（External Service Integration - Slack/GitHub Webhook統合）。

## 機能

### 基本機能
- ✅ Todo CRUD操作
- ✅ カテゴリ・タグ管理
- ✅ 親子関係（サブタスク）
- ✅ ピン留め・優先度
- ✅ 画像アップロード
- ✅ 期限通知メール
- ✅ タスクスケジューリング
- ✅ 動的ページネーション（5/10/20/50件表示切替）

### API・テスト
- ✅ RESTful API（Laravel Sanctum認証）
- ✅ API Resource（レスポンス整形）
- ✅ Feature/Unit Test

### 統計・レポート
- ✅ 統計ダッシュボード
- ✅ グラフ表示
- ✅ CSV/PDFエクスポート

### チーム機能
- ✅ チーム作成・管理
- ✅ メンバー招待
- ✅ チームTodo管理

### リアルタイム機能
- ✅ Todo更新のリアルタイム通知
- ✅ コメント通知システム
- ✅ Laravel Reverb（WebSocket）

### 外部サービス連携（Phase 23完了）

#### Slack統合
- ✅ Slashコマンド実装
  - `/todo add [タスク名]` - Todo作成
  - `/todo list` - 未完了Todo一覧表示
  - `/todo done [ID]` - Todo完了
  - `/todo help` - ヘルプ表示
- ✅ 自動通知（TodoObserver + Job）
  - Todo作成・完了・削除時にSlack通知
- ✅ Webhook署名検証（HMAC-SHA256）
- ✅ 統合ログ記録（integration_logs）

#### GitHub統合
- ✅ Webhook受信・処理
  - `issues.opened` - Issue作成時にTodo自動作成
  - `issues.closed` - Issue完了時にTodo完了
  - `issues.edited` - Issue編集時にTodo更新
  - `issues.assigned` - 担当者割り当て時にTodo assigned_to設定
- ✅ Issue ↔ Todo紐付け（github_issue_url）
- ✅ Webhook署名検証（HMAC-SHA256）
- ✅ 統合ログ記録

#### その他
- ✅ Google Calendar連携（.icsエクスポート）
- ✅ ブラウザテストページ（/integration-test）

### セキュリティ機能
- ✅ レート制限（ログイン・API・パスワードリセット）
- ✅ セッション暗号化・タイムアウト設定
- ✅ CSPヘッダー・セキュリティヘッダー
- ✅ XSS/CSRF対策
- ✅ ファイルアップロード検証強化
- ✅ Mass Assignment保護
- ✅ **Webhook署名検証（Slack/GitHub）**
  - HMAC-SHA256による真正性検証
  - タイムスタンプ検証（Slack）
  - 本番環境のみ有効化

### 通知機能
- ✅ 週次レポートメール自動送信
- ✅ カスタマイズ可能なリマインダー（1日前・3日前・1週間前）
- ✅ コメント通知（メール・データベース・ブロードキャスト・プッシュ）
- ✅ タスク割り当て通知（メール・プッシュ）
- ✅ 締切通知（メール・プッシュ）
- ✅ ブラウザプッシュ通知（PWA対応）
  - Chrome（FCM経由）
  - Edge（WNS経由）
- ✅ 通知設定UI（ユーザーごとにON/OFF可能）

## 技術スタック

- **Backend**: Laravel 11, PHP 8.3
- **Database**: SQLite（開発）/ MySQL 8.0（本番）
- **Authentication**: Laravel Breeze, Laravel Sanctum
- **Testing**: Pest, PHPUnit
- **CI/CD**: GitHub Actions
- **Container**: Docker, Docker Compose
- **WebSocket**: Laravel Reverb
- **Push Notifications**: laravel-notification-channels/webpush
- **PWA**: Service Worker, Web Push API
- **Calendar**: eluceo/ical
- **External APIs**: GitHub API, Slack（データベース保存）

## ローカル開発（Herd使用）

```bash
# 依存関係インストール
composer install

# 環境変数設定
cp .env.example .env
php artisan key:generate

# Webhook Secret設定（本番環境で使用）
# .envに以下を追加
# SLACK_WEBHOOK_SECRET=your-slack-webhook-secret
# GITHUB_WEBHOOK_SECRET=your-github-webhook-secret

# データベース作成・マイグレーション
php artisan migrate

# ダミーデータ投入
php artisan db:seed

# 開発サーバー起動（Herd使用時は不要）
php artisan serve
```

## Docker使用

### 起動

```bash
# コンテナビルド・起動
docker-compose up -d

# マイグレーション実行
docker-compose exec app php artisan migrate

# ダミーデータ投入
docker-compose exec app php artisan db:seed

# アクセス
# http://localhost:8080
```

### 停止

```bash
docker-compose down
```

### コンテナ削除（データも削除）

```bash
docker-compose down -v
```

## API使用方法

### 認証

```bash
# ログイン
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'

# レスポンスからtokenを取得
```

### Todo操作

```bash
# Todo一覧取得
curl http://localhost/api/todos \
  -H "Authorization: Bearer YOUR_TOKEN"

# Todo作成
curl -X POST http://localhost/api/todos \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"新しいTodo","content":"説明文"}'

# Todo更新
curl -X PUT http://localhost/api/todos/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"更新されたTodo"}'

# Todo削除
curl -X DELETE http://localhost/api/todos/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## テスト実行

### 自動テスト

```bash
# 全テスト実行
php artisan test

# 特定のテストのみ
php artisan test --filter TodoTest
```

### Slack/GitHub統合テスト

ブラウザベースのテストページで動作確認できます:

```
https://todo-app.test/integration-test
```

**機能**:
- Slackコマンドシミュレーション
- GitHub Webhookシミュレーション
- 統合ログリアルタイム表示

**注意**: ローカル環境では署名検証がスキップされます（本番環境のみ有効）。

## CI/CD

GitHub Actionsで自動テストを実行します。

- **トリガー**: main/developブランチへのpush/PR
- **実行内容**: PHPセットアップ → 依存関係インストール → マイグレーション → テスト実行

## ライセンス

MIT License

## Phase進捗

- ✅ Phase 1-18: 基本機能・認証・CRUD
- ✅ Phase 19: 通知システム強化（メール・プッシュ・PWA）
- ✅ Phase 20: チーム機能
- ✅ Phase 21: エクスポート機能拡張
- ✅ Phase 22: ダッシュボードカスタマイズ
- ✅ **Phase 23: 外部サービス統合（Slack/GitHub）**
  - Part A: テーブル設計
  - Part B: Slack統合
  - Part C: GitHub統合
  - Part D: Webhook署名検証

## Webhook設定（本番環境）

### Slack設定

1. Slack App作成: https://api.slack.com/apps
2. Slash Commandsを有効化
3. Request URL: `https://your-domain.com/slack/commands`
4. Signing Secretを`.env`に設定

### GitHub設定

1. リポジトリSettings → Webhooks → Add webhook
2. Payload URL: `https://your-domain.com/github/webhook`
3. Content type: `application/json`
4. Secret: `.env`の`GITHUB_WEBHOOK_SECRET`と同じ値を設定
5. Events: `Issues`を選択

## 開発者

学習用プロジェクト - Laravel基礎から実務レベルまで
