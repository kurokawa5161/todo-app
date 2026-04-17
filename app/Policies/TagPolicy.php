<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Tag;

class TagPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function update(User $user, Tag $tag)
    {
        return $tag->user_id === $user->id;
    }

    public function delete(User $user, Tag $tag)
    {
        return $tag->user_id ===  $user->id;
    }

    public function view(User $user, Tag $tag)
    {
        return $tag->user_id ===  $user->id;
    }
}
