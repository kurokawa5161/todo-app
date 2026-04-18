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
    public function index()
    {
        $query = auth()->user()->todos()->whereNull('parent_id')->with(['category', 'children', 'tags'])->get();
        return response()->json([
            'success' => true,
            'data' => TodoResource::collection($query),
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
}
