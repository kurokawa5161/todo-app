<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Middleware\ThrottleRequests;
use App\Http\Middleware\LogApiRequest;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__.'/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            LogApiRequest::class,
            ThrottleRequests::class . ':api',
        ]);

        // GitHub Webhook用のCSRF除外
        $middleware->validateCsrfTokens(except: [
            '/webhook/github',
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        //毎朝午前９時に期限通知を送信
        $schedule->command('app:send-deadline-notifications')->dailyAt('09:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //

    })->create();
