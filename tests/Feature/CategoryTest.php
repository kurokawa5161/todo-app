<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログイン済みユーザーはカテゴリ一覧を表示できる()
    {
        $user = User::factory()->create();
        Category::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/categories');

        $response->assertStatus(200);
        $response->assertViewIs('category.index');
        $response->assertViewHas('categories');
    }

    public function test_未ログインユーザーはログイン画面にリダイレクトされる()
    {
        $response = $this->get('/categories');
        $response->assertRedirect('/login');
    }

    public function test_ログイン済みユーザーはカテゴリを作成できる()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/categories', [
            'name' => 'Work',
            'color' => 'blue'
        ]);

        $response->assertRedirect('/categories');
        $this->assertDatabaseHas('categories', [
            'name' => 'Work',
            'user_id' => $user->id,
            'color' => 'blue'
        ]);
    }

    public function test_カテゴリ名が空ではバリデーションエラー()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/categories', [
            'name' => '',
            'color' => 'blue'
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_カテゴリ名が50文字を超えるとバリデーションエラー()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/categories', [
            'name' => str_repeat('a', 51),
            'color' => 'blue'
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_無効なカラー値でバリデーションエラー()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/categories', [
            'name' => 'Work',
            'color' => 'invalid-color'
        ]);

        $response->assertSessionHasErrors('color');
    }

    public function test_自分のカテゴリは削除できる()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/categories/{$category->id}");

        $response->assertRedirect('/categories');
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_他人のカテゴリは削除できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->delete("/categories/{$category->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
