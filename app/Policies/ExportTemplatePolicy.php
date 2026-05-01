<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ExportTemplate;

class ExportTemplatePolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function update(User $user, ExportTemplate $exportTemplate)
    {
        return $exportTemplate->user_id === $user->id;
    }

    public function delete(User $user, ExportTemplate $exportTemplate)
    {
        return $exportTemplate->user_id ===  $user->id;
    }

    public function view(User $user, ExportTemplate $exportTemplate)
    {
        return $exportTemplate->user_id ===  $user->id;
    }
}
