<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /**
         * タグ一覧表示
         */
        $tags = auth()->user()->tags()->with('todos')->orderBy('created_at', 'desc')->get();
        $data = [
            'tags' => $tags
        ];

        return view('tags.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(Request $request)
    {
        //バリデーション
        $request->validate([
            'name' => 'required|string|max:20',
            'color' => 'required|in:red,yellow,green,blue,purple,pink,gray'
        ]);

        $tag = new Tag();
        $tag->name = $request->name;
        $tag->color = $request->color;
        $tag->user_id = auth()->id();
        $tag->save();

        //キャッシュ削除
        Cache::tags(['user:' . auth()->id(), 'tags'])->flush();

        return redirect()->route('tags.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tag $tag)
    {
        //権限チェック
        $this->authorize('delete', $tag);

        $tag->delete();

        //キャッシュ削除
        Cache::tags(['user:' . auth()->id(), 'tags'])->flush();

        return redirect()->route('tags.index');
    }
}
