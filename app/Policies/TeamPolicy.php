<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use App\Models\Todo;
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

    /**
     * チームTodoに関する権限ルール
     * 閲覧（view）: メンバー全員（Viewer含む）
     */
    public function viewTeamTodo(User $user, Team $team): bool
    {
        return $this->isMember($user, $team);
    }

    /**
     * チームTodoに関する権限ルール
     * 作成（create）: Member以上
     */
    public function createTeamTodo(User $user, Team $team): bool
    {
        // メンバーでない場合は作成不可
        if (!$this->isMember($user, $team)) {
            return false;
        }

        $role = $this->getUserRole($user, $team);
        return in_array($role, ['owner', 'admin', 'member']);
    }

    /**
     * チームTodoに関する権限ルール
     * 編集（update）: Todo作成者本人 または Admin/Owner
     */
    public function updateTeamTodo(User $user, Team $team, Todo $todo): bool
    {
        // メンバーでない場合は編集不可
        if (!$this->isMember($user, $team)) {
            return false;
        }

        $role = $this->getUserRole($user, $team);

        // 作成者本人 または Admin/Owner
        return ($todo->user_id == $user->id || in_array($role, ['owner', 'admin']));
    }

    /**
     * チームTodoに関する権限ルール
     * 削除（delete）: 作成者本人 または Admin/Owner
     */
    public function deleteTeamTodo(User $user, Team $team, Todo $todo): bool
    {
        // メンバーでない場合は削除不可
        if (!$this->isMember($user, $team)) {
            return false;
        }

        $role = $this->getUserRole($user, $team);

        // 作成者本人 または Admin/Owner
        return ($todo->user_id == $user->id || in_array($role, ['owner', 'admin']));
    }
}
