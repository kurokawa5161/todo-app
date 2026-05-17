# Security Policy

## Phase 27: セキュリティ強化（完了）

このドキュメントは、Laravel Todo Appに実装されているセキュリティ対策と、セキュリティテストの実行方法を説明します。

## 🛡️ 実装済みセキュリティ対策

### 1. CSRF（Cross-Site Request Forgery）保護

**実装状況:** ✅ 完全実装

- **ミドルウェア:** Laravel標準の `VerifyCsrfToken` ミドルウェアが有効
- **除外設定:** GitHub Webhook (`/webhook/github`) のみ除外
- **設定ファイル:** [bootstrap/app.php:29-31](bootstrap/app.php#L29-L31)

```php
$middleware->validateCsrfTokens(except: [
    '/webhook/github',
]);
```

**Bladeテンプレートでの使用:**
```blade
<form method="POST" action="/todos">
    @csrf
    <!-- フォームフィールド -->
</form>
```

### 2. XSS（Cross-Site Scripting）対策

**実装状況:** ✅ 完全実装

- **Bladeエスケープ:** すべてのユーザー入力を `{{ }}` で自動エスケープ
- **未使用:** `{!! !!}` の使用なし（監査済み）
- **効果:** スクリプトタグ、HTMLタグが自動的にエスケープされる

**例:**
```blade
<!-- 安全: 自動エスケープ -->
<h1>{{ $todo->title }}</h1>

<!-- 危険: エスケープなし（未使用） -->
<h1>{!! $todo->title !!}</h1>
```

**テスト:** [SecurityTest.php:48-78](tests/Feature/SecurityTest.php#L48-L78)

### 3. SQL Injection対策

**実装状況:** ✅ 完全実装

- **Eloquent ORM使用:** すべてのデータベースクエリでパラメータバインディング
- **生SQL不使用:** `DB::raw()`, `DB::select()`, `DB::statement()` の使用なし（監査済み）
- **効果:** SQLインジェクション攻撃を自動的に防御

**例:**
```php
// 安全: Eloquentはパラメータバインディングを使用
Todo::where('title', $userInput)->get();

// 危険: 生SQLの直接埋め込み（未使用）
DB::select("SELECT * FROM todos WHERE title = '{$userInput}'");
```

**テスト:** [SecurityTest.php:135-150](tests/Feature/SecurityTest.php#L135-L150)

### 4. Rate Limiting（レート制限）

**実装状況:** ✅ 強化完了（Phase 27）

#### 定義済みRate Limiters

| 名前 | 制限 | 対象 | 設定箇所 |
|------|------|------|----------|
| `api` | 60リクエスト/分 | APIルート | [AppServiceProvider.php:82-84](app/Providers/AppServiceProvider.php#L82-L84) |
| `login` | 5リクエスト/分 | ログイン試行 | [AppServiceProvider.php:87-89](app/Providers/AppServiceProvider.php#L87-L89) |
| `auth` | 10リクエスト/分 | 認証系ルート | [AppServiceProvider.php:92-94](app/Providers/AppServiceProvider.php#L92-L94) |
| `password-reset` | 3リクエスト/分 | パスワードリセット | [AppServiceProvider.php:96-98](app/Providers/AppServiceProvider.php#L96-L98) |
| **`web`** | **100リクエスト/分** | **一般Webルート** | **[AppServiceProvider.php:101-103](app/Providers/AppServiceProvider.php#L101-L103)** ✨ New |
| **`todos`** | **60リクエスト/分** | **Todo CRUD操作** | **[AppServiceProvider.php:106-108](app/Providers/AppServiceProvider.php#L106-L108)** ✨ New |

#### 適用状況

**Phase 27で追加されたRate Limiting:**
- ✅ Todo管理ルート（`throttle:todos`）
- ✅ ダッシュボード（`throttle:web`）
- ✅ プロフィール・エクスポート（`throttle:web`）
- ✅ カテゴリー管理（`throttle:web`）
- ✅ コメント機能（`throttle:web`）
- ✅ タグ管理（`throttle:web`）
- ✅ 保存済み検索（`throttle:web`）
- ✅ チーム機能（`throttle:web`）

**既存のRate Limiting:**
- ✅ ログイン試行（`throttle:login`）
- ✅ パスワードリセット（`throttle:password-reset`）
- ✅ メール確認（`throttle:6,1`）

**テスト:** [SecurityTest.php:93-133](tests/Feature/SecurityTest.php#L93-L133)

### 5. セキュリティヘッダー

**実装状況:** ✅ 実装済み

**ミドルウェア:** [SecurityHeaders.php](app/Http/Middleware/SecurityHeaders.php)

設定されているヘッダー：

| ヘッダー | 値 | 効果 |
|---------|-----|------|
| `Content-Security-Policy` | `default-src 'self'; script-src 'self' 'unsafe-inline' ...` | XSS攻撃の防御 |
| `X-Content-Type-Options` | `nosniff` | MIME sniffing攻撃の防御 |
| `X-Frame-Options` | `SAMEORIGIN` | Clickjacking攻撃の防御 |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | リファラー情報の制御 |
| `Permissions-Policy` | `geolocation=(), microphone=(), camera=()` | ブラウザ機能の制限 |

**テスト:** [SecurityTest.php:152-176](tests/Feature/SecurityTest.php#L152-L176)

## 🧪 セキュリティテスト

### テスト実行コマンド

```powershell
# すべてのセキュリティテストを実行
php artisan test --filter=SecurityTest

# 特定のテストを実行
php artisan test --filter=test_CSRF保護が有効でトークンなしのPOSTは失敗する
```

### テストケース一覧

**CSRF保護テスト（2件）:**
- ✅ CSRFトークンなしのPOSTは失敗する（HTTP 419）
- ✅ CSRFトークンありのPOSTは成功する

**XSS対策テスト（3件）:**
- ✅ スクリプトタグがデータベースに保存される
- ✅ Bladeがスクリプトタグをエスケープする
- ✅ カテゴリ名もXSS対策が有効

**Rate Limitingテスト（3件）:**
- ✅ Todo作成のRate Limitingが機能する（60リクエスト制限）
- ✅ ログイン試行のRate Limitingが機能する（5回制限）
- ✅ API Rate Limitingが機能する（100リクエスト制限）

**SQL Injectionテスト（1件）:**
- ✅ EloquentがSQLインジェクションを防ぐ

**セキュリティヘッダーテスト（2件）:**
- ✅ セキュリティヘッダーが正しく設定されている
- ✅ CSPヘッダーにunsafe-inlineとunsafe-evalが含まれる（改善の余地あり）

**合計:** 11テストケース

## 🔒 ベストプラクティス

### 1. フォーム送信時

常に `@csrf` ディレクティブを使用：
```blade
<form method="POST" action="{{ route('todos.store') }}">
    @csrf
    <input type="text" name="title">
    <button type="submit">送信</button>
</form>
```

### 2. ユーザー入力の表示

常に `{{ }}` でエスケープ：
```blade
<!-- 安全 -->
<p>{{ $todo->title }}</p>

<!-- 危険（使用禁止） -->
<p>{!! $todo->title !!}</p>
```

### 3. データベースクエリ

常にEloquentまたはQuery Builderを使用：
```php
// 安全
Todo::where('user_id', $userId)->get();

// 危険（使用禁止）
DB::select("SELECT * FROM todos WHERE user_id = {$userId}");
```

### 4. Rate Limitingの追加

新しいルートを追加する際は、適切なRate Limiterを適用：
```php
Route::post('/new-feature', [Controller::class, 'action'])
    ->middleware(['auth', 'throttle:web']);
```

## ⚠️ 既知の制限事項と改善の余地

### 1. Content Security Policy (CSP)

**現状:**
- `unsafe-inline`: インラインスクリプト・スタイルを許可
- `unsafe-eval`: `eval()` の使用を許可

**改善策（将来のフェーズ）:**
1. インラインスクリプトを外部ファイルに移動
2. nonceまたはhash-basedのCSPを実装
3. `unsafe-inline`, `unsafe-eval` の削除

### 2. Subresource Integrity (SRI)

**現状:**
- CDNから読み込むJavaScript/CSSにSRIハッシュなし

**改善策:**
```html
<script src="https://cdn.example.com/script.js" 
        integrity="sha384-..."
        crossorigin="anonymous"></script>
```

### 3. HTTPSの強制

**現状:**
- 本番環境でHTTPSを強制する設定がない

**改善策:**
```php
// AppServiceProvider.phpに追加
if (app()->environment('production')) {
    URL::forceScheme('https');
}
```

## 🚨 脆弱性の報告

セキュリティ脆弱性を発見した場合は、以下の手順で報告してください：

1. **公開しない:** GitHubのIssueに投稿しないでください
2. **連絡先:** is1101520@gmail.com にメールで報告
3. **詳細を含める:**
   - 脆弱性の詳細な説明
   - 再現手順
   - 影響範囲
   - 可能であれば修正案

## 📚 参考リンク

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/11.x/security)
- [Content Security Policy (CSP)](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP)
- [OWASP CSRF Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html)
- [OWASP XSS Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)

## 📝 変更履歴

### Phase 27（2026-05-17）
- ✅ CSRF保護の確認（既に実装済み）
- ✅ XSS対策の確認（`{!! !!}` 不使用確認）
- ✅ SQL Injection対策の確認（生SQL不使用確認）
- ✅ Rate Limiting強化（一般ルートへの適用）
- ✅ セキュリティテスト作成（11テストケース）
- ✅ SECURITY.md作成

### 今後の予定
- ⚠️ CSPの `unsafe-inline`, `unsafe-eval` 削除
- ⚠️ Subresource Integrity (SRI) 実装
- ⚠️ HTTPS強制（本番環境）
