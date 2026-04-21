<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiLog;

class LogApiRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response =  $next($request);

        ApiLog::create([
            'user_id' => auth()->id(),
            'method' => $request->method(),
            'endpoint'  => $request->path(),
            'ip_address'  => $request->ip(),
            'status_code'  => $response->getStatusCode(),
        ]);
        return $response;
    }
}
