<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログイン済みユーザーはTodoを追加できる()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/todos', [
            'title' => 'Laravel勉強',
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
            'end_date' => '2026-12-31'
        ]);

        $response = $this->actingAs($other)->delete('/todos/{$todo->id}');
        $response->assertNotFound();
        $this->assertDatabaseHas('todos', ['id' => $todo->id]);
    }
}
