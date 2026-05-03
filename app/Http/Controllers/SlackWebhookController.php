<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SlackService;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\IntegrationLog;

class SlackWebhookController extends Controller
{
    protected $slackService;

    public function __construct(SlackService $slackService)
    {
        $this->slackService = $slackService;
    }

    public function handleCommand(Request $request)
    {
        //署名検証（本番環境のみ）
        if (app()->environment('production')) {
            if (!$this->verifySlackSignature($request)) {
                return response()->json([
                    'error' => 'Invalid signature'
                ], 403);
            }
        }

        //リクエストログ
        $payload = $request->all();
        Log::info('Slack command received', $payload);

        //コマンドをパース
        $text = $request->input('text', '');
        $userName = $request->input('user_name', 'unknown');

        //ユーザーを取得（テスト環境ではログインユーザーを使用）
        if (auth()->check()) {
            $user = auth()->user();
            Log::info('Using authenticated user', ['user_id' => $user->id, 'email' => $user->email]);
        } else {
            $user = User::where('email', $userName)->first();
            Log::info('User lookup result', ['userName' => $userName, 'found' => $user !== null]);
        }

        // ユーザーが見つからない場合のエラーハンドリング
        if (!$user) {
            Log::error('User not found', ['userName' => $userName]);
            return response()->json([
                'response_type' => 'ephemeral',
                'text' => "ユーザーが見つかりません（{$userName}）。ログインしてください。",
            ], 404);
        }

        try {
            //コマンド実行
            $result = $this->slackService->parseCommand($text, $user);

            //ログ保存
            IntegrationLog::create([
                'user_id' => $user->id ?? null,
                'service' => 'slack',
                'action' => 'command',
                'payload' => $payload,
                'response' => $result,
                'status' => 'success'
            ]);

            //Slack形式のレスポンス
            return response()->json([
                'response_type' => 'ephemeral',
                'text' => $result['message']
            ]);
        } catch (\Exception $e) {

            //エラーログ保存
            IntegrationLog::create([
                'user_id' => $user->id ?? null,
                'service' => 'slack',
                'action' => 'command',
                'payload' => $payload,
                'response' => ['error' => $e->getMessage()],
                'status' => 'failed'
            ]);

            return response()->json([
                'response_type' => 'ephemeral',
                'text' => 'エラーが発生しました：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Slack Webhook署名を検証
     *
     * @param Request $request
     * @return bool
     */
    protected function verifySlackSignature(Request $request): bool
    {
        $signature = $request->header('X-Slack-Signature');
        $timestamp = $request->header('X-Slack-Request-Timestamp');

        if (!$signature || !$timestamp) {
            Log::warning('Slack webhook Missing signature headers');
            return false;
        }

        //タイムスタンプチェック（5分以内）
        if (abs(time() - $timestamp) > 300) {
            Log::warning('Slack webhook: Request timestamp too old');
            return false;
        }

        $secret = config('services.slack.webhook_secret');

        if (!$secret) {
            Log::warning('Slack webhook secret not configured');
            return false;
        }

        $baseString = 'v0:' . $timestamp . ':' . $request->getContent();
        $expectedSignature = 'v0=' . hash_hmac('sha256', $baseString, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Slack webhook: Invalid signature');
            return false;
        }

        return true;
    }
}
