<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Todo;
use App\Jobs\SlackNotificationJob;
use App\Services\SlackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Mockery;

class JobTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========================================
    // SlackNotificationJob Tests
    // ========================================

    public function test_SlackNotificationJob_created時のメッセージ生成()
    {
        $user = User::factory()->create(['name' => 'テストユーザー']);
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'title' => 'テストTodo'
        ]);

        $job = new SlackNotificationJob($todo, 'created');

        // buildMessage()はprotectedなので、リフレクションを使用
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('buildMessage');
        $method->setAccessible(true);

        $message = $method->invoke($job);

        $this->assertEquals(
            'テストユーザーさんが新しいTodoを作成しました：「テストTodo」',
            $message
        );
    }

    public function test_SlackNotificationJob_completed時のメッセージ生成()
    {
        $user = User::factory()->create(['name' => 'テストユーザー']);
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'title' => 'テストTodo'
        ]);

        $job = new SlackNotificationJob($todo, 'completed');

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('buildMessage');
        $method->setAccessible(true);

        $message = $method->invoke($job);

        $this->assertEquals(
            'テストユーザーさんがTodoを完了しました：「テストTodo」',
            $message
        );
    }

    public function test_SlackNotificationJob_uncompleted時のメッセージ生成()
    {
        $user = User::factory()->create(['name' => 'テストユーザー']);
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'title' => 'テストTodo'
        ]);

        $job = new SlackNotificationJob($todo, 'uncompleted');

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('buildMessage');
        $method->setAccessible(true);

        $message = $method->invoke($job);

        $this->assertEquals(
            'テストユーザーさんがTodoを未完了に戻しました：「テストTodo」',
            $message
        );
    }

    public function test_SlackNotificationJob_deleted時のメッセージ生成()
    {
        $user = User::factory()->create(['name' => 'テストユーザー']);
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'title' => 'テストTodo'
        ]);

        $job = new SlackNotificationJob($todo, 'deleted');

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('buildMessage');
        $method->setAccessible(true);

        $message = $method->invoke($job);

        $this->assertEquals(
            'テストユーザーさんがTodoを削除しました：「テストTodo」',
            $message
        );
    }

    public function test_SlackNotificationJob_不明なaction時のデフォルトメッセージ生成()
    {
        $user = User::factory()->create(['name' => 'テストユーザー']);
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'title' => 'テストTodo'
        ]);

        $job = new SlackNotificationJob($todo, 'unknown-action');

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('buildMessage');
        $method->setAccessible(true);

        $message = $method->invoke($job);

        $this->assertEquals(
            'テストユーザーさんがTodoを操作しました：「テストTodo」',
            $message
        );
    }

    public function test_SlackNotificationJob_handle実行時にSlackServiceが呼ばれる()
    {
        $user = User::factory()->create(['name' => 'テストユーザー']);
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'title' => 'テストTodo'
        ]);

        // userリレーションをeager load
        $todo->load('user');

        // SlackServiceのMockを作成
        $mockSlackService = Mockery::mock(SlackService::class);
        $mockSlackService->shouldReceive('sendMessage')
            ->once()
            ->with(
                '#todo-notifications',
                'テストユーザーさんが新しいTodoを作成しました：「テストTodo」',
                Mockery::type(User::class)
            )
            ->andReturnNull();

        $job = new SlackNotificationJob($todo, 'created');
        $job->handle($mockSlackService);

        // Mockeryの期待が満たされたことを確認（PHPUnitアサーションとしてカウント）
        $this->assertTrue(true);
    }

    public function test_SlackNotificationJob_Queueableトレイトを持つ()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $job = new SlackNotificationJob($todo, 'created');

        // Queueableトレイトを持つことを確認（Laravel 11ではBus\Queueable）
        $traits = class_uses_recursive($job);
        $this->assertContains(\Illuminate\Bus\Queueable::class, $traits);
    }

    public function test_SlackNotificationJob_ShouldQueueインターフェースを実装()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $job = new SlackNotificationJob($todo, 'created');

        // ShouldQueueインターフェースを実装していることを確認
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    public function test_SlackNotificationJob_キューにディスパッチできる()
    {
        Queue::fake();

        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        SlackNotificationJob::dispatch($todo, 'created');

        // キューにジョブがプッシュされたことを確認（プロパティアクセス不要）
        Queue::assertPushed(SlackNotificationJob::class);
    }

    public function test_SlackNotificationJob_複数のアクションをキューにディスパッチできる()
    {
        Queue::fake();

        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        SlackNotificationJob::dispatch($todo, 'created');
        SlackNotificationJob::dispatch($todo, 'completed');
        SlackNotificationJob::dispatch($todo, 'deleted');

        Queue::assertPushed(SlackNotificationJob::class, 3);
    }
}
