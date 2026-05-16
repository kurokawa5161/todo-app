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
        Cache::tags(['user:' . auth()->id(), 'saved_searches'])->flush();

        return redirect()->route('todos.index');
    }

    //保存済み検索を適用
    public function apply(SavedSearch $savedSearch)
    {
        //権限チェック
        $this->authorize('view', $savedSearch);

        return redirect()->route('todos.index', $savedSearch->conditions);
    }

    //保存済み検索を削除
    public function destroy(SavedSearch $savedSearch)
    {
        //権限チェック
        $this->authorize('delete', $savedSearch);

        $savedSearch->delete();

        //キャッシュ削除
        Cache::tags(['user:' . auth()->id(), 'saved_searches'])->flush();

        return redirect()->route('todos.index');
    }
}
