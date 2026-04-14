<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use App\Models\Todo;
use App\Models\Comment;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        Route::bind('todo', function ($value) {
            return auth()->user()->todos()->findOrFail($value);
        });
        Route::bind('category', function ($value) {
            return auth()->user()->categories()->findOrFail($value);
        });
        Route::bind('comment', function ($value) {
            return Comment::where('user_id', auth()->id())->findOrFail($value);
        });
    }
}
