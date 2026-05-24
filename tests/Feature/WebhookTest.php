<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\IntegrationLog;
use App\Services\GitHubService;
use App\Services\SlackService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ========================================
    // GitHub Webhook Tests
    // ========================================

    public function test_GitHubWebhook_イベント処理が成功する()
    {
        $payload = [
            'action' => 'opened',
            'issue' => [
                'number' => 1,
                'title' => 'Test Issue',
                'body' => 'Test Body',
                'html_url' => 'https://github.com/test/repo/issues/1',
            ],
            'repository' => ['name' => 'test-repo'],
        ];

        $response = $this->actingAs($this->user)
            ->withHeaders(['X-GitHub-Event' => 'issues'])
            ->postJson('/github/webhook', $payload);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('integration_logs', [
            'user_id' => $this->user->id,
            'service' => 'github',
            'action' => 'issues',
            'status' => 'success',
        ]);
    }

    public function test_GitHubWebhook_Testing環境では署名検証をスキップする()
    {
        // testing環境では署名検証なしで成功する
        $payload = [
            'action' => 'opened',
            'issue' => ['number' => 1, 'title' => 'Test'],
            'repository' => ['name' => 'test-repo'],
        ];

        $response = $this->actingAs($this->user)
            ->withHeaders(['X-GitHub-Event' => 'issues'])
            ->postJson('/github/webhook', $payload);

        $response->assertStatus(200);
    }

    public function test_GitHubWebhook_例外が発生した場合はエラーログを記録する()
    {
        $this->mock(GitHubService::class, function ($mock) {
            $mock->shouldReceive('handleEvent')
                ->andThrow(new \Exception('Service error'));
        });

        $response = $this->actingAs($this->user)
            ->withHeaders(['X-GitHub-Event' => 'issues'])
            ->postJson('/github/webhook', ['action' => 'opened']);

        $response->assertStatus(500)
            ->assertJson(['status' => 'error']);

        $this->assertDatabaseHas('integration_logs', [
            'user_id' => $this->user->id,
            'service' => 'github',
            'status' => 'failed',
        ]);
    }

    // ========================================
    // Slack Webhook Tests
    // ========================================

    public function test_SlackWebhook_コマンド処理が成功する()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/slack/commands', [
                'text' => 'add テストタスク',
                'user_name' => $this->user->email,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['response_type', 'text']);

        $this->assertDatabaseHas('integration_logs', [
            'user_id' => $this->user->id,
            'service' => 'slack',
            'action' => 'command',
            'status' => 'success',
        ]);
    }

    public function test_SlackWebhook_Testing環境では署名検証をスキップする()
    {
        // testing環境では署名検証なしで成功する
        $response = $this->actingAs($this->user)
            ->postJson('/slack/commands', [
                'text' => 'list',
                'user_name' => $this->user->email,
            ]);

        $response->assertStatus(200);
    }

    public function test_SlackWebhook_ユーザーが見つからない場合はエラーを返す()
    {
        $response = $this->postJson('/slack/commands', [
            'text' => 'add test',
            'user_name' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(404)
            ->assertJsonFragment(['text' => 'ユーザーが見つかりません（nonexistent@example.com）。ログインしてください。']);
    }

    public function test_SlackWebhook_例外が発生した場合はエラーログを記録する()
    {
        $this->mock(SlackService::class, function ($mock) {
            $mock->shouldReceive('parseCommand')
                ->andThrow(new \Exception('Service error'));
        });

        $response = $this->actingAs($this->user)
            ->postJson('/slack/commands', [
                'text' => 'add test',
                'user_name' => $this->user->email,
            ]);

        $response->assertStatus(500)
            ->assertJsonFragment(['text' => 'エラーが発生しました：Service error']);

        $this->assertDatabaseHas('integration_logs', [
            'user_id' => $this->user->id,
            'service' => 'slack',
            'status' => 'failed',
        ]);
    }
}
