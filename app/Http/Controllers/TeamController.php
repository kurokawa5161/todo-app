<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\User;

class TeamController extends Controller
{
    public function index()
    {
        //権限チェック
        $this->authorize('viewAny', Team::class);

        $teams = auth()->user()->teams()->withCount('users')->get();

        $data = [
            'teams' => $teams
        ];

        return view('teams.index', $data);
    }

    public function store(Request $request)
    {
        //権限チェック
        $this->authorize('create', Team::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $team = Team::create($validated);

        //中間テーブル　作成者をOwnerとして追加
        $team->users()->attach(auth()->id(), ['role' => 'owner']);

        return redirect()->route('teams.show', $team);
    }

    public function show(Team $team)
    {
        //権限チェック
        $this->authorize('view', $team);

        return view('teams.show', compact('team'));
    }

    public function update(Request $request, Team $team)
    {
        //権限チェック
        $this->authorize('update', $team);

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $team->name = $request->name;
        $team->save();

        return redirect()->route('teams.show', $team)->with('success', 'チーム情報を更新しました');
    }

    public function destroy(Request $request, Team $team)
    {
        //権限チェック
        $this->authorize('delete', $team);

        $team->delete();

        return redirect()->route('teams.index');
    }

    //チームに対してメンバーを追加
    public function addMember(Request $request, Team $team)
    {
        //権限チェック
        $this->authorize('update', $team);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:owner,admin,member,viewer',
        ]);

        //中間テーブル
        $team->users()->attach($validated['user_id'], ['role' => $validated['role']]);

        return redirect()->route('teams.show', $team);
    }

    //チームに対してメンバーを削除する
    public function removeMember(Request $request, Team $team, User $user)
    {

        //権限チェック
        $this->authorize('delete', $team);

        //中間テーブル
        $team->users()->detach($user->id);

        return redirect()->route('teams.show', $team);
    }

    //チームのメンバーの権限を変更する
    public function updateRole(Request $request, Team $team, User $user)
    {

        //権限チェック
        $this->authorize('update', $team);

        $validated = $request->validate([
            'role' => 'required|in:owner,admin,member,viewer'
        ]);

        //中間テーブル
        $team->users()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        return redirect()->route('teams.show', $team);
    }
}
