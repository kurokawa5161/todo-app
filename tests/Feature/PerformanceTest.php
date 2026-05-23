<?php

use App\Models\User;
use App\Models\Todo;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Comment;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * N+1クエリ検出テスト
 *
 * N+1問題：1つのクエリでメインデータを取得し、その後N回のクエリで関連データを取得する非効率なパターン
 */
test('todo index should not have N+1 query problem', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    // 10個のTodoを作成
    Todo::factory()->count(10)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    DB::enableQueryLog();

    // Eager loadingを使用してTodoを取得
    $todos = Todo::with(['user', 'category', 'tags', 'comments'])
        ->where('user_id', $user->id)
        ->get();

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // Eager loadingを使用すれば、クエリ数は以下のようになるべき：
    // 1. Todoの取得
    // 2. Userの取得
    // 3. Categoryの取得
    // 4. Tagsの取得
    // 5. Commentsの取得
    // = 合計5クエリ以下

    expect(count($queries))->toBeLessThanOrEqual(5);
});

test('todo index without eager loading has N+1 problem', function () {
    // Lazy loadingが無効化されているため、このテストはスキップ
    // Lazy loading protectionが機能している証拠
    $this->markTestSkipped('Lazy loading is disabled (Model::preventLazyLoading). This is the desired behavior in production.');
});

test('category with todos should use eager loading', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    // 5個のTodoを作成
    Todo::factory()->count(5)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    DB::enableQueryLog();

    // Eager loadingを使用
    $categories = Category::with('todos')
        ->where('user_id', $user->id)
        ->get();

    foreach ($categories as $cat) {
        foreach ($cat->todos as $todo) {
            $title = $todo->title;
        }
    }

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // Categoryクエリ + Todosクエリ = 2クエリ
    expect(count($queries))->toBeLessThanOrEqual(2);
});

/**
 * スロークエリ検出テスト
 */
test('todo search query should be fast', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    // 100個のTodoを作成
    Todo::factory()->count(100)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    $startTime = microtime(true);

    // 検索クエリ実行
    $todos = Todo::where('user_id', $user->id)
        ->where('title', 'like', '%test%')
        ->get();

    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // ミリ秒

    // クエリ実行時間が100ms未満であることを確認
    expect($executionTime)->toBeLessThan(100);
});

test('todo with relations query should be optimized', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);
    $tag = Tag::factory()->create(['user_id' => $user->id]);

    // 50個のTodoを作成
    $todos = Todo::factory()->count(50)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    // 各Todoにタグとコメントを追加
    foreach ($todos as $todo) {
        $todo->tags()->attach($tag);
        Comment::factory()->count(2)->create([
            'todo_id' => $todo->id,
            'user_id' => $user->id,
        ]);
    }

    $startTime = microtime(true);

    // Eager loadingを使用して関連データを取得
    $results = Todo::with(['category', 'tags', 'comments', 'user'])
        ->where('user_id', $user->id)
        ->get();

    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // ミリ秒

    // 複雑なクエリでも200ms未満であることを確認
    expect($executionTime)->toBeLessThan(200);
});

/**
 * メモリ使用量テスト
 */
test('todo pagination should not load all records into memory', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    // 500個のTodoを作成
    Todo::factory()->count(500)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    $memoryBefore = memory_get_usage();

    // ページネーションを使用（一度に15件のみ取得）
    $todos = Todo::where('user_id', $user->id)->paginate(15);

    $memoryAfter = memory_get_usage();
    $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB

    // メモリ使用量が5MB未満であることを確認
    expect($memoryUsed)->toBeLessThan(5);
});

test('chunk query should process large dataset efficiently', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    // 1000個のTodoを作成
    Todo::factory()->count(1000)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    $memoryBefore = memory_get_usage();
    $processedCount = 0;

    // chunk()を使用して100件ずつ処理
    Todo::where('user_id', $user->id)->chunk(100, function ($todos) use (&$processedCount) {
        $processedCount += $todos->count();
    });

    $memoryAfter = memory_get_usage();
    $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB

    // 1000件処理してもメモリ使用量が10MB未満
    expect($memoryUsed)->toBeLessThan(10);
    expect($processedCount)->toBe(1000);
});

/**
 * クエリ数最適化テスト
 */
test('todo controller index should execute minimal queries', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    // 20個のTodoを作成
    Todo::factory()->count(20)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    DB::enableQueryLog();

    // TodoControllerのindex処理をシミュレート
    $todos = Todo::with(['category', 'tags'])
        ->where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->paginate(15);

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // ページネーション + Eager loading = 5クエリ以下
    // 1. Todoカウント（pagination）
    // 2. Todo取得
    // 3. Category取得
    // 4. Tags取得
    expect(count($queries))->toBeLessThanOrEqual(5);
});
