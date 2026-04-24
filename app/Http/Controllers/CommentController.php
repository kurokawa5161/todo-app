<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Todo;
use App\Notifications\TodoCommentNotification;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    public function store(Request $request, Todo $todo)
    {
        $request->validate([
            'body' => 'required|string|max:1000'
        ]);

        $comment = $todo->comments()->create([
            'body' => $request->body,
            'user_id' => auth()->id()
        ]);

        //自分以外のTodoにコメントした場合のみ通知
        Log::info('Comment created', [
            'todo_user_id' => $todo->user_id,
            'auth_user_id' => auth()->id(),
            'condition' => $todo->user_id !== auth()->id()
        ]);

        //自分以外のTodoにコメントした場合のみ通知
        if ($todo->user_id !== auth()->id()) {
            Log::info('Sending notification to user', ['user_id' => $todo->user_id]);
            $todo->user->notify(new TodoCommentNotification($todo, $comment));
            Log::info('Notification sent');
        }

        return redirect()->back();
    }

    public function destroy(Comment $comment)
    {
        //権限チェック
        $this->authorize('delete', $comment);

        $comment->delete();
        return redirect()->back();
    }
}
