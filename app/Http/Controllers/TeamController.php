<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\User;
use App\Models\TeamInvitation;
use Illuminate\Support\Facades\Notification;
use App\Notifications\TeamInvitationNotification;

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

    //招待送信
    public function invite(Request $request, Team $team)
    {
        //権限チェック
        $this->authorize('update', $team);

        $request->validate([
            'email' => 'required|email|max:255',
            'role' => 'required|in:owner,admin,member,viewer'
        ]);

        //既にメンバーかチェック
        $user = User::where('email', $request->email)->first();
        if ($user && $team->users()->where('user_id', $user->id)->exists()) {
            return back()->with('error', 'このユーザーは既にメンバーです');
        }

        //招待レコード登録
        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => $request->email,
            'token' => TeamInvitation::generateToken(),
            'role' => $request->role,
            'expires_at' => now()->addDays(7),
        ]);

        // Notification を使った匿名送信（アカウント不要でもメール送信可能）
        Notification::route('mail', $request->email)
            ->notify(new TeamInvitationNotification($invitation));

        return redirect()->route('teams.show', $team);
    }

    //招待受諾ページ表示
    public function showInvitation($token)
    {
        // 1. トークンで招待検索
        $invitation = TeamInvitation::where('token', $token)
            ->firstOrFail();

        // 2. 有効期限チェック
        if ($invitation->isExpired()) {
            abort(404, '招待の有効期限が切れています');
        }

        // 3. 受諾済みチェック
        if ($invitation->isAccepted()) {
            abort(404, 'この招待は既に使用されています');
        }

        // 4. ビュー表示
        return view('teams.invitations.show', compact('invitation'));
    }

    //招待受諾処理
    public function acceptInvitation($token)
    {
        // 1. 招待検索・バリデーション
        $invitation = TeamInvitation::where('token', $token)
            ->firstOrFail();

        //有効期限チェック
        if ($invitation->isExpired()) {
            return redirect()->route('teams.index')->with('error', '招待の有効期限が切れています');
        }

        //受諾済みチェック
        if ($invitation->isAccepted()) {
            return redirect()->route('teams.index')->with('error', 'この招待は既に使用されています');
        }

        // 2. ログインチェック
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'ログインしてください');
        }

        // 3. 既にメンバーかチェック
        if ($invitation->team->users()->where('user_id', auth()->id())->exists()) {
            return redirect()->route('teams.show', $invitation->team)
                ->with('error', 'このユーザーは既にメンバーです');
        }

        // 4. チームに追加
        $invitation->team->users()->attach(auth()->id(), ['role' => $invitation->role]);

        // 5. 招待を受諾済みにマーク
        $invitation->update([
            'accepted_at' => now()
        ]);

        // 6. リダイレクト
        return redirect()->route('teams.show', $invitation->team)->with('success', 'チームに追加しました');
    }
}
