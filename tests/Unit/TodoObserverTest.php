<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Todo;
use App\Jobs\SlackNotificationJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TodoObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_Todo作成時にSlack通知ジョブがディスパッチされる()
    {
        $user = User::factory()->create();

        $todo = Todo::factory()->create(['user_id' => $user->id]);

        Queue::assertPushed(SlackNotificationJob::class, function ($job) use ($todo) {
            return $job->todo->id === $todo->id && $job->action === 'created';
        });
    }

    public function test_Todo更新時に完了状態が変更されるとSlack通知ジョブがディスパッチされる()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'completed_at' => null
        ]);

        Queue::fake(); // キューをリセット

        // 完了状態を変更
        $todo->completed_at = now();
        $todo->save();

        Queue::assertPushed(SlackNotificationJob::class, function ($job) use ($todo) {
            return $job->todo->id === $todo->id && $job->action === 'completed';
        });
    }

    public function test_Todo更新時に完了状態が未完了に変更されるとSlack通知ジョブがディスパッチされる()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'completed_at' => now()
        ]);

        Queue::fake(); // キューをリセット

        // 未完了に変更
        $todo->completed_at = null;
        $todo->save();

        Queue::assertPushed(SlackNotificationJob::class, function ($job) use ($todo) {
            return $job->todo->id === $todo->id && $job->action === 'uncompleted';
        });
    }

    public function test_Todo更新時に完了状態以外が変更されても通知は送信されない()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        Queue::fake(); // キューをリセット

        // タイトルのみ変更（completed_atは変更しない）
        $todo->title = '新しいタイトル';
        $todo->save();

        Queue::assertNotPushed(SlackNotificationJob::class);
    }

    public function test_Todo削除時にSlack通知ジョブがディスパッチされる()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        Queue::fake(); // キューをリセット

        $todoId = $todo->id;
        $todo->delete();

        Queue::assertPushed(SlackNotificationJob::class, function ($job) use ($todoId) {
            return $job->todo->id === $todoId && $job->action === 'deleted';
        });
    }

    public function test_複数Todo作成時にそれぞれSlack通知ジョブがディスパッチされる()
    {
        $user = User::factory()->create();

        Todo::factory()->count(3)->create(['user_id' => $user->id]);

        Queue::assertPushed(SlackNotificationJob::class, 3);
    }
}
