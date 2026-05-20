<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SavedSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログイン済みユーザーは保存済み検索を作成できる()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/saved-searches', [
            'name' => 'My Search',
            'filter' => 'all',
            'q' => 'test',
            'category_id' => 1,
            'priority' => 'high',
            'sort' => 'created_at',
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31'
        ]);

        $response->assertRedirect('/todos');
        $this->assertDatabaseHas('saved_searches', [
            'name' => 'My Search',
            'user_id' => $user->id
        ]);

        // conditionsがJSON形式で保存されていることを確認
        $savedSearch = SavedSearch::where('name', 'My Search')->first();
        $this->assertIsArray($savedSearch->conditions);
        $this->assertEquals('all', $savedSearch->conditions['filter']);
        $this->assertEquals('test', $savedSearch->conditions['q']);
    }

    public function test_検索名が空ではバリデーションエラー()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/saved-searches', [
            'name' => '',
            'filter' => 'all'
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_検索名が100文字を超えるとバリデーションエラー()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/saved-searches', [
            'name' => str_repeat('a', 101),
            'filter' => 'all'
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_nullの条件は保存されない()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/saved-searches', [
            'name' => 'My Search',
            'filter' => 'all',
            'q' => null,
            'category_id' => null
        ]);

        $savedSearch = SavedSearch::where('name', 'My Search')->first();

        // nullの値は保存されていないことを確認
        $this->assertArrayHasKey('filter', $savedSearch->conditions);
        $this->assertArrayNotHasKey('q', $savedSearch->conditions);
        $this->assertArrayNotHasKey('category_id', $savedSearch->conditions);
    }

    public function test_自分の保存済み検索を適用できる()
    {
        $user = User::factory()->create();
        $savedSearch = SavedSearch::factory()->create([
            'user_id' => $user->id,
            'conditions' => ['filter' => 'all', 'q' => 'test']
        ]);

        $response = $this->actingAs($user)->get("/saved-searches/{$savedSearch->id}/apply");

        $response->assertRedirect('/todos?filter=all&q=test');
    }

    public function test_他人の保存済み検索は適用できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $savedSearch = SavedSearch::factory()->create([
            'user_id' => $otherUser->id,
            'conditions' => ['filter' => 'all']
        ]);

        $response = $this->actingAs($user)->get("/saved-searches/{$savedSearch->id}/apply");

        $response->assertStatus(403);
    }

    public function test_自分の保存済み検索は削除できる()
    {
        $user = User::factory()->create();
        $savedSearch = SavedSearch::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/saved-searches/{$savedSearch->id}");

        $response->assertRedirect('/todos');
        $this->assertDatabaseMissing('saved_searches', ['id' => $savedSearch->id]);
    }

    public function test_他人の保存済み検索は削除できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $savedSearch = SavedSearch::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->delete("/saved-searches/{$savedSearch->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('saved_searches', ['id' => $savedSearch->id]);
    }
}
