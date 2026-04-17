<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Comment;

class CommentPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function update(User $user, Comment $comment)
    {
        return $comment->user_id === $user->id;
    }

    public function delete(User $user, Comment $comment)
    {
        return $comment->user_id ===  $user->id;
    }

    public function view(User $user, Comment $comment)
    {
        return $comment->user_id ===  $user->id;
    }
}
