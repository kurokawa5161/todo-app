<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログイン済みユーザーはタグ一覧を表示できる()
    {
        $user = User::factory()->create();
        Tag::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/tags');

        $response->assertStatus(200);
        $response->assertViewIs('tags.index');
        $response->assertViewHas('tags');
    }

    public function test_未ログインユーザーはログイン画面にリダイレクトされる()
    {
        $response = $this->get('/tags');
        $response->assertRedirect('/login');
    }

    public function test_ログイン済みユーザーはタグを作成できる()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/tags', [
            'name' => 'Urgent',
            'color' => 'red'
        ]);

        $response->assertRedirect('/tags');
        $this->assertDatabaseHas('tags', [
            'name' => 'Urgent',
            'user_id' => $user->id,
            'color' => 'red'
        ]);
    }

    public function test_タグ名が空ではバリデーションエラー()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/tags', [
            'name' => '',
            'color' => 'red'
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_タグ名が20文字を超えるとバリデーションエラー()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/tags', [
            'name' => str_repeat('a', 21),
            'color' => 'red'
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_カラーが空ではバリデーションエラー()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/tags', [
            'name' => 'Urgent',
            'color' => ''
        ]);

        $response->assertSessionHasErrors('color');
    }

    public function test_無効なカラー値でバリデーションエラー()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/tags', [
            'name' => 'Urgent',
            'color' => 'invalid-color'
        ]);

        $response->assertSessionHasErrors('color');
    }

    public function test_自分のタグは削除できる()
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/tags/{$tag->id}");

        $response->assertRedirect('/tags');
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_他人のタグは削除できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->delete("/tags/{$tag->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }
}
