<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\TodoRequest;
use App\Models\Todo;

class TodoController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->filter;
        $query = auth()->user()->todos()->with('category')->orderBy('end_date', 'asc');
        if ($filter == 'active') {
            $query->whereNull('completed_at');
        } elseif ($filter == 'done') {
            $query->whereNotNull('completed_at');
        }
        if ($request->q) {
            $title = $request->q;
            $query = $query->where('title', 'like', '%' . $title . '%');
        }

        $items = $query->paginate(5);
        $categories = auth()->user()->categories()->orderBy('created_at', 'asc')->get();
        $data = [
            'items' => $items,
            'filter' => $filter,
            'categories' => $categories
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
        $todo->save();
        return redirect()->route('todos.index');
    }

    public function edit(Todo $todo)
    {
        $categories = auth()->user()->categories()->orderBy('created_at', 'asc')->get();
        $data = [
            'item' => $todo,
            'categories' => $categories
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
        $todo->save();
        return redirect()->route('todos.index');
    }

    public function toggle(Todo $todo)
    {
        $todo->completed_at = $todo->completed_at ? NULL : now();
        $todo->save();
        return redirect()->route('todos.index');
    }


    public function destroy(Todo $todo)
    {
        $todo->delete();
        return redirect()->route('todos.index');
    }
}
