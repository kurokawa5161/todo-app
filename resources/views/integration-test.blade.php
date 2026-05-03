<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integration Test - Slack/GitHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <div class="max-w-4xl mx-auto p-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                🔗 Integration Test
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Slack/GitHub連携のテストページ
            </p>
            <a href="{{ route('dashboard') }}" class="text-blue-500 hover:underline">
                ← ダッシュボードに戻る
            </a>
        </div>

        <!-- Slack Test Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center mb-4">
                <span class="text-2xl mr-2">💬</span>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Slack Command Test
                </h2>
            </div>

            <form id="slackForm" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        コマンド
                    </label>
                    <select id="slackCommandType" class="w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 dark:bg-gray-700 dark:text-white mb-2">
                        <option value="add">add - Todo追加</option>
                        <option value="list">list - Todo一覧</option>
                        <option value="done">done - Todo完了</option>
                        <option value="help">help - ヘルプ表示</option>
                    </select>
                </div>

                <div id="addParams" class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        タスク名（addコマンド用）
                    </label>
                    <input type="text" id="taskTitle" placeholder="例: レポート作成"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 dark:bg-gray-700 dark:text-white">
                </div>

                <div id="doneParams" class="space-y-2 hidden">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Todo ID（doneコマンド用）
                    </label>
                    <input type="number" id="todoId" placeholder="例: 1"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 dark:bg-gray-700 dark:text-white">
                </div>

                <button type="submit"
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded-md transition">
                    📤 Slackコマンド送信
                </button>
            </form>

            <div class="mt-4">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">レスポンス:</h3>
                <pre id="slackResult" class="bg-gray-100 dark:bg-gray-900 p-4 rounded border border-gray-300 dark:border-gray-600 text-sm overflow-auto max-h-64 text-gray-800 dark:text-gray-200"></pre>
            </div>
        </div>

        <!-- GitHub Test Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center mb-4">
                <span class="text-2xl mr-2">🐙</span>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    GitHub Webhook Test
                </h2>
            </div>

            <form id="githubForm" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        イベントタイプ
                    </label>
                    <select id="githubEventType" class="w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 dark:bg-gray-700 dark:text-white">
                        <option value="opened">opened - Issue作成</option>
                        <option value="edited">edited - Issue編集</option>
                        <option value="closed">closed - Issue閉じる</option>
                        <option value="assigned">assigned - 担当者割り当て</option>
                        <option value="labeled">labeled - ラベル追加</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Issue番号
                    </label>
                    <input type="number" id="issueNumber" value="1"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Issueタイトル
                    </label>
                    <input type="text" id="issueTitle" placeholder="例: バグ修正が必要"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Issue本文
                    </label>
                    <textarea id="issueBody" rows="3" placeholder="例: ログイン時のエラーを修正する必要があります"
                              class="w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 dark:bg-gray-700 dark:text-white"></textarea>
                </div>

                <div id="assigneeParams" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        担当者（assignedイベント用）
                    </label>
                    <input type="text" id="assigneeLogin" placeholder="例: test (test@example.comで検索)"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 dark:bg-gray-700 dark:text-white">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        ※ {入力値}@example.com でユーザーを検索します
                    </p>
                </div>

                <button type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-md transition">
                    📤 GitHub Webhook送信
                </button>
            </form>

            <div class="mt-4">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">レスポンス:</h3>
                <pre id="githubResult" class="bg-gray-100 dark:bg-gray-900 p-4 rounded border border-gray-300 dark:border-gray-600 text-sm overflow-auto max-h-64 text-gray-800 dark:text-gray-200"></pre>
            </div>
        </div>

        <!-- Integration Logs -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    📋 最新の連携ログ
                </h2>
                <button onclick="loadLogs()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                    🔄 更新
                </button>
            </div>
            <div id="logsContainer" class="space-y-2">
                <p class="text-gray-500 dark:text-gray-400">ログを読み込み中...</p>
            </div>
        </div>
    </div>

    <script>
        // Slack Command Type の変更に応じてパラメータ表示を切り替え
        document.getElementById('slackCommandType').addEventListener('change', function() {
            const commandType = this.value;
            document.getElementById('addParams').classList.toggle('hidden', commandType !== 'add');
            document.getElementById('doneParams').classList.toggle('hidden', commandType !== 'done');
        });

        // GitHub Event Type の変更に応じてパラメータ表示を切り替え
        document.getElementById('githubEventType').addEventListener('change', function() {
            const eventType = this.value;
            document.getElementById('assigneeParams').classList.toggle('hidden', eventType !== 'assigned');
        });

        // Slack Form Submit
        document.getElementById('slackForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const commandType = document.getElementById('slackCommandType').value;
            let text = commandType;

            if (commandType === 'add') {
                const title = document.getElementById('taskTitle').value;
                text = `add ${title}`;
            } else if (commandType === 'done') {
                const id = document.getElementById('todoId').value;
                text = `done ${id}`;
            }

            const resultElement = document.getElementById('slackResult');
            resultElement.textContent = '送信中...';

            try {
                const response = await fetch('/slack/commands', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: `text=${encodeURIComponent(text)}&user_id=U123456&user_name={{ auth()->user()->email ?? 'testuser' }}&command=/todo`
                });

                const result = await response.text();
                resultElement.textContent = result;

                // ログを更新
                setTimeout(loadLogs, 500);
            } catch (error) {
                resultElement.textContent = 'エラー: ' + error.message;
            }
        });

        // GitHub Form Submit
        document.getElementById('githubForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const eventType = document.getElementById('githubEventType').value;
            const issueNumber = document.getElementById('issueNumber').value;
            const issueTitle = document.getElementById('issueTitle').value;
            const issueBody = document.getElementById('issueBody').value;

            const payload = {
                action: eventType,
                issue: {
                    number: parseInt(issueNumber),
                    title: issueTitle,
                    body: issueBody,
                    state: eventType === 'closed' ? 'closed' : 'open',
                    html_url: `https://github.com/test/repo/issues/${issueNumber}`
                }
            };

            // assignedイベントの場合、assignee情報を追加
            if (eventType === 'assigned') {
                const assigneeLogin = document.getElementById('assigneeLogin').value;
                if (assigneeLogin) {
                    payload.assignee = {
                        login: assigneeLogin
                    };
                }
            }

            const resultElement = document.getElementById('githubResult');
            resultElement.textContent = '送信中...';

            try {
                const response = await fetch('/github/webhook', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'X-GitHub-Event': 'issues'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.text();
                resultElement.textContent = result;

                // ログを更新
                setTimeout(loadLogs, 500);
            } catch (error) {
                resultElement.textContent = 'エラー: ' + error.message;
            }
        });

        // Load Integration Logs
        async function loadLogs() {
            const container = document.getElementById('logsContainer');
            container.innerHTML = '<p class="text-gray-500 dark:text-gray-400">読み込み中...</p>';

            try {
                const response = await fetch('/integration-logs');
                const logs = await response.json();

                if (logs.length === 0) {
                    container.innerHTML = '<p class="text-gray-500 dark:text-gray-400">ログがありません</p>';
                    return;
                }

                container.innerHTML = logs.map(log => `
                    <div class="border border-gray-200 dark:border-gray-700 rounded p-3 ${log.status === 'success' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20'}">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-semibold ${log.status === 'success' ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'}">
                                ${log.service.toUpperCase()} - ${log.action}
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">${new Date(log.created_at).toLocaleString('ja-JP')}</span>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <strong>ステータス:</strong> ${log.status}
                        </div>
                    </div>
                `).join('');
            } catch (error) {
                container.innerHTML = '<p class="text-red-500">エラー: ' + error.message + '</p>';
            }
        }

        // ページ読み込み時にログを取得
        loadLogs();
    </script>
</body>
</html>
