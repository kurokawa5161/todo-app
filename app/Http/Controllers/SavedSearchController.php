<?php

namespace App\Http\Controllers;

use App\Models\SavedSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SavedSearchController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        //検索条件
        $conditions = [
            'filter' => $request->filter,
            'q' => $request->q,
            'category_id' => $request->category_id,
            'priority' => $request->priority,
            'sort' => $request->sort,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ];
        //nullを削除
        $conditions = array_filter($conditions, fn($value) => $value !== null);

        $savedSearch = new SavedSearch();
        $savedSearch->user_id = auth()->id();
        $savedSearch->name = $request->name;
        $savedSearch->conditions = $conditions;
        $savedSearch->save();

        //キャッシュ削除
        Cache::forget('user_' . auth()->id() . '_saved_searches');

        return redirect()->route('todos.index');
    }

    //保存済み検索を適用
    public function apply(SavedSearch $savedSearch)
    {
        if ($savedSearch->user_id !== auth()->id()) {
            abort(403);
        }
        return redirect()->route('todos.index', $savedSearch->conditions);
    }

    //保存済み検索を削除
    public function destroy(SavedSearch $savedSearch)
    {
        if ($savedSearch->user_id !== auth()->id()) {
            abort(403);
        }
        $savedSearch->delete();

        //キャッシュ削除
        Cache::forget('user_' . auth()->id() . '_saved_searches');

        return redirect()->route('todos.index');
    }
}
