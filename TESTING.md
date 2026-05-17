# Testing Guide

## Phase 26-28: テストカバレッジ改善・セキュリティ強化

このドキュメントは、Phase 26-28で追加されたテストとカバレッジレポートの生成方法を説明します。

## 📁 追加されたテストファイル

### Feature Tests (Phase 26-27)
- `tests/Feature/CategoryTest.php` - Categoryコントローラーのテスト（11テストケース）
- `tests/Feature/TagTest.php` - Tagコントローラーのテスト（12テストケース）
- `tests/Feature/CommentTest.php` - Commentコントローラーのテスト（7テストケース）
- `tests/Feature/SavedSearchTest.php` - SavedSearchコントローラーのテスト（11テストケース）
- `tests/Feature/TodoTest.php` - キャッシュテスト3件追加（合計16テストケース）
- `tests/Feature/SecurityTest.php` - セキュリティテスト（11テストケース）✨ Phase 27

### Unit Tests (Phase 26 & 28)
- `tests/Unit/UserObserverTest.php` - UserObserverのテスト（3テストケース）
- `tests/Unit/TodoObserverTest.php` - TodoObserverのテスト（6テストケース）
- `tests/Unit/PolicyTest.php` - Policyテスト（30テストケース）✨ Phase 28
- `tests/Unit/JobTest.php` - Jobテスト（10テストケース）✨ Phase 28
- `tests/Unit/NotificationTest.php` - Notificationテスト（18テストケース）✨ Phase 28

**合計:** 135テストケース

## 🧪 テスト実行コマンド

### すべてのテストを実行
```powershell
php artisan test
```

### 特定のテストファイルを実行
```powershell
# Phase 26: Controller & Observer Tests
php artisan test --filter=CategoryTest
php artisan test --filter=TagTest
php artisan test --filter=CommentTest
php artisan test --filter=SavedSearchTest
php artisan test --filter=UserObserverTest
php artisan test --filter=TodoObserverTest

# Phase 27: Security Tests
php artisan test --filter=SecurityTest

# Phase 28: Policy, Job & Notification Tests
php artisan test --filter=PolicyTest
php artisan test --filter=JobTest
php artisan test --filter=NotificationTest
```

### Feature/Unitテストのみ実行
```powershell
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

## 📊 テストカバレッジレポート生成

### 前提条件
カバレッジレポートを生成するには、PHPのコードカバレッジツールが必要です：

**Xdebugを使用する場合（推奨）:**
```powershell
# Xdebugの状態確認
php -v

# Xdebugがインストールされていない場合
# HerdのPHP設定でXdebugを有効化してください
```

**PCOVを使用する場合:**
```powershell
# PCOVのインストール（Composer経由）
composer require --dev pcov/clobber
```

### HTMLカバレッジレポート生成
```powershell
# Xdebugを使用
php artisan test --coverage-html coverage-report

# または直接PHPUnitを実行
./vendor/bin/phpunit --coverage-html coverage-report
```

生成されたレポートは `coverage-report/index.html` をブラウザで開いて確認できます。

### テキスト形式のカバレッジ表示
```powershell
php artisan test --coverage
```

### 最小カバレッジ率の指定
```powershell
# 80%以上のカバレッジを要求（未達の場合はテスト失敗）
php artisan test --coverage --min=80
```

## 📈 テストカバレッジ目標

| 領域 | 目標カバレッジ | 現状 |
|------|--------------|------|
| Controllers | 80%+ | ✅ Phase 26完了 |
| Models | 70%+ | 一部完了 |
| Observers | 90%+ | ✅ Phase 26完了 |
| Policies | 80%+ | ✅ Phase 28完了 |
| Jobs | 80%+ | ✅ Phase 28完了 |
| Notifications | 80%+ | ✅ Phase 28完了 |
| Security | 100% | ✅ Phase 27完了 |
| Requests | 100% | 未測定 |

## 🔍 テスト対象機能

### ✅ カバー済み（Phase 26-28）
- **CategoryController**: CRUD操作、バリデーション、権限チェック、キャッシュ機能
- **TagController**: CRUD操作、バリデーション、権限チェック、キャッシュ機能
- **CommentController**: CRUD操作、バリデーション、権限チェック、通知送信
- **SavedSearchController**: CRUD操作、バリデーション、権限チェック、キャッシュ機能
- **TodoController**: CRUD操作、バリデーション、権限チェック、キャッシュ機能
- **UserObserver**: ユーザー作成時の通知設定自動生成
- **TodoObserver**: Todo作成/更新/削除時のSlack通知
- **Policies**: TodoPolicy、CategoryPolicy、TagPolicy、CommentPolicy、SavedSearchPolicy（各6テストケース）✨ Phase 28
- **Jobs**: SlackNotificationJob（メッセージ生成、キューディスパッチ）✨ Phase 28
- **Notifications**: TodoCommentNotification、TodoDeadlineNotification、WeeklyReportNotification ✨ Phase 28
- **Security**: CSRF保護、XSS対策、SQL Injection対策、Rate Limiting、セキュリティヘッダー ✨ Phase 27

### ⚠️ 未カバー
- TeamController、ExportTemplateController、DashboardWidgetController
- TeamPolicy、ExportTemplatePolicy、DashboardWidgetPolicy
- メール通知機能（TodoAssignedNotification、TeamInvitationNotification、TodoSlackNotification）
- ジョブ（SendWeeklyReportsJob、SendRemindersJob）

## 🚀 次のステップ（Phase 29候補）

1. **CI/CDへのカバレッジ統合**
   - GitHub Actionsでカバレッジレポート生成
   - Codecov等の外部サービス連携
   - 最小カバレッジ率の強制

2. **E2Eテストの追加**
   - Laravel Dusk導入（ブラウザテスト）
   - ユーザーフローのエンドツーエンドテスト

3. **パフォーマンステストの追加**
   - Telescopeを活用したスロークエリ検出
   - 負荷テスト（Apache Bench、Siege等）

4. **残りのコンポーネントテスト**
   - TeamController、ExportTemplateController等
   - 残りのNotification、Job

## 📝 テスト作成のベストプラクティス

### 1. AAA（Arrange-Act-Assert）パターン
```php
public function test_example()
{
    // Arrange: テストデータの準備
    $user = User::factory()->create();
    
    // Act: テスト対象の実行
    $response = $this->actingAs($user)->get('/todos');
    
    // Assert: 結果の検証
    $response->assertStatus(200);
}
```

### 2. テスト名は日本語で明確に
```php
// ✅ Good
public function test_ログイン済みユーザーはTodoを追加できる()

// ❌ Bad
public function test_create_todo()
```

### 3. 各テストは独立させる
```php
// ✅ Good: RefreshDatabaseを使用
use RefreshDatabase;

// ❌ Bad: 前のテストの状態に依存
```

### 4. Fakeを活用する
```php
// キュー
Queue::fake();

// 通知
Notification::fake();

// イベント
Event::fake();

// ストレージ
Storage::fake();
```

## 🔧 トラブルシューティング

### テストが遅い場合
```powershell
# SQLiteインメモリDBを使用（phpunit.xmlで設定済み）
# または並列実行（Paratest）
composer require --dev brianium/paratest
./vendor/bin/paratest
```

### キャッシュテストが失敗する場合
```powershell
# テスト環境でRedisドライバーを使用している場合、arrayに変更
# phpunit.xml の CACHE_STORE を確認
<env name="CACHE_STORE" value="array"/>
```

### Observerテストが失敗する場合
```php
// Observerを一時的に無効化
Todo::unsetEventDispatcher();
```

## 📚 参考リンク

- [Laravel Testing Documentation](https://laravel.com/docs/11.x/testing)
- [PHPUnit Documentation](https://docs.phpunit.de/)
- [Pest PHP](https://pestphp.com/) - 代替テストフレームワーク
