<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = auth()->user()->categories()->orderBy('created_at', 'asc')->get();
        $data = [
            'categories' => $categories,
        ];
        return view('category.index', $data);
    }

    public function store(CategoryRequest $request)
    {
        $category = new Category();
        $category->name = $request->name;
        $category->user_id = auth()->id();
        $category->color = $request->color;
        $category->save();

        //キャッシュ削除
        Cache::forget('user_' . auth()->id() . '_categories');

        return redirect()->route('categories.index');
    }

    public function destroy(Category $category)
    {
        //権限チェック
        $this->authorize('delete', $category);

        $category->delete();

        //キャッシュ削除
        Cache::forget('user_' . auth()->id() . '_categories');

        return redirect()->route('categories.index');
    }
}
