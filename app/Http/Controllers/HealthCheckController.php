<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HealthCheckController extends Controller
{
    /**
     * アプリケーションのヘルスチェック
     */
    public function __invoke(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => []
        ];

        // データベース接続確認
        try {
            DB::connection()->getPdo();
            $health['checks']['database'] = 'ok';
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['checks']['database'] = 'failed: ' . $e->getMessage();
        }

        // キャッシュ動作確認
        try {
            $key = 'health_check_' . time();
            Cache::put($key, 'test', 10);
            $value = Cache::get($key);
            Cache::forget($key);

            $health['checks']['cache'] = ($value === 'test') ? 'ok' : 'failed';
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['checks']['cache'] = 'failed: ' . $e->getMessage();
        }

        $statusCode = ($health['status'] === 'healthy') ? 200 : 503;

        return response()->json($health, $statusCode);
    }
}
