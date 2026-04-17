<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Todo;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoModelTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // リレーションのテスト
    // ========================================

    public function test_userリレーション()
    {
        $user = User::factory()->create();
        $todo = $user->todos()->create([
            'title' => 'テスト',
            'end_date' => '2026-12-31',
            'priority' => 2
        ]);

        $this->assertInstanceOf(User::class, $todo->user);
        $this->assertEquals($user->id, $todo->user->id);
    }

    public function test_categoryリレーション()
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'テストカテゴリ',
            'user_id' => $user->id
        ]);

        $todo = $user->todos()->create([
            'title' => 'テスト',
            'category_id' => $category->id,
            'end_date' => '2026-12-31',
            'priority' => 2
        ]);

        $this->assertInstanceOf(Category::class, $todo->category);
        $this->assertEquals($category->id, $todo->category->id);
    }

    public function test_parentリレーション()
    {
        $user = User::factory()->create();

        $parent = $user->todos()->create([
            'title' => '親タスク',
            'end_date' => '2026-12-31',
            'priority' => 2
        ]);

        $child = $user->todos()->create([
            'title' => '子タスク',
            'parent_id' => $parent->id,
            'end_date' => '2026-12-31',
            'priority' => 2
        ]);

        $this->assertInstanceOf(Todo::class, $child->parent);
        $this->assertEquals($parent->id, $child->parent->id);
    }

    public function test_childrenリレーション()
    {
        $user = User::factory()->create();

        $parent = $user->todos()->create([
            'title' => '親タスク',
            'end_date' => '2026-12-31',
            'priority' => 2
        ]);

        $child = $user->todos()->create([
            'title' => '子タスク',
            'parent_id' => $parent->id,
            'end_date' => '2026-12-31',
            'priority' => 2
        ]);

        $this->assertCount(1, $parent->children);
        $this->assertInstanceOf(Todo::class, $parent->children->first());
        $this->assertEquals($child->id, $parent->children->first()->id);
    }

    public function test_commentsリレーション()
    {
        $user = User::factory()->create();

        $todo = $user->todos()->create([
            'title' => 'テスト',
            'end_date' => '2026-12-31',
            'priority' => 2
        ]);

        $comment = Comment::create([
            'todo_id' => $todo->id,
            'user_id' => $user->id,
            'content' => 'テストコメント'
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
            'end_date' => '2026-12-31',
            'priority' => 2
        ]);

        $tag = Tag::create([
            'name' => 'テストタグ',
            'user_id' => $user->id
        ]);

        $todo->tags()->attach($tag->id);

        $this->assertCount(1, $todo->tags);
        $this->assertInstanceOf(Tag::class, $todo->tags->first());
        $this->assertEquals($tag->id, $todo->tags->first()->id);
    }

    // ========================================
    // スコープのテスト
    // ========================================

    public function test_searchスコープ()
    {
        $user = User::factory()->create();

        $todo1 = $user->todos()->create([
            'title' => 'Laravel勉強',
            'content' => 'テスト内容',
            'end_date' => '2026-12-31',
            'priority' => 2
        ]);

        $todo2 = $user->todos()->create([
            'title' => 'PHP勉強',
            'content' => '別の内容',
            'end_date' => '2026-12-31',
            'priority' => 2
        ]);

        $results = Todo::search('Laravel')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($todo1->id, $results->first()->id);
    }

    public function test_categoryスコープ()
    {
        $user = User::factory()->create();

        $category = Category::create([
            'name' => 'テストカテゴリ',
            'user_id' => $user->id
        ]);

        $todo1 = $user->todos()->create([
            'title' => 'カテゴリ有り',
            'category_id' => $category->id,
            'end_date' => '2026-12-31',
            'priority' => 2
        ]);

        $todo2 = $user->todos()->create([
            'title' => 'カテゴリ無し',
            'end_date' => '2026-12-31',
            'priority' => 2
        ]);

        $results = Todo::category($category->id)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($todo1->id, $results->first()->id);
    }

    public function test_priorityスコープ()
    {
        $user = User::factory()->create();

        $todo1 = $user->todos()->create([
            'title' => '高優先度',
            'priority' => 1,
            'end_date' => '2026-12-31'
        ]);

        $todo2 = $user->todos()->create([
            'title' => '低優先度',
            'priority' => 3,
            'end_date' => '2026-12-31'
        ]);

        $results = Todo::priority(1)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($todo1->id, $results->first()->id);
    }

    public function test_dateRangeスコープ()
    {
        $user = User::factory()->create();

        $todo1 = $user->todos()->create([
            'title' => '範囲内',
            'end_date' => '2026-04-15',
            'priority' => 2
        ]);

        $todo2 = $user->todos()->create([
            'title' => '範囲外',
            'end_date' => '2026-05-15',
            'priority' => 2
        ]);

        $results = Todo::dateRange('2026-04-01', '2026-04-30')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($todo1->id, $results->first()->id);
    }

    public function test_completedFilterスコープ_active()
    {
        $user = User::factory()->create();

        $todo1 = $user->todos()->create([
            'title' => '未完了',
            'end_date' => '2026-12-31',
            'priority' => 2,
            'completed_at' => null
        ]);

        $todo2 = $user->todos()->create([
            'title' => '完了済み',
            'end_date' => '2026-12-31',
            'priority' => 2,
            'completed_at' => now()
        ]);

        $results = Todo::completedFilter('active')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($todo1->id, $results->first()->id);
    }

    public function test_completedFilterスコープ_done()
    {
        $user = User::factory()->create();

        $todo1 = $user->todos()->create([
            'title' => '未完了',
            'end_date' => '2026-12-31',
            'priority' => 2,
            'completed_at' => null
        ]);

        $todo2 = $user->todos()->create([
            'title' => '完了済み',
            'end_date' => '2026-12-31',
            'priority' => 2,
            'completed_at' => now()
        ]);

        $results = Todo::completedFilter('done')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($todo2->id, $results->first()->id);
    }
}
