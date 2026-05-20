<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use App\Models\Todo;
use App\Models\Comment;
use App\Models\SavedSearch;
use App\Models\Tag;
use App\Models\Category;
use App\Models\User;
use App\Observers\UserObserver;
use App\Observers\TodoObserver;
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
        // 開発環境でのみTelescopeを有効化（本番ビルドエラー回避）
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Scout設定：Meilisearchが使えない環境ではデータベース検索を使用
        if (!config('scout.driver') || config('scout.driver') === 'meilisearch') {
            // Meilisearchサーバーが起動していない場合はDatabase検索に切り替え
            config(['scout.driver' => 'database']);
        }

        //Policy 登録
        Gate::policy(Todo::class, TodoPolicy::class);
        Gate::policy(SavedSearch::class, SavedSearchPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);

        Model::preventLazyLoading(! app()->isProduction());
        Route::bind('todo', function ($value) {
            $todo = Todo::findOrFail($value);

            // コメント作成時は他人のTodoもアクセス可能（Policyで制御）
            $currentRoute = request()->route()->getName();
            if ($currentRoute === 'comments.store') {
                return $todo;
            }

            //個人Todo＝自分の実
            if (!$todo->team_id) {
                if ($todo->user_id !== auth()->id()) {
                    abort(404);
                }
                return $todo;
            }

            //チームTodo＝チームメンバーならOK
            if (auth()->user()->teams()->where('teams.id', $todo->team_id)->exists()) {
                return $todo;
            }
            abort(404);
        });
        Route::bind('category', function ($value) {
            return Category::findOrFail($value);
        });
        Route::bind('comment', function ($value) {
            return Comment::findOrFail($value);
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        //レート制限の定義
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->email . $request->ip());
        });

        //より厳しい制限（認証系）
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(3)->by($request->email . $request->ip());
        });

        // 一般的なWebルート用（認証済みユーザー）
        RateLimiter::for('web', function (Request $request) {
            return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
        });

        // Todo CRUD操作用
        RateLimiter::for('todos', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        User::observe(UserObserver::class);

        Todo::observe(TodoObserver::class);
    }
}
