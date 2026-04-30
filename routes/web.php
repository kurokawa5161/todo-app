<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\SavedSearchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\GitHubWebhookController;
use App\Http\Controllers\PushSubscriptionController;
use Illuminate\Support\Facades\Auth;
use App\Notifications\TodoDeadlineNotification;
use App\Models\Todo;
use Illuminate\Support\Facades\Log;

// ========================================
// 公開ページ
// ========================================
Route::get('/', function () {
    return view('welcome');
});

// ========================================
// GitHub Webhook（CSRF除外済み）
// ========================================
Route::post('/webhook/github', [GitHubWebhookController::class, 'handleWebhook']);

// ========================================
// Todo管理
// ========================================
Route::prefix('todos')->name('todos.')->middleware('auth')->group(function () {
    Route::get('/', [TodoController::class, 'index'])->name('index');
    Route::post('/', [TodoController::class, 'store'])->name('store');
    Route::get('/{todo}/edit', [TodoController::class, 'edit'])->name('edit');
    Route::put('/{todo}', [TodoController::class, 'update'])->name('update');
    Route::patch('/{todo}/toggle', [TodoController::class, 'toggle'])->name('toggle');
    Route::delete('/{todo}', [TodoController::class, 'destroy'])->name('destroy');
    Route::patch('/{todo}/pin', [TodoController::class, 'togglePin'])->name('pin');
    //サジェスト
    Route::get('/suggest', [TodoController::class, 'suggest'])->name('suggest');
});

// ========================================
// ダッシュボード
// ========================================
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// ========================================
// プロフィール・エクスポート・通知API
// ========================================
Route::middleware('auth')->group(function () {
    //プロフィール
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/reminder', [ProfileController::class, 'updateReminder'])->name('profile.reminder');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    //通知設定
    Route::get('/profile/notifications', [ProfileController::class, 'editNotifications'])->name('profile.notifications.edit');
    Route::patch('/profile/notifications', [ProfileController::class, 'updateNotifications'])->name('profile.notifications.update');

    //エクスポート
    Route::get('/dashboard/export/csv', [DashboardController::class, 'exportCsv'])->name('dashboard.export.csv');
    Route::get('/dashboard/export/pdf/weekly', [DashboardController::class, 'exportWeeklyPdf'])->name('dashboard.export.pdf.weekly');
    Route::get('/dashboard/export/pdf/monthly', [DashboardController::class, 'exportMonthlyPdf'])->name('dashboard.export.pdf.monthly');
    Route::get('/dashboard/export/pdf/yearly', [DashboardController::class, 'exportYearlyPdf'])->name('dashboard.export.pdf.yearly');
    Route::get('/dashboard/export/excel', [DashboardController::class, 'exportExcel'])->name('dashboard.export.excel');
    Route::get('/dashboard/export/json', [DashboardController::class, 'exportJson'])->name('dashboard.export.json');
    Route::get('/dashboard/export/xml', [DashboardController::class, 'exportXml'])->name('dashboard.export.xml');



    //カレンダー
    Route::get('/todos/{todo}/export-calendar', [TodoController::class, 'exportCalendar'])->name('todos.export-calendar');

    //通知API
    Route::get('/notifications/unread-count', function () {
        return response()->json(['count' => auth()->user()->unreadNotifications->count()]);
    });

    //プッシュ通知
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
});

require __DIR__ . '/auth.php';

// ========================================
// カテゴリー管理
// ========================================
Route::prefix('categories')->name('categories.')->middleware('auth')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::post('/', [CategoryController::class, 'store'])->name('store');
    Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
});

// ========================================
// コメント機能
// ========================================
Route::name('comments.')->middleware('auth')->group(function () {
    Route::post('/todos/{todo}/comments', [CommentController::class, 'store'])->name('store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('destroy');
});

// ========================================
// タグ管理
// ========================================
Route::prefix('tags')->name('tags.')->middleware('auth')->group(function () {
    Route::get('/', [TagController::class, 'index'])->name('index');
    Route::post('/', [TagController::class, 'store'])->name('store');
    Route::delete('/{tag}', [TagController::class, 'destroy'])->name('destroy');
});

// ========================================
// 保存済み検索条件
// ========================================
Route::prefix('saved-searches')->name('saved-searches.')->middleware('auth')->group(function () {
    Route::post('/', [SavedSearchController::class, 'store'])->name('store');
    Route::get('/{savedSearch}/apply', [SavedSearchController::class, 'apply'])->name('apply');
    Route::delete('/{savedSearch}', [SavedSearchController::class, 'destroy'])->name('destroy');
});

// ========================================
// チーム機能
// ========================================
Route::prefix('teams')->name('teams.')->middleware('auth')->group(function () {
    Route::get('/', [TeamController::class, 'index'])->name('index');
    Route::post('/', [TeamController::class, 'store'])->name('store');
    Route::get('/{team}', [TeamController::class, 'show'])->name('show');
    Route::put('/{team}', [TeamController::class, 'update'])->name('update');
    Route::delete('/{team}', [TeamController::class, 'destroy'])->name('destroy');

    //招待機能
    Route::post('/{team}/invite', [TeamController::class, 'invite'])->name('invite');
    Route::get('/invitations/{token}', [TeamController::class, 'showInvitation'])->name('invitations.show');
    Route::post('/invitations/{token}/accept', [TeamController::class, 'acceptInvitation'])->name('invitations.accept');

    //メンバー管理
    //チームに対してメンバーを追加
    Route::post('/{team}/members', [TeamController::class, 'addMember'])->name('members.add');
    //チームに対してメンバーを削除する
    Route::delete('/{team}/members/{user}', [TeamController::class, 'removeMember'])->name('members.remove');
    //チームのメンバーの権限を変更する
    Route::patch('/{team}/members/{user}/role', [TeamController::class, 'updateRole'])->name('members.role');
});


Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('throttle:login');

// ========================================
// データベース閲覧
// ========================================
Route::get('/dev/database', function () {
    // 本番環境では認証必須
    if (!auth()->check() && app()->environment('production')) {
        return redirect()->route('login');
    }

    $driver = DB::getDriverName();

    // テーブル一覧取得（ドライバー別）
    if ($driver === 'sqlite') {
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
    } else {
        $tables = DB::select('SHOW TABLES');
    }

    $tableName = request('table');
    $data = null;
    $columns = null;

    if ($tableName) {
        $data = DB::table($tableName)->paginate(50);

        // カラム情報取得（ドライバー別）
        if ($driver === 'sqlite') {
            $columns = DB::select("PRAGMA table_info({$tableName})");
        } else {
            $columns = DB::select("DESCRIBE {$tableName}");
        }
    }

    return view('dev.database', compact('tables', 'tableName', 'data', 'columns', 'driver'));
})->name('dev.database');



// ========================================
// デバッグ用ルート
// ========================================
Route::get('/debug-todos', function () {
    $todos = Todo::select('id', 'title', 'end_date', 'completed_at')
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

Route::get('/debug-sql-todo/{id}', function ($id) {
    Log::info('Debug SQL route called', ['id' => $id]);

    $todo = Todo::where('id', $id)->first();
    Log::info('SQL result', ['todo' => $todo]);

    return response()->json([
        'from_db_table' => $todo,
        'from_model' => Todo::find($id),
    ]);
})->middleware('auth');
