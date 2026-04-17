<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use App\Models\Todo;
use App\Models\Comment;
use App\Models\SavedSearch;
use App\Models\Tag;
use App\Models\Category;
use App\Policies\TodoPolicy;
use App\Policies\SavedSearchPolicy;
use App\Policies\TagPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CommentPolicy;

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
        //Policy 登録
        Gate::policy(Todo::class, TodoPolicy::class);
        Gate::policy(SavedSearch::class, SavedSearchPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);

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
