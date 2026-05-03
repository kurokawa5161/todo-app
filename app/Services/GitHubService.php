<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Todo;
use App\Models\User;

class GitHubService
{
    /**
     * GitHubイベントを処理
     *
     * @param string $event GitHubイベント名 (issues, pull_request, push等)
     * @param array $payload Webhookペイロード
     * @return array 処理結果
     */
    public function handleEvent(string $event, array $payload): array
    {
        return match ($event) {
            'issues' => $this->handleIssue($payload),
            'pull_request' => $this->handlePullRequest($payload),
            'push' => $this->handlePush($payload),
            default => ['message' => "Unsupported event: {$event}"]
        };
    }

    /**
     * GitHub Issueイベントを処理
     *
     * @param array $payload Issueペイロード
     * @return array 処理結果
     */
    protected function handleIssue(array $payload): array
    {
        $action = $payload['action'] ?? 'unknown';
        $issue =  $payload['issue'] ?? [];

        if ($action === 'opened') {
            return $this->createTodoFromIssue($issue);
        }

        if ($action === 'closed') {
            return $this->completeTodoFromIssue($issue);
        }

        if ($action === 'edited') {
            return $this->updateTodoFromIssue($issue);
        }

        if ($action === 'assigned') {
            return $this->assignTodoFromIssue($issue, $payload);
        }

        return ['message' => "Issue {$action}"];
    }

    /**
     * GitHub IssueからTodoを作成
     *
     * @param array $issue Issueデータ
     * @return array 作成結果
     */
    protected function createTodoFromIssue(array $issue): array
    {
        $user = auth()->user();
        if (!$user) {
            return ['message' => 'User not authenticated'];
        }
        $todo = Todo::create([
            'user_id' => $user->id,
            'title' => '[GitHub] ' . ($issue['title'] ?? 'Untitled'),
            'content' => $issue['body'] ?? '',
            'github_issue_url' => $issue['html_url'] ?? null,
            'priority' => 2,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
        ]);

        return [
            'message' => "Created Todo from GitHub Issue",
            'todo_id' => $todo->id
        ];
    }

    /**
     * GitHub Issue完了時にTodoを完了
     *
     * @param array $issue Issueデータ
     * @return array 完了結果
     */
    protected function completeTodoFromIssue(array $issue): array
    {
        $issueUrl = $issue['html_url'] ?? null;

        if (!$issueUrl) {
            return ['message' => 'No issue URL found'];
        }

        $todo = Todo::where('github_issue_url', $issueUrl)
            ->whereNull('completed_at')
            ->first();

        if ($todo) {
            $todo->update(['completed_at' => now()]);
            return ['message' => "Completed Todo (ID: {$todo->id})"];
        }

        return ['message' => 'No matching Todo found'];
    }

    /**
     * GitHub Issue編集時にTodoを更新
     *
     * @param array $issue Issueデータ
     * @return array 更新結果
     */
    protected function updateTodoFromIssue(array $issue): array
    {
        $issueUrl = $issue['html_url'] ?? null;

        if (!$issueUrl) {
            return ['message' => 'No issue URL found'];
        }

        $todo = Todo::where('github_issue_url', $issueUrl)->first();

        if ($todo) {
            $todo->update([
                'title' => '[GitHub] ' . ($issue['title'] ?? $todo->title),
                'content' => $issue['body'] ?? $todo->content,
            ]);
            return [
                'message' => "Updated Todo (ID: {$todo->id})",
                'todo_id' => $todo->id
            ];
        }

        return ['message' => 'No matching Todo found'];
    }

    /**
     * GitHub Issue担当者割り当て時にTodoの担当者を設定
     *
     * @param array $issue Issueデータ
     * @param array $payload 完全なペイロード
     * @return array 割り当て結果
     */
    protected function assignTodoFromIssue(array $issue, array $payload): array
    {
        $issueUrl = $issue['html_url'] ?? null;

        if (!$issueUrl) {
            return ['message' => 'No issue URL found'];
        }

        $todo = Todo::where('github_issue_url', $issueUrl)->first();

        if (!$todo) {
            return ['message' => 'No matching Todo found'];
        }

        $assignee = $payload['assignee'] ?? null;

        if (!$assignee) {
            return ['message' => 'No assignee information found'];
        }

        $assigneeLogin = $assignee['login'] ?? null;

        if ($assigneeLogin) {
            $assignedUser = User::where('email', $assigneeLogin . '@example.com')->first();

            if ($assignedUser) {
                $todo->update(['assigned_to' => $assignedUser->id]);
                return [
                    'message' => "Assigned Todo (ID: {$todo->id}) to {$assignedUser->name}",
                    'todo_id' => $todo->id,
                    'assigned_to' => $assignedUser->id
                ];
            }
        }

        return [
            'message' => "Todo found but user '{$assigneeLogin}' not found in system",
            'todo_id' => $todo->id
        ];
    }

    /**
     * GitHub Pull Requestイベントを処理
     *
     * @param array $payload Pull Requestペイロード
     * @return array 処理結果
     */
    protected function handlePullRequest(array $payload): array
    {
        $action = $payload['action'] ?? 'Unknown';
        $pr = $payload['pull_request'] ?? [];

        return ['message' => "Pull Request {$action}: " . ($pr['title'] ?? '')];
    }

    /**
     * GitHub Pushイベントを処理
     *
     * @param array $payload Pushペイロード
     * @return array 処理結果
     */
    protected function handlePush(array $payload): array
    {
        $commits = count($payload['commits'] ?? []);
        $ref = $payload['ref'] ?? 'unknown';

        return ['message' => "Push: {$commits} commits to {$ref}"];
    }
}
