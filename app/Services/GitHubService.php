<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Todo;

class GitHubService
{
    protected $token;
    protected $enabled;

    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->token = config('services.github.token');
        $this->enabled = !empty($this->token);
    }


    /**
     * IssueからTodo作成
     */
    public function createTodoFromIssue(array $issueData)
    {
        $todo = new Todo();
        $todo->user_id = auth()->id() ?? 1; // Webhook経由の場合はuser_id=1
        $todo->title = $issueData['title'];
        $todo->content = $issueData['body'] ?? '';
        $todo->end_date = now()->addDays(7);
        $todo->priority = $this->getPriorityFromLabels($issueData['labels'] ?? []);
        $todo->github_issue_url = $issueData['html_url'];
        $todo->save();

        Log::info('Todo created from GitHub Issue', [
            'todo_id' => $todo->id,
            'issue_url' => $issueData['html_url']
        ]);

        return $todo;
    }

    /**
     * Issueをクローズ
     */
    public function closeIssue(string $issueUrl)
    {
        if (!$this->enabled) {
            Log::info('GitHub token not set, skipping issue close', [
                'url' => $issueUrl
            ]);
            return false;
        }

        //IssueURLからowner/repo/number を抽出
        preg_match('#github\.com/([^/]+)/([^/]+)/issues/(\d+)#', $issueUrl, $matches);
        if (count($matches) !== 4) {
            return false;
        }

        [$full, $owner, $repo, $number] = $matches;

        $response = Http::withToken($this->token)
            ->patch("https://api.github.com/repos/{$owner}/{$repo}/issues/{$number}", [
                'state' => 'closed'
            ]);

        Log::info('Github issue closed', [
            'url' => $issueUrl,
            'success' => $response->successful()
        ]);

        return $response->successful();
    }

    /**
     * ラベルから優先度を判定
     */
    public function getPriorityFromLabels(array $labels)
    {
        foreach ($labels as $label) {
            $name = is_array($label) ? $label['name'] : $label;
            if (in_array($name, ['high', 'urgent', 'critical'])) return 1;
            if (in_array($name, ['low'])) return 3;
        }
        return 2;
    }
}
