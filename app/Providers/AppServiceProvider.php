<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\facades\Route;
use App\Models\Todo;

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
    }
}
