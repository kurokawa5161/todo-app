<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('todos')->name('todos.')->middleware('auth')->group(function () {
    Route::get('/', [TodoController::class, 'index'])->name('index');
    Route::post('/', [TodoController::class, 'store'])->name('store');
    Route::get('/{todo}/edit', [TodoController::class, 'edit'])->name('edit');
    Route::put('/{todo}', [TodoController::class, 'update'])->name('update');
    Route::patch('/{todo}/toggle', [TodoController::class, 'toggle'])->name('toggle');
    Route::delete('/{todo}', [TodoController::class, 'destroy'])->name('destroy');
    Route::patch('/{todo}/pin', [TodoController::class, 'togglePin'])->name('pin');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

Route::prefix('category')->name('category.')->middleware('auth')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::post('/', [CategoryController::class, 'store'])->name('store');
    Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
});

Route::name('comments.')->middleware('auth')->group(function () {
    Route::post('/todos/{todo}/comments', [CommentController::class, 'store'])->name('store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('destroy');
});
