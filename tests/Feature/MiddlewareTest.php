<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ApiLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ========================================
    // LogApiRequest Middleware Tests
    // ========================================

    public function test_LogApiRequest_API_リクエストをログに記録する()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/todos');

        $response->assertStatus(200);

        $this->assertDatabaseHas('api_logs', [
            'user_id' => $this->user->id,
            'method' => 'GET',
            'endpoint' => 'api/todos',
            'status_code' => 200,
        ]);
    }

    public function test_LogApiRequest_未認証ユーザーのログも記録する()
    {
        $response = $this->getJson('/api/todos');

        $response->assertStatus(401);

        $this->assertDatabaseHas('api_logs', [
            'user_id' => null,
            'method' => 'GET',
            'endpoint' => 'api/todos',
            'status_code' => 401,
        ]);
    }

    public function test_LogApiRequest_POSTリクエストを記録する()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/todos', [
                'title' => 'Test Todo',
                'start_date' => '2026-04-01',
                'end_date' => '2026-12-31',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('api_logs', [
            'user_id' => $this->user->id,
            'method' => 'POST',
            'endpoint' => 'api/todos',
            'status_code' => 200,
        ]);
    }

    public function test_LogApiRequest_エラーレスポンスも記録する()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/todos', []);

        $response->assertStatus(422);

        $this->assertDatabaseHas('api_logs', [
            'user_id' => $this->user->id,
            'method' => 'POST',
            'endpoint' => 'api/todos',
            'status_code' => 422,
        ]);
    }

    public function test_LogApiRequest_IP_アドレスを記録する()
    {
        $response = $this->actingAs($this->user)
            ->from('192.168.1.1')
            ->getJson('/api/todos');

        $response->assertStatus(200);

        $log = ApiLog::where('user_id', $this->user->id)->latest()->first();
        $this->assertNotNull($log->ip_address);
    }

    // ========================================
    // SecurityHeaders Middleware Tests
    // ========================================

    public function test_SecurityHeaders_Content_Security_Policy_ヘッダーを設定する()
    {
        $response = $this->get('/');

        $response->assertHeader('Content-Security-Policy');
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src", $csp);
        $this->assertStringContainsString("style-src", $csp);
    }

    public function test_SecurityHeaders_X_Content_Type_Options_ヘッダーを設定する()
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_SecurityHeaders_X_Frame_Options_ヘッダーを設定する()
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_SecurityHeaders_Referrer_Policy_ヘッダーを設定する()
    {
        $response = $this->get('/');

        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_SecurityHeaders_Permissions_Policy_ヘッダーを設定する()
    {
        $response = $this->get('/');

        $response->assertHeader('Permissions-Policy');
        $permissionsPolicy = $response->headers->get('Permissions-Policy');
        $this->assertStringContainsString('geolocation=()', $permissionsPolicy);
        $this->assertStringContainsString('microphone=()', $permissionsPolicy);
        $this->assertStringContainsString('camera=()', $permissionsPolicy);
    }


    public function test_SecurityHeaders_全てのレスポンスにセキュリティヘッダーが設定される()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertHeader('Content-Security-Policy');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy');
    }

}
