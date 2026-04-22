<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\TodoRequest;
use App\Models\Todo;
use App\Models\Comment;
use App\Models\TodoTag;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Policies\TodoPolicy;

class TodoController extends Controller
{
    public function index(Request $request)
    {
        $query = auth()->user()->todos()->whereNull('parent_id')->with(['category', 'children', 'tags']);

        // ========================================
        // 絞り込み条件
        // ========================================
        $query->completedFilter($request->filter)
            ->search($request->q)
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
        $items = $query->paginate(5);

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

        $data = [
            'items' => $items,
            'filter' => $request->filter,
            'categories' => $categories,
            'sort' => $request->sort,
            'counts' => $counts,
            'tags' => $tags,
            'savedSearches' => $savedSearches
        ];
        return view('todos.index', $data);
    }

    public function store(TodoRequest $request)
    {
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
        $todo->save();

        //中間テーブル
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
        return redirect()->route('todos.index');
    }

    public function edit(Todo $todo)
    {
        //権限チェック
        $this->authorize('update', $todo);

        $todo->load('comments.user');
        $categories = auth()->user()->categories()->orderBy('created_at', 'asc')->get();
        $tags = auth()->user()->tags()->orderBy('name', 'asc')->get();

        $data = [
            'item' => $todo,
            'categories' => $categories,
            'tags' => $tags
        ];
        return view('todos.edit', $data);
    }

    public function update(TodoRequest $request, Todo $todo)
    {
        //権限チェック
        $this->authorize('update', $todo);

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

        return redirect()->route('todos.index');
    }

    public function toggle(Request $request, Todo $todo)
    {
        //権限チェック
        $this->authorize('update', $todo);

        $todo->completed_at = $todo->completed_at ? NULL : now();
        $todo->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'completed' => $todo->completed_at !== null,
                'completed_at' => $todo->completed_at?->format('Y-m-d H:i:s')
            ]);
        }

        return redirect()->route('todos.index');
    }

    public function destroy(Todo $todo)
    {
        //権限チェック
        $this->authorize('delete', $todo);

        //画像削除
        if ($todo->image_path) {
            Storage::disk('public')->delete($todo->image_path);
        }
        $todo->delete();
        return redirect()->route('todos.index');
    }

    public function togglePin(Request $request, Todo $todo)
    {
        //権限チェック
        $this->authorize('update', $todo);

        if ($todo->is_pinned) {
            $todo->is_pinned = FALSE;
        } else {
            $todo->is_pinned = TRUE;
        }
        $todo->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'is_pinned' => $todo->is_pinned,
            ]);
        }
        return redirect()->route('todos.index');
    }
}
