<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\TodoRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Todo;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\TodoResource;

class TodoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $request->validate([
            'page' => 'nullable|integer',
            'per_page' => 'nullable|integer|max:100',
            'category_id' => 'nullable|integer|exists:categories,id',
            'tag_id' => 'nullable|integer|exists:tags,id',
            'priority' => 'nullable|integer|between:1,3',
            'status' => 'nullable|string|in:active,done,all',
            'sort' => 'nullable|string|in:created_at,updated_at,end_date,priority',
            'order' => 'nullable|string|in:asc,desc',
            'search' => 'nullable|string'
        ]);

        $query = auth()->user()->todos()->whereNull('parent_id');

        //カテゴリ
        $query->category($request->category_id);

        //タグ
        $query->tag($request->tag_id);

        //優先度
        $query->priority($request->priority);

        //完了状態
        $query->completedFilter($request->status);

        //ソート項目・ソート順
        $order = $request->order ?? 'asc';
        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'created_at':
                    $query->orderBy('created_at', $order);
                    break;
                case 'updated_at':
                    $query->orderBy('updated_at', $order);
                    break;
                case 'end_date':
                    $query->orderBy('end_date', $order);
                    break;
                case 'priority':
                    $query->orderBy('priority', $order);
                    break;
                default:
                    $query->orderBy('end_date', 'asc');
                    break;
            }
        }

        //タイトル・内容
        $query->search($request->search);

        //ページ
        $perPage = $request->per_page ?? 20;

        //一覧取得
        $todos = $query->with(['category', 'children', 'tags'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => TodoResource::collection($todos),
            'meta' =>
            [
                'current_page' => $todos->currentPage(),
                'last_page' => $todos->lastPage(),
                'per_page' => $todos->perPage(),
                'total' => $todos->total()
            ],
            'message' => 'Todo Index successfully'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
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

        if ($request->has('tags')) {
            $todo->tags()->sync($request->tags);
        }

        return response()->json([
            'success' => true,
            'data' => new TodoResource($todo),
            'message' => 'Todo Store successfully'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Todo $todo)
    {
        //権限チェック
        $this->authorize('view', $todo);

        return response()->json([
            'success' => true,
            'data' => new TodoResource($todo),
            'message' => 'Todo Show successfully'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
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
            //古い画像があれば削除
            if ($todo->image_path) {
                //画像削除
                Storage::disk('public')->delete($todo->image_path);
            }
            $path = $request->file('image')->store('todos', 'public');
            $todo->image_path = $path;
        }
        //中間テーブル
        $todo->tags()->sync($request->tags ?? []);

        $todo->save();

        return response()->json([
            'success' => true,
            'data' => new TodoResource($todo),
            'message' => 'Todo Update successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Todo $todo)
    {
        //権限チェック
        $this->authorize('delete', $todo);

        //画像削除
        if ($todo->image_path) {
            Storage::disk('public')->delete($todo->image_path);
        }

        $todo->delete();
        return response()->json([
            'success' => true,
            'data' => new TodoResource($todo),
            'message' => 'Todo delete successfully'
        ]);
    }

    //一括削除
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:todos,id'
        ]);

        // リクエスト: { "ids": [1, 2, 3] }
        $ids = $request->ids ?? null;

        if ($ids) {
            $todos = collect();
            foreach ($ids as $id) {
                $todo = Todo::findOrFail($id);

                //権限チェック
                $this->authorize('delete', $todo);

                $todos->push($todo);

                //画像削除
                if ($todo->image_path) {
                    Storage::disk('public')->delete($todo->image_path);
                }

                //Todo削除
                $todo->delete();
            }
        } else {
            $todos = collect();
        }

        return response()->json([
            'success' => true,
            'data' => TodoResource::collection($todos),
            'message' => 'Todo delete successfully'
        ]);
    }

    //一括更新
    public function bulkUpdate(Request $request)
    {
        // リクエスト: { "ids": [1, 2, 3], "category_id": 2, "priority": 1 }
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:todos,id'
        ]);

        $ids = $request->ids ?? null;
        if ($ids && ($request->has('category_id') || $request->has('priority'))) {
            $todos = collect();
            foreach ($ids as $id) {
                $todo = Todo::findOrFail($id);
                //権限チェック
                $this->authorize('update', $todo);

                $todos->push($todo);

                if ($request->has('category_id')) {
                    $todo->category_id = $request->category_id;
                }
                if ($request->has('priority')) {
                    $todo->priority = $request->priority;
                }

                $todo->save();
            }
        } else {
            $todos = collect();
        }

        return response()->json([
            'success' => true,
            'data' => TodoResource::collection($todos),
            'message' => 'Todo Update successfully'
        ]);
    }

    //一括完了/未完了
    public function bulkComplete(Request $request)
    {
        // リクエスト: { "ids": [1, 2, 3], "completed": true }
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:todos,id'
        ]);

        $ids = $request->ids ?? null;
        if ($ids && $request->has('completed')) {
            $todos = collect();
            foreach ($ids as $id) {
                $todo = Todo::findOrFail($id);
                //権限チェック
                $this->authorize('update', $todo);

                $todos->push($todo);

                $todo->completed_at = $request->completed ? now() : null;

                $todo->save();
            }
        } else {
            $todos = collect();
        }

        return response()->json([
            'success' => true,
            'data' => TodoResource::collection($todos),
            'message' => 'Todo Update successfully'
        ]);
    }
}
