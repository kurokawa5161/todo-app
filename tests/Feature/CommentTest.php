<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Todo;
use App\Models\Comment;
use App\Notifications\TodoCommentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログイン済みユーザーはコメントを作成できる()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/todos/{$todo->id}/comments", [
            'body' => 'これはテストコメントです'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'body' => 'これはテストコメントです',
            'user_id' => $user->id,
            'todo_id' => $todo->id
        ]);
    }

    public function test_コメント本文が空ではバリデーションエラー()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/todos/{$todo->id}/comments", [
            'body' => ''
        ]);

        $response->assertSessionHasErrors('body');
    }

    public function test_コメント本文が1000文字を超えるとバリデーションエラー()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/todos/{$todo->id}/comments", [
            'body' => str_repeat('a', 1001)
        ]);

        $response->assertSessionHasErrors('body');
    }

    public function test_他人のTodoにコメントすると通知が送信される()
    {
        Notification::fake();

        $owner = User::factory()->create();
        $commenter = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($commenter)->post("/todos/{$todo->id}/comments", [
            'body' => 'これはテストコメントです'
        ]);

        // 通知が送信されたことを確認
        Notification::assertSentTo(
            $owner,
            TodoCommentNotification::class
        );
    }

    public function test_自分のTodoにコメントしても通知は送信されない()
    {
        Notification::fake();

        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post("/todos/{$todo->id}/comments", [
            'body' => 'これはテストコメントです'
        ]);

        // 通知が送信されていないことを確認
        Notification::assertNothingSent();
    }

    public function test_自分のコメントは削除できる()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'todo_id' => $todo->id
        ]);

        $response = $this->actingAs($user)->delete("/comments/{$comment->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_他人のコメントは削除できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $otherUser->id]);
        $comment = Comment::factory()->create([
            'user_id' => $otherUser->id,
            'todo_id' => $todo->id
        ]);

        $response = $this->actingAs($user)->delete("/comments/{$comment->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }
}
