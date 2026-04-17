<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Todo;
use Illuminate\Support\Facades\Log;

class TodoPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function update(User $user, Todo $todo)
    {
        return $todo->user_id === $user->id;
    }

    public function delete(User $user, Todo $todo)
    {
        return $todo->user_id ===  $user->id;
    }

    public function view(User $user, Todo $todo)
    {
        return $todo->user_id ===  $user->id;
    }
}
