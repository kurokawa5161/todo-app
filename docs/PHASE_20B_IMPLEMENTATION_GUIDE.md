# フェーズ20B: 高度な検索機能 - 実装ガイド

## 概要
Meilisearchの高度な機能を活用して、検索体験を向上させます。

## 実装内容

### 1. ファセット検索（カテゴリ、タグ、期限での絞り込み）
### 2. 検索履歴の保存
### 3. サジェスト機能（オートコンプリート）
### 4. 検索結果のソート（関連度、日付、優先度）

---

## 1. ファセット検索の実装

### 現状
現在の絞り込み機能は、データベースクエリで実装されています。

### 目標
Meilisearchのファセット機能を使って、検索結果内での絞り込みを高速化します。

### 実装手順

#### ステップ1: Meilisearchの設定更新

`config/scout.php`の`filterableAttributes`を確認：

```php
'index-settings' => [
    'todos' => [
        'filterableAttributes' => [
            'category_id',
            'priority',
            'completed_at',
            'user_id',
            'end_date',
            'created_at'
        ],
        'sortableAttributes' => [
            'end_date',
            'created_at',
            'priority',
            'title'
        ],
    ],
],
```

#### ステップ2: TodoControllerの検索ロジック拡張

`app/Http/Controllers/TodoController.php`の`index()`メソッドを修正：

```php
public function index(Request $request)
{
    $user = auth()->user();
    
    // 検索キーワードがある場合はScout検索
    if ($request->q) {
        $searchQuery = Todo::search($request->q);
        
        // フィルター適用
        if ($request->category_id) {
            $searchQuery->where('category_id', $request->category_id);
        }
        
        if ($request->priority) {
            $searchQuery->where('priority', $request->priority);
        }
        
        // ユーザーフィルター
        $searchQuery->where('user_id', $user->id);
        
        // ソート
        if ($request->sort === 'end_date_asc') {
            $searchQuery->orderBy('end_date', 'asc');
        } elseif ($request->sort === 'priority_asc') {
            $searchQuery->orderBy('priority', 'asc');
        }
        
        $todos = $searchQuery->paginate($request->input('per_page', 10));
    } else {
        // 通常のクエリ（既存のコード）
        $query = $user->todos()->whereNull('parent_id')->with(['category', 'children', 'tags']);
        
        $query->completedFilter($request->filter)
            ->category($request->category_id)
            ->priority($request->priority)
            ->dateRange($request->date_from, $request->date_to);
        
        // 並び替え
        $query->orderBy('is_pinned', 'desc');
        // ... 既存のソートロジック ...
        
        $todos = $query->paginate($request->input('per_page', 10));
    }
    
    // ... カテゴリ、タグなどの取得 ...
    
    return view('todos.index', compact('todos', ...));
}
```

#### ステップ3: filterableAttributesの設定を反映

**方法A: Artisanコマンドで設定を反映**

Laravel Scout 11では、設定を自動反映するコマンドがありません。手動で設定する必要があります。

**方法B: カスタムコマンド作成**

`php artisan make:command SyncMeilisearchSettings`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use MeiliSearch\Client;

class SyncMeilisearchSettings extends Command
{
    protected $signature = 'scout:sync-index-settings';
    protected $description = 'Sync Meilisearch index settings from config';

    public function handle()
    {
        $client = new Client(config('scout.meilisearch.host'), config('scout.meilisearch.key'));
        $settings = config('scout.meilisearch.index-settings');

        foreach ($settings as $indexName => $indexSettings) {
            $index = $client->index($indexName);
            
            if (isset($indexSettings['filterableAttributes'])) {
                $index->updateFilterableAttributes($indexSettings['filterableAttributes']);
                $this->info("Updated filterable attributes for {$indexName}");
            }
            
            if (isset($indexSettings['sortableAttributes'])) {
                $index->updateSortableAttributes($indexSettings['sortableAttributes']);
                $this->info("Updated sortable attributes for {$indexName}");
            }
            
            if (isset($indexSettings['searchableAttributes'])) {
                $index->updateSearchableAttributes($indexSettings['searchableAttributes']);
                $this->info("Updated searchable attributes for {$indexName}");
            }
        }

        $this->info('Index settings synced successfully!');
    }
}
```

実行：
```bash
php artisan scout:sync-index-settings
```

---

## 2. 検索履歴の保存

### データベース設計

マイグレーション作成：
```bash
php artisan make:migration create_search_histories_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('search_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('keyword');
            $table->integer('result_count')->default(0);
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('search_histories');
    }
};
```

### モデル作成

```bash
php artisan make:model SearchHistory
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchHistory extends Model
{
    protected $fillable = ['user_id', 'keyword', 'result_count'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### TodoControllerで検索履歴を保存

```php
use App\Models\SearchHistory;

public function index(Request $request)
{
    // ... 検索処理 ...
    
    if ($request->q) {
        // 検索履歴を保存
        SearchHistory::create([
            'user_id' => auth()->id(),
            'keyword' => $request->q,
            'result_count' => $todos->total(),
        ]);
    }
    
    // ... ビュー表示 ...
}
```

### 検索履歴の表示

最近の検索履歴を取得：
```php
$recentSearches = SearchHistory::where('user_id', auth()->id())
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();
```

---

## 3. サジェスト機能（オートコンプリート）

### API作成

```bash
php artisan make:controller Api/SearchSuggestController
```

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Todo;
use Illuminate\Http\Request;

class SearchSuggestController extends Controller
{
    public function suggest(Request $request)
    {
        $keyword = $request->input('q', '');
        
        if (strlen($keyword) < 2) {
            return response()->json([]);
        }
        
        // Meilisearchで検索
        $results = Todo::search($keyword)
            ->where('user_id', auth()->id())
            ->take(5)
            ->get()
            ->map(function ($todo) {
                return [
                    'id' => $todo->id,
                    'title' => $todo->title,
                ];
            });
        
        // 検索履歴からもサジェスト
        $historyKeywords = \App\Models\SearchHistory::where('user_id', auth()->id())
            ->where('keyword', 'like', $keyword . '%')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->pluck('keyword');
        
        return response()->json([
            'todos' => $results,
            'history' => $historyKeywords,
        ]);
    }
}
```

### ルート追加

`routes/web.php`または`routes/api.php`：
```php
Route::get('/api/search/suggest', [SearchSuggestController::class, 'suggest'])
    ->middleware('auth')
    ->name('search.suggest');
```

### フロントエンド実装（Alpine.js）

`resources/views/todos/index.blade.php`：

```html
<div x-data="searchSuggest()">
    <input 
        type="text" 
        name="q" 
        x-model="query"
        @input.debounce.300ms="fetchSuggestions"
        @focus="showSuggestions = true"
        placeholder="検索..."
    >
    
    <div x-show="showSuggestions && suggestions.length > 0" 
         @click.away="showSuggestions = false"
         class="absolute bg-white shadow-lg rounded mt-1 w-full">
        <template x-for="suggestion in suggestions" :key="suggestion.id">
            <div @click="selectSuggestion(suggestion)" 
                 class="px-4 py-2 hover:bg-gray-100 cursor-pointer"
                 x-text="suggestion.title">
            </div>
        </template>
    </div>
</div>

<script>
function searchSuggest() {
    return {
        query: '',
        suggestions: [],
        showSuggestions: false,
        
        async fetchSuggestions() {
            if (this.query.length < 2) {
                this.suggestions = [];
                return;
            }
            
            const response = await fetch(`/api/search/suggest?q=${encodeURIComponent(this.query)}`);
            const data = await response.json();
            this.suggestions = data.todos;
            this.showSuggestions = true;
        },
        
        selectSuggestion(suggestion) {
            window.location.href = `/todos/${suggestion.id}`;
        }
    }
}
</script>
```

---

## 4. 検索結果のソート

### Meilisearchでのソート

すでに`sortableAttributes`を設定しているので、Scout検索でソートを使用できます：

```php
$todos = Todo::search($keyword)
    ->where('user_id', auth()->id())
    ->orderBy('end_date', 'asc')  // 期限順
    ->paginate(10);
```

### 関連度順（デフォルト）

Meilisearchはデフォルトで関連度順にソートします。ソートを指定しない場合は関連度順になります。

### ソートオプションの追加

ビューでソートオプションを追加：

```html
<select name="sort">
    <option value="">関連度順</option>
    <option value="end_date_asc">期限が近い順</option>
    <option value="end_date_desc">期限が遠い順</option>
    <option value="created_at_desc">作成日が新しい順</option>
    <option value="priority_asc">優先度が高い順</option>
</select>
```

---

## トラブルシューティング

### filterableAttributesが反映されない

カスタムコマンド`scout:sync-index-settings`を実行してください。

### 検索が遅い

- インデックスサイズを確認
- Meilisearchサーバーのリソースを増やす
- キャッシュを活用

---

## 完了条件

✅ フェーズ20B完了の条件：
1. ファセット検索が動作している
2. 検索履歴が保存・表示されている
3. サジェスト機能が動作している
4. 検索結果のソートができる

---

作成日: 2026-04-29
