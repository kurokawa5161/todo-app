<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\TodoRequest;
use App\Models\Todo;
use App\Models\Comment;
use Illuminate\Support\Facades\Storage;

class TodoController extends Controller
{
    public function index(Request $request)
    {
        $query = auth()->user()->todos()->whereNull('parent_id')->with(['category', 'children']);

        //絞り込み
        $filter = $request->filter;
        if ($filter == 'active') {
            $query->whereNull('completed_at');
        } elseif ($filter == 'done') {
            $query->whereNotNull('completed_at');
        }
        if ($request->q) {
            $title = $request->q;
            $query = $query->where('title', 'like', '%' . $title . '%');
        }
        //並び替え
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

        $categories = auth()->user()->categories()->orderBy('created_at', 'asc')->get();

        //すべて・完了済・未完了の件数
        $counts = auth()->user()->todos()->selectRaw(
            'COUNT(*) as total,
            COUNT(CASE WHEN completed_at IS NULL THEN 1 END) as active,
            COUNT(CASE WHEN completed_at IS NOT NULL THEN 1 END) as done'
        )->whereNull('parent_id')->first();

        $data = [
            'items' => $items,
            'filter' => $filter,
            'categories' => $categories,
            'sort' => $request->sort,
            'counts' => $counts
        ];
        return view('todos.index', $data);
    }

    public function store(TodoRequest $request)
    {
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
        return redirect()->route('todos.index');
    }

    public function edit(Todo $todo)
    {
        $todo->load('comments.user');
        $categories = auth()->user()->categories()->orderBy('created_at', 'asc')->get();
        $data = [
            'item' => $todo,
            'categories' => $categories,
        ];
        return view('todos.edit', $data);
    }

    public function update(TodoRequest $request, Todo $todo)
    {
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
        $todo->save();

        return redirect()->route('todos.index');
    }

    public function toggle(Request $request, Todo $todo)
    {
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
        //画像削除
        if ($todo->image_path) {
            Storage::disk('public')->delete($todo->image_path);
        }
        $todo->delete();
        return redirect()->route('todos.index');
    }

    public function togglePin(Request $request, Todo $todo)
    {
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
