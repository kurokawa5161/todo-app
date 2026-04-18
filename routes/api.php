<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TodoController;
use App\Http\Controllers\Api\AuthController;

// 認証不要（ログイン）
Route::post('/login', [AuthController::class, 'login'])->name('login');

// 認証必要（ログアウト）
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout'])->name('logout');

// 認証必要（Todoリソース）
Route::middleware('auth:sanctum')->prefix('todos')->name('todos.')->group(function () {
    //一覧
    Route::get('/', [TodoController::class, 'index'])->name('index');
    //作成
    Route::post('/', [TodoController::class, 'store'])->name('store');
    //詳細
    Route::get('/{todo}', [TodoController::class, 'show'])->name('show');
    //更新
    Route::put('/{todo}', [TodoController::class, 'update'])->name('update');
    //削除
    Route::delete('/{todo}', [TodoController::class, 'destroy'])->name('destroy');
});
