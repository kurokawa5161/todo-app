<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Todo;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_userリレーション()
    {
        $user = User::factory()->create();
        $todo = $user->todos()->create([
            'title' => 'テスト',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31',
            'priority' => 2
        ]);
        $this->assertInstanceOf(User::class, $todo->user);
    }

    public function test_categoryリレーション()
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'リレーションテスト',
            'user_id' => $user->id,
            'color' => 'gray'
        ]);
        $todo = $user->todos()->create([
            'title' => 'テスト',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31',
            'priority' => 2,
            'category_id' => $category->id
        ]);

        $this->assertInstanceOf(Category::class, $todo->category);
    }

    public function test_parentリレーション()
    {
        $user = User::factory()->create();
        $parent = $user->todos()->create([
            'title' => '親TODO',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31',
            'priority' => 2,
            'parent_id' => null
        ]);
        $todo = $user->todos()->create([
            'title' => 'テスト',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31',
            'priority' => 2,
            'parent_id' => $parent->id
        ]);
        $this->assertInstanceOf(Todo::class, $todo->parent);
    }

    public function test_childrenリレーション()
    {
        $user = User::factory()->create();
        $parent = $user->todos()->create([
            'title' => 'テスト',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31',
            'priority' => 2,
            'parent_id' => null
        ]);
        $todo = $user->todos()->create([
            'title' => 'テスト',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31',
            'priority' => 2,
            'parent_id' => $parent->id
        ]);
        $this->assertCount(1, $parent->children);
        $this->assertInstanceOf(Todo::class, $parent->children->first());
    }

    public function test_commentsリレーション()
    {
        $user = User::factory()->create();
        $todo = $user->todos()->create([
            'title' => 'テスト',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31',
            'priority' => 2,
        ]);

        $comment = Comment::create([
            'todo_id' => $todo->id,
            'user_id' => $user->id,
            'body' => 'リレーションテスト'
        ]);

        $this->assertCount(1, $todo->comments);
        $this->assertInstanceOf(Comment::class, $todo->comments->first());
        $this->assertEquals($comment->id, $todo->comments->first()->id);
    }

    public function test_tagsリレーション()
    {
        $user = User::factory()->create();
        $todo = $user->todos()->create([
            'title' => 'テスト',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31',
            'priority' => 2,
        ]);

        $tag = Tag::create([
            'user_id' => $user->id,
            'name' => 'リレーションテスト',
            'color' => 'gray'
        ]);

        $todo->tags()->sync($tag->id);

        $this->assertCount(1, $todo->tags);
        $this->assertInstanceOf(Tag::class, $todo->tags->first());
        $this->assertEquals($tag->id, $todo->tags->first()->id);
    }

    public function test_searchスコープ()
    {
        $user = User::factory()->create();
        $keyword = 'test';
        $todo = $user->todos()->create([
            'title' => $keyword,
            'priority' => 2,
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31'
        ]);

        // スコープを使用
        $scopeResults = Todo::query()->search($keyword)->get();

        // 手動クエリ（比較用）
        $results = Todo::where('title', '%' . $keyword, '%')
            ->orWhere('content', '%' . $keyword . '%')->get();

        $this->assertEquals($scopeResults, $results);
    }

    public function test_categoryスコープ()
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'リレーションテスト',
            'user_id' => $user->id,
            'color' => 'gray'
        ]);
        $todo = $user->todos()->create([
            'title' => 'test',
            'priority' => 2,
            'category_id' => $category->id,
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31'
        ]);

        $scopeResults = Todo::query()->category($category->id)->get();

        $results = Todo::where('category_id', $category->id)->get();

        $this->assertEquals($scopeResults, $results);
    }

    public function test_priorityスコープ()
    {
        $user = User::factory()->create();
        $priority = 2;
        $todo = $user->todos()->create([
            'title' => 'test',
            'priority' => $priority,
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31'
        ]);

        $scopeResults = Todo::query()->priority($priority)->get();

        $results = Todo::where('priority', $priority)->get();

        $this->assertEquals($scopeResults, $results);
    }

    public function test_dateRangeスコープ()
    {
        $user = User::factory()->create();
        $dateFrom = '2026-04-01';
        $dateTo = '2026-04-30';
        $todo = $user->todos()->create([
            'title' => 'test',
            'priority' => 2,
            'start_date' => $dateFrom,
            'end_date' => $dateTo
        ]);

        $scopeResults = Todo::dateRange($dateFrom, $dateTo)->get();

        $results = Todo::where('end_date', '>=', $dateFrom)
            ->where('end_date', '<=', $dateTo)->get();

        $this->assertEquals($scopeResults, $results);
    }

    public function test_completedFilterスコープ()
    {
        $user = User::factory()->create();
        $filter = 'active';
        $todo = $user->todos()->create([
            'title' => 'test',
            'priority' => 2,
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31',
            'completed_at' => null
        ]);

        $scopeResults = Todo::completedFilter($filter)->get();

        $results = Todo::whereNull('completed_at')->get();

        $this->assertEquals($scopeResults, $results);
    }
}
