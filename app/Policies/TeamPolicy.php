<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TeamPolicy
{
    /**
     * チームメンバーかどうか判定
     */
    public function isMember(User $user, Team $team)
    {
        return $team->users->contains($user->id);
    }

    /**
     * ユーザーの権限を取得
     */
    public function getUserRole(User $user, Team $team)
    {
        return $team->users()->where('user_id', $user->id)
            ->first()?->pivot->role;
    }

    /**
     * 一覧表示（全ユーザー可能）
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * チーム閲覧（メンバー全員可能）
     */
    public function view(User $user, Team $team): bool
    {
        return $this->isMember($user, $team);
    }

    /**
     * チーム作成（全ユーザー可能）
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * チーム編集（Owner/Adminのみ）
     */
    public function update(User $user, Team $team): bool
    {
        $role = $this->getUserRole($user, $team);
        return in_array($role, ['owner', 'admin']);
    }

    /**
     * チーム削除（Ownerのみ）
     */
    public function delete(User $user, Team $team): bool
    {
        $role = $this->getUserRole($user, $team);
        return $role === 'owner';
    }
}
