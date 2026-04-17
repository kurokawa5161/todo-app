<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SavedSearch;


class SavedSearchPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function update(User $user, SavedSearch $savedSearch)
    {
        return $savedSearch->user_id === $user->id;
    }

    public function delete(User $user, SavedSearch $savedSearch)
    {
        return $savedSearch->user_id ===  $user->id;
    }

    public function view(User $user, SavedSearch $savedSearch)
    {
        return $savedSearch->user_id ===  $user->id;
    }
}
