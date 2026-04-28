# Laravel Todo App

Laravel学習用のTodoアプリケーション。フェーズ19C完了（プッシュ通知完全実装・PWA対応）。

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

### 外部サービス連携
- ✅ Slack通知（データベース保存）
- ✅ Google Calendar連携（.icsエクスポート）
- ✅ GitHub連携（Webhook・Issue同期）

### セキュリティ機能
- ✅ レート制限（ログイン・API・パスワードリセット）
- ✅ セッション暗号化・タイムアウト設定
- ✅ CSPヘッダー・セキュリティヘッダー
- ✅ XSS/CSRF対策
- ✅ ファイルアップロード検証強化
- ✅ Mass Assignment保護

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

```bash
# 全テスト実行
php artisan test

# 特定のテストのみ
php artisan test --filter TodoTest
```

## CI/CD

GitHub Actionsで自動テストを実行します。

- **トリガー**: main/developブランチへのpush/PR
- **実行内容**: PHPセットアップ → 依存関係インストール → マイグレーション → テスト実行

## ライセンス

MIT License

## 開発者

学習用プロジェクト - Laravel基礎から実務レベルまで
