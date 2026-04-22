<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\SavedSearchController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use App\Notifications\TodoDeadlineNotification;

Route::get('/', function () {
    return view('welcome');
});

//Todo
Route::prefix('todos')->name('todos.')->middleware('auth')->group(function () {
    Route::get('/', [TodoController::class, 'index'])->name('index');
    Route::post('/', [TodoController::class, 'store'])->name('store');
    Route::get('/{todo}/edit', [TodoController::class, 'edit'])->name('edit');
    Route::put('/{todo}', [TodoController::class, 'update'])->name('update');
    Route::patch('/{todo}/toggle', [TodoController::class, 'toggle'])->name('toggle');
    Route::delete('/{todo}', [TodoController::class, 'destroy'])->name('destroy');
    Route::patch('/{todo}/pin', [TodoController::class, 'togglePin'])->name('pin');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/reminder', [ProfileController::class, 'updateReminder'])->name('profile.reminder');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

//カテゴリー
Route::prefix('categories')->name('categories.')->middleware('auth')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::post('/', [CategoryController::class, 'store'])->name('store');
    Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
});

//コメント
Route::name('comments.')->middleware('auth')->group(function () {
    Route::post('/todos/{todo}/comments', [CommentController::class, 'store'])->name('store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('destroy');
});

//タグ
Route::prefix('tags')->name('tags.')->middleware('auth')->group(function () {
    Route::get('/', [TagController::class, 'index'])->name('index');
    Route::post('/', [TagController::class, 'store'])->name('store');
    Route::delete('/{tag}', [TagController::class, 'destroy'])->name('destroy');
});

//検索条件
Route::prefix('saved-searches')->name('saved-searches.')->middleware('auth')->group(function () {
    Route::post('/', [SavedSearchController::class, 'store'])->name('store');
    Route::get('/{savedSearch}/apply', [SavedSearchController::class, 'apply'])->name('apply');
    Route::delete('/{savedSearch}', [SavedSearchController::class, 'destroy'])->name('destroy');
});

//通知

Route::get('/debug-todos', function () {
    $todos = \App\Models\Todo::select('id', 'title', 'end_date', 'completed_at')
        ->orderBy('id', 'desc')
        ->limit(5)
        ->get();

    return response()->json($todos);
})->middleware('auth');

Route::get('/test-notification', function () {
    // 1. ログイン中のユーザーを取得
    $user = Auth::user();
    // 2. そのユーザーのTodoを1つ取得
    $todo = auth()->user()->todos()->first();
    // 3. Todoがあれば通知を送る
    if ($todo) {
        $user->notify(new TodoDeadlineNotification(($todo)));
    }
    // 4. 結果を表示
    return '通知を送信しました、storage/logs/laravel.log を確認してください';
})->middleware('auth');
