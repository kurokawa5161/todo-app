<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Todo;

class CommentController extends Controller
{
    public function store(Request $request, Todo $todo)
    {
        $request->validate([
            'body' => 'required|string|max:1000'
        ]);
        $todo->comments()->create([
            'body' => $request->body,
            'user_id' => auth()->id()
        ]);
        return redirect()->back();
    }

    public function destroy(Comment $comment)
    {
        if ($comment->user_id == auth()->id()) {
            $comment->delete();
            return redirect()->back();
        } else {
            abort(403);
        }
    }
}
