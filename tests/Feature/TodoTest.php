<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Todo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TodoTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログイン済みユーザーはTodoを追加できる()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/todos', [
            'title' => 'Laravel勉強',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31'
        ]);

        $response->assertRedirect('/todos');
        $this->assertDatabaseHas('todos', [
            'title' => 'Laravel勉強',
            'user_id' => $user->id
        ]);
    }

    public function test_未ログイン－ザーはログイン画面に飛ばされる(): void
    {
        $response = $this->get('/todos');
        $response->assertRedirect('/login');
    }

    public function test_タイトル空ではバリデーションエラー(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post('/todos', [
            'title' => '',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31'
        ]);

        $response->assertSessionHasErrors('title');
    }

    public function test_他人のTodoは削除できない(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $todo = $owner->todos()->create([
            'title' => 'secret',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31'
        ]);

        $response = $this->actingAs($other)->delete("/todos/{$todo->id}");
        $response->assertNotFound();
        $this->assertDatabaseHas('todos', ['id' => $todo->id]);
    }

    //----------------------------------------------------------------
    public function test_自分のTodoは更新できる()
    {
        $user = User::factory()->create();
        $todo = $user->todos()->create([
            'title' => '更新元',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31',
            'priority' => 2
        ]);
        $update = [
            'title' => '自分のTodoは更新できる',
            'priority' => 2,
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-18'
        ];

        $response = $this->actingAs($user)->put("/todos/{$todo->id}", $update);
        $response->assertRedirect('/todos');
        $this->assertDatabaseHas('todos', ['title' => $update['title']]);
    }

    public function test_他人のTodoは更新できない()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $todo = $owner->todos()->create([
            'title' => '更新元',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31',
            'priority' => 2
        ]);
        $update = [
            'title' => '他人のTodoは更新できない',
            'priority' => 2,
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-18'
        ];

        $response = $this->actingAs($other)->put("/todos/{$todo->id}", $update);
        $response->assertNotFound();
        $this->assertDatabaseMissing('todos', ['title' => $update['title']]);
    }

    public function test_自分のTodoは削除できる()
    {
        //Observer無効化
        Todo::unsetEventDispatcher();

        $owner = User::factory()->create();

        $todo = $owner->todos()->create([
            'title' => '自分のTodoは削除できる',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31'
        ]);
        $response = $this->actingAs($owner)->delete("/todos/{$todo->id}");
        $response->assertRedirect('/todos');
        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
    }

    public function test_自分のTodo編集画面にアクセスできる()
    {
        $owner = User::factory()->create();

        $todo = $owner->todos()->create([
            'title' => '自分のTodo編集画面にアクセスできる',
            'priority' => 2,
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31'
        ]);

        $response = $this->actingAs($owner)->get("/todos/{$todo->id}/edit");
        $response->assertStatus(200);
    }

    public function test_他人のTodo編集画面にはアクセスできない()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $todo = $owner->todos()->create([
            'title' => '自分のTodo編集画面にアクセスできる',
            'priority' => 2,
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31'
        ]);

        $response = $this->actingAs($other)->get("/todos/{$todo->id}/edit");
        $response->assertNotFound();
    }

    public function test_自分のTodoの完了状態を切り替えられる()
    {
        $owner = User::factory()->create();
        $todo = $owner->todos()->create([
            'title' => '自分のTodoの完了状態を切り替えられる',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31'
        ]);

        $response = $this->actingAs($owner)->patch("/todos/{$todo->id}/toggle");
        $response->assertRedirect('/todos');

        $todo->refresh();
        $this->assertNotNull($todo->completed_at);
    }

    public function test_他人のTodoの完了状態は切り替えられない()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $todo = $owner->todos()->create([
            'title' => '他人のTodoの完了状態は切り替えられない',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31'
        ]);

        $response = $this->actingAs($other)->patch("/todos/{$todo->id}/toggle");
        $response->assertNotFound();
    }

    public function test_自分のTodoのピン留めを切り替えられる()
    {
        $owner = User::factory()->create();
        $todo = $owner->todos()->create([
            'title' => '自分のTodoのピン留めを切り替えられる',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31',
            'is_pinned' => 0
        ]);

        $response = $this->actingAs($owner)->patch("/todos/{$todo->id}/pin");
        $response->assertRedirect('/todos');

        $todo->refresh();
        $this->assertTrue($todo->is_pinned);
    }

    public function test_他人のTodoのピン留めは切り替えられない()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $todo = $owner->todos()->create([
            'title' => '他人のTodoのピン留めは切り替えられない',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31',
            'is_pinned' => 0
        ]);

        $update = [
            'is_pinned' => 1,
        ];
        $response = $this->actingAs($other)->patch("/todos/{$todo->id}/pin");
        $response->assertNotFound();
        $this->assertDatabaseMissing('todos', ['is_pinned' => $update['is_pinned']]);
    }

    public function test_Todo作成後にキャッシュがフラッシュされる()
    {
        $user = User::factory()->create();

        // キャッシュを事前に設定
        Cache::tags(['user:' . $user->id])
            ->put('test_cache', 'test_value', 3600);

        $this->actingAs($user)->post('/todos', [
            'title' => 'Laravel勉強',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31'
        ]);

        // キャッシュがフラッシュされていることを確認
        $cached = Cache::tags(['user:' . $user->id])
            ->get('test_cache');

        $this->assertNull($cached);
    }

    public function test_Todo更新後にキャッシュがフラッシュされる()
    {
        $user = User::factory()->create();
        $todo = $user->todos()->create([
            'title' => '更新元',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31',
            'priority' => 2
        ]);

        // キャッシュを事前に設定
        Cache::tags(['user:' . $user->id])
            ->put('test_cache', 'test_value', 3600);

        $this->actingAs($user)->put("/todos/{$todo->id}", [
            'title' => '更新後',
            'priority' => 2,
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-18'
        ]);

        // キャッシュがフラッシュされていることを確認
        $cached = Cache::tags(['user:' . $user->id])
            ->get('test_cache');

        $this->assertNull($cached);
    }

    public function test_Todo削除後にキャッシュがフラッシュされる()
    {
        // Observer無効化
        Todo::unsetEventDispatcher();

        $user = User::factory()->create();
        $todo = $user->todos()->create([
            'title' => '削除対象',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31'
        ]);

        // キャッシュを事前に設定
        Cache::tags(['user:' . $user->id])
            ->put('test_cache', 'test_value', 3600);

        $this->actingAs($user)->delete("/todos/{$todo->id}");

        // キャッシュがフラッシュされていることを確認
        $cached = Cache::tags(['user:' . $user->id])
            ->get('test_cache');

        $this->assertNull($cached);
    }
}
