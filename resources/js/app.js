import './bootstrap';
import Alpine from 'alpinejs';
import './realtime';

window.Alpine = Alpine;
Alpine.start();

// Ajax処理：完了/未完了の切り替え
document.addEventListener('DOMContentLoaded', function () {
    // CSRFトークンを取得
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // 完了ボタンのAjax化
    document.addEventListener('click', function (e) {
        // 完了ボタンがクリックされたか
        const toggleButton = e.target.closest('[data-toggle-url]');
        if (toggleButton) {
            e.preventDefault(); // デフォルトのフォーム送信を防ぐ
            handleToggle(toggleButton);
        }

        // ピン留めボタンがクリックされたか
        const pinButton = e.target.closest('[data-pin-url]');
        if (pinButton) {
            e.preventDefault();
            handlePin(pinButton);
        }
    });

    document.addEventListener('submit', function (e) {
        // サブタスク追加ボタン
        const form = e.target.closest('[data-subtask-form]');
        if (form) {
            e.preventDefault();
            //FormDataで送信
            handleSubtaskSubmit(form);
        }
    });

    // 完了/未完了の切り替え処理
    async function handleToggle(button) {
        const url = button.getAttribute('data-toggle-url');
        const todoItem = button.closest('[data-todo-item]');
        console.log(url);
        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!response.ok) {
                console.error('HTTP Status:', response.status);
                console.error('Status Text:', response.statusText);
                const errorText = await response.text();
                console.error('Response:', errorText);
                throw new Error(`HTTP error! status: ${response.status}`);
            }


            const data = await response.json();

            // UIを即座に更新
            if (data.success) {
                const checkbox = todoItem.querySelector('[data-checkbox]');
                const title = todoItem.querySelector('[data-title]');
                const buttonText = button.querySelector('span');

                if (data.completed) {
                    // 完了状態
                    checkbox.textContent = '✅';
                    title.innerHTML = `<s class="text-gray-400">${title.textContent}</s>`;
                    if (buttonText) buttonText.textContent = '戻す';
                } else {
                    // 未完了状態
                    checkbox.textContent = '⬜';
                    const plainText = title.textContent;
                    title.innerHTML = plainText;
                    title.classList.remove('text-gray-400');
                    if (buttonText) buttonText.textContent = '完了';
                }
            }
        } catch (error) {
            console.error('Error:', error);
            alert('エラーが発生しました');
        }
    }

    // ピン留めの切り替え処理
    async function handlePin(button) {
        const url = button.getAttribute('data-pin-url');

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!response.ok) {
                console.error('HTTP Status:', response.status);
                console.error('Status Text:', response.statusText);
                const errorText = await response.text();
                console.error('Response:', errorText);
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            // UIを即座に更新
            if (data.success) {
                button.textContent = data.is_pinned ? '⭐' : '☆';
            }
        } catch (error) {
            console.error('Error:', error);
            alert('エラーが発生しました');
        }
    }

    // サブタスク追加処理
    async function handleSubtaskSubmit(form) {
        //parent_idの取得
        const ParentId = form.querySelector('[name="parent_id"]').value;
        const title = form.querySelector('[name="title"]').value;
        const url = form.getAttribute('action');

        if (!title) {
            alert('タイトルを入力してください');
            return;
        }

        try {
            const formData = new FormData(form);
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                // === 1. 親タスク全体の要素を取得 ===
                // form要素から上に向かって、data-todo-item属性を持つ<li>要素を探す
                // これが親タスク全体を表す要素
                const parentTodo = form.closest('[data-todo-item]');

                // === 2. サブタスクリストを取得（なければ作成） ===
                // 親タスクの中から、data-subtask-list属性を持つ<ul>要素を探す
                // これが既存のサブタスクリスト
                let subtaskList = parentTodo.querySelector('[data-subtask-list]');

                // サブタスクリストがまだ存在しない場合（初めてサブタスクを追加する場合）
                if (!subtaskList) {
                    // 新しい<ul>要素を作成
                    subtaskList = document.createElement('ul');
                    // CSSクラスを設定：ml-8（左余白）、mt-2（上余白）、space-y-1（縦の間隔）
                    subtaskList.className = 'ml-8 mt-2 space-y-1';
                    // data-subtask-list属性を追加（後でJavaScriptから見つけられるように）
                    subtaskList.setAttribute('data-subtask-list', '');
                    // フォームの直前に<ul>を挿入
                    // form.parentNode = <li data-todo-item>、form = サブタスク追加フォーム
                    form.parentNode.insertBefore(subtaskList, form);
                }

                // === 3. 新しいサブタスクのHTMLを作成 ===
                // 新しい<li>要素を作成（1つのサブタスクを表す）
                const newSubtask = document.createElement('li');
                // CSSクラスを設定：flex（横並び）、items-center（縦中央揃え）、gap-2（要素間の間隔）、など
                newSubtask.className = 'flex items-center gap-2 p-2 border rounded bg-gray-50';
                // サブタスクの中身（HTML）を設定
                // ⬜マーク、タイトル、完了ボタン、削除ボタンを含む
                newSubtask.innerHTML = `
                    ⬜ <span class="flex-1">${escapeHtml(data.todo.title)}</span>
                    <form action="/todos/${data.todo.id}/toggle" method="POST" class="inline">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="_method" value="PATCH">
                        <button type="submit" class="px-2 py-0.5 text-xs bg-blue-500 text-white rounded hover:bg-blue-600">
                            完了
                        </button>
                    </form>
                    <form action="/todos/${data.todo.id}" method="POST" class="inline">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="px-2 py-0.5 text-xs bg-red-500 text-white rounded hover:bg-red-600">
                            削除
                        </button>
                    </form>
                `;

                // === 4. リストに追加 ===
                // 作成した<li>を<ul data-subtask-list>の最後に追加
                subtaskList.appendChild(newSubtask);

                // === 5. フォームをクリア ===
                // タイトル入力欄を空にする（次の入力に備える）
                form.querySelector('[name="title"]').value = '';

            }
        } catch (error) {
            console.error('Error:', error);
            alert('エラーが発生しました');
        }
    }

    // HTMLエスケープ関数
    // XSS攻撃を防ぐため、特殊文字をHTMLエンティティに変換する
    // 例: "<script>" → "&lt;script&gt;" に変換
    function escapeHtml(text) {
        // 変換マップ：特殊文字 → HTMLエンティティ
        const map = {
            '&': '&amp;',   // & → &amp;（アンパサンド）
            '<': '&lt;',    // < → &lt;（小なり）
            '>': '&gt;',    // > → &gt;（大なり）
            '"': '&quot;',  // " → &quot;（ダブルクォート）
            "'": '&#039;'   // ' → &#039;（シングルクォート）
        };
        // 正規表現で特殊文字を見つけて、mapの値に置き換える
        return text.replace(/[&<>"']/g, m => map[m]);
    }

});
