<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GitHubService;
use Illuminate\Support\Facades\Log;

class GitHubWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $event = $request->header('X-GitHub-Event');
        $payload = $request->all();

        Log::info('GitHub webhook received', [
            'event' => $event,
            'action' => $payload['action'] ?? null,
        ]);

        //Issueが作成された場合
        if ($event === 'issues' && $payload['action'] === 'opened') {
            $githubService = new GitHubService();
            $githubService->createTodoFromIssue($payload['issue']);

            return response()->json([
                'message' => 'Todo created from issue'
            ]);
        }

        return response()->json([
            'message' => 'Webhook received'
        ]);
    }
}
