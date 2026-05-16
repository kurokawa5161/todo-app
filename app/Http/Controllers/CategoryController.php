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
        $categories = Cache::tags(['user:' . auth()->id(), 'categories'])
            ->remember('user_categories', 3600, function () {
                return auth()->user()->categories()->get();
            });

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

        //削除
        Cache::tags(['user:' . auth()->id(), 'categories'])->flush();

        return redirect()->route('categories.index');
    }

    public function destroy(Category $category)
    {
        //権限チェック
        $this->authorize('delete', $category);

        $category->delete();

        //削除
        Cache::tags(['user:' . auth()->id(), 'categories'])->flush();

        return redirect()->route('categories.index');
    }
}
