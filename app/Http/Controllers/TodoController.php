<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\TodoRequest;
use App\Models\Todo;
use App\Models\Team;
use App\Models\Comment;
use App\Models\TodoTag;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Models\SearchHistory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Policies\TodoPolicy;
use App\Events\TodoCreated;
use App\Events\TodoUpdated;
use App\Events\TodoDeleted;
use Illuminate\Support\Facades\Log;
use App\Notifications\TodoSlackNotification;
use App\Notifications\TodoAssignedNotification;
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\ValueObject\DateTime;
use Eluceo\iCal\Domain\ValueObject\TimeSpan;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use App\Services\GitHubService;
use Illuminate\Pagination\LengthAwarePaginator;

class TodoController extends Controller
{

    public function index(Request $request)
    {
        $user = auth()->user();

        //検索キーワードがある場合はScout検索
        if ($request->q) {
            $searchQuery = Todo::search($request->q);

            //フィルター適用
            if ($request->category_id) {
                $searchQuery->where('category_id', $request->category_id);
            }

            if ($request->priority) {
                $searchQuery->where('priority', $request->priority);
            }

            //ソート
            switch ($request->sort) {
                case 'end_date_asc':
                    $searchQuery->orderBy('end_date', 'asc');
                    break;
                case 'end_date_desc':
                    $searchQuery->orderBy('end_date', 'desc');
                    break;
                case 'created_at_desc':
                    $searchQuery->orderBy('created_at', 'desc');
                    break;
                case 'priority_asc':
                    $searchQuery->orderBy('priority', 'asc');
                    break;
                case 'title_asc':
                    $searchQuery->orderBy('title', 'asc');
                    break;
            }

            //検索結果を取得
            $searchResults = $searchQuery->get();

            //ユーザーフィルター（検索結果から絞り込み）
            $searchResults = $searchResults->where('user_id', $user->id);

            //期間
            if ($request->date_from) {
                $searchResults = $searchResults->filter(function ($todo) use ($request) {
                    return $todo->end_date >= $request->date_from;
                });
            }
            if ($request->date_to) {
                $searchResults = $searchResults->filter(function ($todo) use ($request) {
                    return $todo->end_date <= $request->date_to;
                });
            }

            if ($request->filter === 'active') {
                $searchResults = $searchResults->whereNull('completed_at');
            } elseif ($request->filter === 'done') {
                $searchResults = $searchResults->whereNotNull('completed_at');
            }

            $perPage = $request->input('per_page', 10);
            $currentPage = request()->get('page', 1);
            $items = new LengthAwarePaginator(
                $searchResults->forPage($currentPage, $perPage),
                $searchResults->count(),
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );

            $items->load([
                'category',
                'children.category',
                'children.tags',
                'tags',
                'user',
                'assignedUser'
            ]);

            //検索履歴を保存（空でない場合のみ）
            $keyword = trim($request->q);
            if ($keyword) {
                SearchHistory::create([
                    'user_id' => $user->id,
                    'keyword' => $keyword,
                    'result_count' => $items->total()
                ]);
            }
        } else {
            $query = $user->todos()
                ->whereNull('parent_id')
                ->with([
                    'category',
                    'children.category',
                    'children.tags',
                    'tags',
                    'user',
                    'assignedUser'
                ]);

            // ========================================
            // 絞り込み条件
            // ========================================
            $query->completedFilter($request->filter)
                ->category($request->category_id)
                ->priority($request->priority)
                ->dateRange($request->date_from, $request->date_to);

            // ========================================
            // 並び替え
            // ========================================
            $query->orderBy('is_pinned', 'desc');
            switch ($request->sort) {
                case 'end_date_asc':
                    $query->orderBy('end_date', 'asc');
                    break;
                case 'end_date_desc':
                    $query->orderBy('end_date', 'desc');
                    break;
                case 'created_at_desc':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'priority_asc':
                    $query->orderBy('priority', 'asc');
                    break;
                case 'title_asc':
                    $query->orderBy('title', 'asc');
                    break;
                default:
                    $query->orderBy('end_date', 'asc');
                    break;
            }
            $perPage = $request->input('per_page', 10);
            $items = $query->paginate($perPage)->appends($request->except('page'));
        }

        //カテゴリ
        $categories = Cache::remember('user_' . auth()->id() . '_categories', 3600, function () {
            return auth()->user()->categories()->orderBy('created_at', 'asc')->get();
        });

        //タグ
        $tags = Cache::remember('user_' . auth()->id() . '_tags', 3600, function () {
            return auth()->user()->tags()->orderBy('created_at', 'asc')->get();
        });

        //検索条件
        $savedSearches = Cache::remember('user_' . auth()->id() . '_saved_searches', 3600, function () {
            return auth()->user()->savedSearches()->orderBy('created_at', 'asc')->get();
        });

        //すべて・完了済・未完了の件数
        $counts = auth()->user()->todos()->selectRaw(
            'COUNT(*) as total,
            COUNT(CASE WHEN completed_at IS NULL THEN 1 END) as active,
            COUNT(CASE WHEN completed_at IS NOT NULL THEN 1 END) as done'
        )->whereNull('parent_id')->first();

        //検索履歴
        $recentSearches = SearchHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')->limit(10)->get();

        $data = [
            'items' => $items,
            'filter' => $request->filter,
            'categories' => $categories,
            'sort' => $request->sort,
            'counts' => $counts,
            'tags' => $tags,
            'savedSearches' => $savedSearches,
            'recentSearches' => $recentSearches
        ];

        return view('todos.index', $data);
    }

    public function suggest(Request $request)
    {
        $query = $request->input('q', '');
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $suggestions = SearchHistory::where('user_id', auth()->id())
            ->where('keyword', 'like', $query . '%')
            ->select('keyword')
            ->distinct()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->pluck('keyword');

        return response()->json($suggestions);
    }

    public function store(TodoRequest $request, ?Team $team = null)
    {
        //チームTodoの場合のみ権限チェック
        if ($request->team_id) {
            $team = Team::findOrFail($request->team_id);
            //権限チェック
            $this->authorize('createTeamTodo', $team);
        }

        //todoテーブル
        $todo = new Todo();
        $todo->user_id = auth()->id();
        $todo->title = $request->title;
        $todo->content = $request->content;
        $todo->start_date = $request->start_date;
        $todo->end_date = $request->end_date;
        $todo->category_id = $request->category_id;
        $todo->priority = $request->priority ?: 2;
        $todo->parent_id = $request->parent_id;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('todos', 'public');
            $todo->image_path = $path;
        }
        $todo->team_id = $request->team_id;
        $todo->save();

        //アラート
        event(new TodoCreated($todo));
        //Slack通知
        $todo->user->notify(new TodoSlackNotification($todo, 'created'));

        //中間テーブル（タグ紐づけ）
        if ($request->has('tags')) {
            $todo->tags()->attach($request->tags);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'todo' => [
                    'id' => $todo->id,
                    'title' => $todo->title,
                    'completed' => $todo->completed_at !== null
                ]
            ]);
        }

        //リダイレクト先を条件分岐
        if ($request->team_id) {
            return redirect()->route('teams.show', $team);
        } else {
            return redirect()->route('todos.index');
        }
    }

    public function edit(Todo $todo, ?Team $team = null)
    {
        //チームTodoの場合のみ権限チェック
        if ($todo->team_id) {
            $team = Team::findOrFail($todo->team_id);
            $this->authorize('updateTeamTodo', [$team, $todo]);
        } else {
            $this->authorize('update', $todo);
        }

        $todo->load([
            'comments.user',
            'category',
            'tags',
            'assignedUser',
            'user'
        ]);

        // カテゴリとタグの取得
        if ($todo->team_id && $team) {
            // チームTodoの場合：チーム全体のカテゴリとタグを取得
            $teamUserIds = $team->users()->pluck('users.id');
            $categories = Category::whereIn('user_id', $teamUserIds)->orderBy('created_at', 'asc')->get();
            $tags = Tag::whereIn('user_id', $teamUserIds)->orderBy('name', 'asc')->get();
        } else {
            // 個人Todoの場合：自分のカテゴリとタグのみ
            $categories = auth()->user()->categories()->orderBy('created_at', 'asc')->get();
            $tags = auth()->user()->tags()->orderBy('name', 'asc')->get();
        }

        //ユーザー
        $users = User::all();

        return view('todos.edit', [
            'todo' => $todo,
            'categories' => $categories,
            'tags' => $tags,
            'team' => $team,
            'users' => $users
        ]);
    }

    public function update(TodoRequest $request, Todo $todo, ?Team $team = null)
    {
        //チームTodoの場合のみ権限チェック
        if ($todo->team_id) {
            $team = Team::findOrFail($todo->team_id);
            //権限チェック
            $this->authorize('updateTeamTodo', [$team, $todo]);
        } else {
            //権限チェック
            $this->authorize('update', $todo);
        }

        //assigned_toが変更された場合に追加
        if ($request->has('assigned_to') && $request->assigned_to != $todo->assigned_to) {
            $todo->assigned_to = $request->assigned_to;

            //新しく割り当てられたユーザーに追加
            if ($request->assigned_to) {
                $assignedUser = User::find($request->assigned_to);
                if ($assignedUser) {
                    $assignedUser->notify(new TodoAssignedNotification($todo, $request->user()));
                }
            }
        }

        $todo->title = $request->title;
        $todo->content = $request->content;
        $todo->start_date = $request->start_date;
        $todo->end_date = $request->end_date;
        $todo->category_id = $request->category_id;
        $todo->priority = $request->priority;
        if ($request->hasFile('image')) {
            //画像削除
            Storage::disk('public')->delete($todo->image_path);

            $path = $request->file('image')->store('todos', 'public');
            $todo->image_path = $path;
        }
        //中間テーブル
        $todo->tags()->sync($request->tags ?? []);

        $todo->save();

        //アラート
        event(new TodoUpdated($todo));
        //Slack通知
        $todo->user->notify(new TodoSlackNotification($todo, 'updated'));

        //リダイレクト先を条件分岐
        if ($todo->team_id) {
            return redirect()->route('teams.show', $team);
        } else {
            return redirect()->route('todos.index');
        }
    }

    public function toggle(Request $request, Todo $todo, ?Team $team = null)
    {
        //チームTodoの場合のみ権限チェック
        if ($todo->team_id) {
            $team = Team::findOrFail($todo->team_id);
            //権限チェック
            $this->authorize('updateTeamTodo', [$team, $todo]);
        } else {
            //権限チェック
            $this->authorize('update', $todo);
        }

        $todo->completed_at = $todo->completed_at ? NULL : now();
        $todo->save();

        //GitHub Issueがあれば閉じる
        if ($todo->completed_at && $todo->github_issue_url) {
            $githubService = new GitHubService();
            $githubService->closeIssue($todo->github_issue_url);
        }

        //Slack通知
        if ($todo->completed_at) {
            $todo->user->notify(new TodoSlackNotification($todo, 'completed'));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'completed' => $todo->completed_at !== null,
                'completed_at' => $todo->completed_at?->format('Y-m-d H:i:s')
            ]);
        }

        //リダイレクト先を条件分岐
        if ($todo->team_id) {
            return redirect()->route('teams.show', $team);
        } else {
            return redirect()->route('todos.index');
        }
    }

    public function destroy(Todo $todo, ?Team $team = null)
    {
        //チームTodoの場合のみ権限チェック
        if ($todo->team_id) {
            $team = Team::findOrFail($todo->team_id);
            //権限チェック
            $this->authorize('deleteTeamTodo', [$team, $todo]);
        } else {
            //権限チェック
            $this->authorize('delete', $todo);
        }

        //画像削除
        if ($todo->image_path) {
            Storage::disk('public')->delete($todo->image_path);
        }

        //アラート
        event(new TodoDeleted($todo));

        $todo->delete();

        //リダイレクト先を条件分岐
        if ($todo->team_id) {
            return redirect()->route('teams.show', $team);
        } else {
            return redirect()->route('todos.index');
        }
    }

    public function togglePin(Request $request, Todo $todo, ?Team $team = null)
    {
        //チームTodoの場合のみ権限チェック
        if ($todo->team_id) {
            $team = Team::findOrFail($todo->team_id);
            //権限チェック
            $this->authorize('updateTeamTodo', [$team, $todo]);
        } else {
            //権限チェック
            $this->authorize('update', $todo);
        }

        $todo->is_pinned = !$todo->pinned;

        $todo->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'is_pinned' => $todo->is_pinned,
            ]);
        }

        //リダイレクト先を条件分岐
        if ($todo->team_id) {
            return redirect()->route('teams.show', $team);
        } else {
            return redirect()->route('todos.index');
        }
    }

    public function exportCalendar(todo $todo)
    {
        //権限チェック
        $this->authorize('view', $todo);

        //イベント作成
        $event = new Event();
        $event->setSummary($todo->title)
            ->setDescription($todo->content ?? '')
            ->setOccurrence(
                new TimeSpan(
                    new DateTime($todo->start_date ?? $todo->end_date, true),
                    new DateTime($todo->end_date, true)
                )
            );

        //カレンダー作成
        $calendar = new Calendar([$event]);

        //.ics生成
        $componentFactory = new CalendarFactory();
        $calendarComponent = $componentFactory->createCalendar($calendar);

        //ダウンロード
        return response($calendarComponent)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="todo-' . $todo->id . '.ics"');
    }
}
