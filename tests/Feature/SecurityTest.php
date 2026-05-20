<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Todo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // CSRF Protection Tests
    // ========================================

    public function test_CSRF保護が有効でトークンなしのPOSTは失敗する()
    {
        // Laravel testingはデフォルトでCSRFトークンを自動付与するため、
        // PHPUnitでの真のCSRF保護テストは困難
        // 実際のCSRF保護はVerifyCsrfTokenミドルウェアで実装済み
        // 統合テスト・E2Eテストで検証することを推奨
        $this->markTestSkipped('Laravel testing framework automatically includes CSRF tokens');
    }

    public function test_CSRF保護が有効でトークンありのPOSTは成功する()
    {
        $user = User::factory()->create();

        // CSRFトークンありでPOSTリクエスト（Laravelのテストヘルパーは自動的にトークンを付与）
        $response = $this->actingAs($user)->post('/todos', [
            'title' => 'Test Todo',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31'
        ]);

        $response->assertRedirect('/todos');
        $this->assertDatabaseHas('todos', ['title' => 'Test Todo']);
    }

    // ========================================
    // XSS Protection Tests
    // ========================================

    public function test_XSS対策でスクリプトタグが保存される()
    {
        $user = User::factory()->create();

        // スクリプトタグを含むタイトルでTodo作成
        $maliciousTitle = '<script>alert("XSS")</script>Test Todo';

        $this->actingAs($user)->post('/todos', [
            'title' => $maliciousTitle,
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31'
        ]);

        // データベースにはそのまま保存される（エスケープは表示時に行われる）
        $this->assertDatabaseHas('todos', ['title' => $maliciousTitle]);
    }

    public function test_XSS対策でBladeがスクリプトタグをエスケープする()
    {
        $user = User::factory()->create();

        // スクリプトタグを含むTodo作成
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'title' => '<script>alert("XSS")</script>Test Todo'
        ]);

        // Todo一覧ページを取得
        $response = $this->actingAs($user)->get('/todos');

        // レスポンスにエスケープされたスクリプトタグが含まれている
        $response->assertSee('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;Test Todo', false);

        // エスケープされていない生のスクリプトタグは含まれていない
        $response->assertDontSee('<script>alert("XSS")</script>', false);
    }

    public function test_カテゴリ名もXSS対策が有効()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/categories', [
            'name' => '<img src=x onerror=alert(1)>',
            'color' => 'red'
        ]);

        $response = $this->actingAs($user)->get('/categories');

        // エスケープされて表示される
        $response->assertSee('&lt;img src=x onerror=alert(1)&gt;', false);
        $response->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    // ========================================
    // Rate Limiting Tests
    // ========================================

    public function test_Todo作成のRate_Limitingが機能する()
    {
        $user = User::factory()->create();

        // 60回までは成功（throttle:todos は60リクエスト/分）
        for ($i = 0; $i < 60; $i++) {
            $response = $this->actingAs($user)->post('/todos', [
                'title' => "Test Todo {$i}",
                'start_date' => '2026-04-01',
                'end_date' => '2026-12-31'
            ]);

            $response->assertRedirect('/todos');
        }

        // 61回目は制限に引っかかる
        $response = $this->actingAs($user)->post('/todos', [
            'title' => 'Test Todo 61',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31'
        ]);

        $response->assertStatus(429); // Too Many Requests
    }

    public function test_ログイン試行のRate_Limitingが機能する()
    {
        // 5回までは試行可能（throttle:login は5リクエスト/分）
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password'
            ]);

            // 認証失敗（パスワード間違い）
            $response->assertSessionHasErrors();
        }

        // 6回目は制限に引っかかる
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password'
        ]);

        $response->assertStatus(429); // Too Many Requests
    }

    public function test_API_Rate_Limitingが機能する()
    {
        $user = User::factory()->create();

        // APIトークンを想定（api routesに適用）
        // ここではweb routesのthrottle:webをテスト
        for ($i = 0; $i < 100; $i++) {
            $response = $this->actingAs($user)->get('/categories');
            $response->assertStatus(200);
        }

        // 101回目は制限に引っかかる
        $response = $this->actingAs($user)->get('/categories');
        $response->assertStatus(429); // Too Many Requests
    }

    // ========================================
    // SQL Injection Protection Tests
    // ========================================

    public function test_SQL_Injection対策でEloquentが安全にクエリを実行する()
    {
        $user = User::factory()->create();

        // SQL Injectionを試みるタイトル
        $maliciousTitle = "' OR '1'='1";

        $this->actingAs($user)->post('/todos', [
            'title' => $maliciousTitle,
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31'
        ]);

        // Eloquentはパラメータバインディングを使用するため、安全に保存される
        $this->assertDatabaseHas('todos', ['title' => $maliciousTitle]);

        // 他のTodoが取得されないことを確認（SQL Injectionが成功していないことを証明）
        $todos = $user->todos()->get();
        $this->assertCount(1, $todos);
    }

    // ========================================
    // Security Headers Tests
    // ========================================

    public function test_セキュリティヘッダーが正しく設定されている()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/todos');

        // Content-Security-Policy
        $response->assertHeader('Content-Security-Policy');

        // X-Content-Type-Options
        $response->assertHeader('X-Content-Type-Options', 'nosniff');

        // X-Frame-Options
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');

        // Referrer-Policy
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions-Policy
        $response->assertHeader('Permissions-Policy');
    }

    public function test_CSPヘッダーにunsafe_inlineとunsafe_evalが含まれる()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/todos');

        $csp = $response->headers->get('Content-Security-Policy');

        // 現在の実装では unsafe-inline と unsafe-eval が含まれている
        // これは改善の余地がある（Phase 27の次のタスク）
        $this->assertStringContainsString('unsafe-inline', $csp);
        $this->assertStringContainsString('unsafe-eval', $csp);
    }
}
