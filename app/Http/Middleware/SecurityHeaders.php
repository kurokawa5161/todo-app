<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response =  $next($request);

        // Content Security Policy (開発環境対応)
        $viteUrl = app()->environment('local') ? 'http://localhost:5173 http://127.0.0.1:5173' : '';

        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self';" .
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' {$viteUrl} https://cdn.jsdelivr.net;" .
                "style-src 'self' 'unsafe-inline' {$viteUrl} https://fonts.bunny.net;" .
                "img-src 'self' data: https:;" .
                "font-src 'self' data: https://fonts.bunny.net;" .
                "connect-src 'self' ws: wss: {$viteUrl} https://cdn.jsdelivr.net;"
        );

        // X-Content-Type-Options
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // X-Frame-Options
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Referrer-Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions-Policy
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        return $response;
    }
}
