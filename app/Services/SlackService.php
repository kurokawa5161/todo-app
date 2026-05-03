<?php

namespace App\Services;

use App\Models\Todo;
use App\Models\User;
use App\Models\IntegrationLog;
use Illuminate\Support\Facades\Log;

class SlackService
{
    /**
     * Slackコマンドをパース
     */
    public function parseCommand(string $text, ?User $user): array
    {
        $parts = explode(' ', trim($text), 2);
        $command = $parts[0] ?? 'help';
        $args = $parts[1] ?? '';

        return match ($command) {
            'add' => $this->addTodo($args, $user),
            'list' => $this->listTodos($user),
            'done' => $this->completeTodo($args, $user),
            'help' => $this->showHelp(),
            default => ['message' => "不明なコマンド: {$command}\n使い方は /todo help を参照してください。"],
        };
    }

    /**
     * Todo追加
     */
    protected function addTodo(string $title, ?User $user): array
    {
        Log::info('addTodo called', [
            'title' => $title,
            'user_is_null' => $user === null,
            'user_id' => $user?->id ?? 'NULL'
        ]);

        if (!$user) {
            return ['message' => 'ユーザーが見つかりません'];
        }

        if (empty($title)) {
            return ['message' => 'タスク名を入力してください。例: /todo add レポート作成'];
        }

        $data = [
            'user_id' => $user->id,
            'title' => $title,
            'priority' => 2,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d')
        ];

        Log::info('Creating todo with data', $data);

        $todo = Todo::create($data);

        Log::info('Todo created successfully', ['todo_id' => $todo->id]);


        return [
            'message' => "✅ Todoを追加しました！\n「{$todo->title}」 (ID: {$todo->id})",
            'todo_id' => $todo->id
        ];
    }

    /**
     * Todo一覧
     */
    public function listTodos(?User $user): array
    {
        if (!$user) {
            return ['message' => 'ユーザーが見つかりません'];
        }

        $todos = $user->todos()
            ->whereNull('completed_at')
            ->orderBy('priority', 'desc')
            ->limit(10)
            ->get();

        if ($todos->isEmpty()) {
            return ['message' => '未完了のTodoがありません'];
        }

        $list = "未完了Todo一覧（最新10件）\n\n";
        foreach ($todos as $todo) {
            $priority = ['', '高', '中', '低'][$todo->priority] ?? '';
            $list .= "・[{$todo->id}]{$priority}{$todo->title}\n";
        }

        return ['message' => $list];
    }

    /**
     * Todo完了
     */
    public function completeTodo(string $idStr, ?User $user): array
    {
        if (!$user) {
            return ['message' => 'ユーザーが見つかりません'];
        }

        $id = (int)$idStr;
        if ($id <= 0) {
            return ['message' => 'Todo IDを指定してください。例: /todo done 1'];
        }

        $todo = $user->todos()->find($id);
        if (!$todo) {
            return ['message' => "Todo (ID: {$id}) が見つかりません。"];
        }

        if ($todo->completed_at) {
            return ['message' => "このTodoは既に完了しています。"];
        }

        $todo->update(['completed_at' => now()]);

        return [
            'message' => "Todoを完了しました。\n「{$todo->title}」",
            'todo_id' => $todo->id,
        ];
    }


    /**
     * ヘルプ表示
     */
    public function showHelp(): array
    {
        $help = "📚 Slack Todo コマンド\n\n";
        $help .= "/todo add [タスク名] - Todoを追加\n";
        $help .= "/todo list - 未完了Todo一覧を表示\n";
        $help .= "/todo done [ID] - Todoを完了\n";
        $help .= "/todo help - このヘルプを表示\n";

        return ['message' => $help];
    }

    /**
     * Slackにメッセージ送信（ログのみ、実際の送信はスキップ）
     */
    public function sendMessage(string $channel, string $text, ?User $user = null): void
    {
        Log::info('Slack message (not sent)', [
            'channel' => $channel,
            'text' => $text,
        ]);

        //ログ保存
        IntegrationLog::create([
            'user_id' => $user->id ?? null,
            'service' => 'slack',
            'action' => 'notification',
            'payload' => ['channel' => $channel, 'text' => $text],
            'response' => ['status' => 'logged'],
            'status' => 'success'
        ]);
    }
}
