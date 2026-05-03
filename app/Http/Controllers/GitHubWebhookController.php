<?php

namespace App\Http\Controllers;

use App\Models\IntegrationLog;
use Illuminate\Http\Request;
use App\Services\GitHubService;
use Illuminate\Support\Facades\Log;

class GitHubWebhookController extends Controller
{
    protected $githubService;

    public function __construct(GitHubService $githubService)
    {
        $this->githubService = $githubService;
    }

    public function handleWebhook(Request $request)
    {
        //署名検証（本番環境のみ）
        if (app()->environment('production')) {
            if (!$this->verifyGitHubSignature($request)) {
                return response()->json([
                    'error' => 'Invalid signature'
                ], 403);
            }
        }

        $event = $request->header('X-GitHub-Event');
        $payload = $request->all();

        Log::info('GitHub webhook received', [
            'event' => $event,
            'action' => $payload['action'] ?? null,
        ]);

        try {

            $result = $this->githubService->handleEvent($event, $payload);

            IntegrationLog::create([
                'user_id' => auth()->id(),
                'service' => 'github',
                'action' => $event,
                'payload' => $payload,
                'response' => $result,
                'status' => 'success'
            ]);

            return response()->json(['status' => 'success', 'message' => $result['message'] ?? 'Processed']);
        } catch (\Exception $e) {
            IntegrationLog::create([
                'user_id' => auth()->id(),
                'service' => 'github',
                'action' => $event,
                'payload' => $payload,
                'response' => ['error' => $e->getMessage()],
                'status' => 'failed'
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GitHub Webhook署名を検証
     *
     * @param Request $request
     * @return bool
     */
    public function verifyGitHubSignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (!$signature) {
            Log::warning('GitHub webhook: No signature header found');
            return false;
        }

        $payload = $request->getContent();
        $secret = config('services.github.webhook_secret');
        if (!$secret) {
            Log::warning('GitHub webhook secret not configured');
            return false;
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning("GitHub webhook: invalid signature");
            return false;
        }

        return true;
    }
}
