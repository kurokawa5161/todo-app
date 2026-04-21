<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TodoController;
use App\Http\Controllers\Api\AuthController;

// 認証不要（ログイン）
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// 認証必要（ログアウト）
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout'])->name('api.logout');

// 認証必要（Todoリソース）
Route::middleware('auth:sanctum')->prefix('todos')->name('api.todos.')->group(function () {
    //一覧
    Route::get('/', [TodoController::class, 'index'])->name('index');
    //作成
    Route::post('/', [TodoController::class, 'store'])->name('store');

    //一括削除
    Route::delete('/bulk/delete', [TodoController::class, 'bulkDelete'])->name('bulk.delete');
    //一括更新
    Route::put('/bulk/update', [TodoController::class, 'bulkUpdate'])->name('bulk.update');
    //一括完了/未完了
    Route::put('/bulk/complete', [TodoController::class, 'bulkComplete'])->name('bulk.complete');

    //詳細
    Route::get('/{todo}', [TodoController::class, 'show'])->name('show');
    //更新
    Route::put('/{todo}', [TodoController::class, 'update'])->name('update');
    //削除
    Route::delete('/{todo}', [TodoController::class, 'destroy'])->name('destroy');
});
