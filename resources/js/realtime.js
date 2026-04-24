// 個人Todoページ（/todos）
if (window.userId && document.querySelector('#todos-list')) {
    window.Echo.private(`user.${window.userId}`)
        .listen('TodoCreated', (e) => {
            location.reload();
        })
        .listen('TodoUpdated', (e) => {
            location.reload();
        })
        .listen('TodoDeleted', (e) => {
            location.reload();
        });
}

// チームTodoページ（/teams/{id}）
if (window.teamId && document.querySelector('#team-todos-list')) {
    window.Echo.private(`team.${window.teamId}`)
        .listen('TodoCreated', (e) => {
            location.reload();
        })
        .listen('TodoUpdated', (e) => {
            location.reload();
        })
        .listen('TodoDeleted', (e) => {
            location.reload();
        });
}

// グローバル通知リスナー（全ページで動作）
if (window.userId) {
    window.Echo.private(`App.Models.User.${window.userId}`)
        .notification((notification) => {
            if (notification.type === 'App\\Notifications\\TodoCommentNotification') {
                showNotification(notification.message, notification.todo_id);
            }
            updateNotificationBadge();
        });
}

// 通知表示関数
function showNotification(message, todoId) {
    if (confirm(message + '\n\nTodoを確認しますか？')) {
        window.location.href = `/todos/${todoId}/edit`;
    }
}

// 通知バッジ更新関数
function updateNotificationBadge() {
    fetch('/notifications/unread-count')
        .then(res => res.json())
        .then(data => {
            const badge = document.getElementById('notification-badge');
            if (badge) {
                badge.textContent = data.count;
                badge.style.display = data.count > 0 ? 'inline' : 'none';
            }
        });
}

// ページ読み込み時にバッジを更新
if (window.userId) {
    updateNotificationBadge();
}

// ========================================
// プレゼンス機能（実装済みだが動作不安定）
// ========================================

// チーム関連ページでpresenceチャンネルに接続
if (window.teamId && (document.querySelector('[data-team-page]') || document.querySelector('[data-todo-edit]'))) {
    const channel = window.Echo.join(`team-presence.${window.teamId}`)
        .here((users) => {
            if (document.querySelector('[data-team-page]')) {
                updateOnlineMembers(users);
            }
        })
        .joining((user) => {
            if (document.querySelector('[data-team-page]')) {
                addOnlineMember(user);
            }
        })
        .leaving((user) => {
            if (document.querySelector('[data-team-page]')) {
                removeOnlineMember(user);
            }
        })
        .listenForWhisper('typing-comment', (e) => {
            showTypingIndicator(e.user);
        })
        .listenForWhisper('editing-todo', (e) => {
            showEditingIndicator(e.user, e.todoId);
        });

    // Todo編集ページの場合
    if (document.querySelector('[data-todo-edit]')) {
        // 編集開始を通知
        setTimeout(() => {
            const todoId = document.querySelector('[data-todo-id]')?.dataset.todoId;
            if (todoId) {
                channel.whisper('editing-todo', {
                    user: {
                        id: window.userId,
                        name: document.querySelector('[data-user-name]')?.textContent || 'Unknown'
                    },
                    todoId: todoId
                });
            }
        }, 1000);

        // コメント入力検知
        const commentInput = document.querySelector('textarea[name="body"]');
        if (commentInput) {
            let typingTimer;
            commentInput.addEventListener('input', () => {
                clearTimeout(typingTimer);
                channel.whisper('typing-comment', {
                    user: {
                        id: window.userId,
                        name: document.querySelector('[data-user-name]')?.textContent || 'Unknown'
                    }
                });
                typingTimer = setTimeout(() => {}, 3000);
            });
        }
    }
}

// ヘルパー関数
function updateOnlineMembers(users) {
    const container = document.getElementById('online-members');
    if (!container) return;

    container.innerHTML = users.map(user => `
        <div class="flex items-center gap-2 p-2 bg-green-50 dark:bg-green-900/30 rounded" data-user-id="${user.id}">
            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
            <span class="text-sm text-gray-900 dark:text-gray-100">${user.name}</span>
        </div>
    `).join('');
}

function addOnlineMember(user) {
    const container = document.getElementById('online-members');
    if (!container) return;

    const memberEl = document.createElement('div');
    memberEl.className = 'flex items-center gap-2 p-2 bg-green-50 dark:bg-green-900/30 rounded';
    memberEl.dataset.userId = user.id;
    memberEl.innerHTML = `
        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
        <span class="text-sm text-gray-900 dark:text-gray-100">${user.name}</span>
    `;
    container.appendChild(memberEl);
}

function removeOnlineMember(user) {
    const memberEl = document.querySelector(`[data-user-id="${user.id}"]`);
    if (memberEl) {
        memberEl.remove();
    }
}

function showTypingIndicator(user) {
    const indicator = document.getElementById('typing-indicator');
    if (!indicator) return;

    if (user.id !== window.userId) {
        indicator.textContent = `${user.name}が入力中…`;
        indicator.style.display = 'block';
        setTimeout(() => {
            indicator.style.display = 'none';
        }, 3000);
    }
}

function showEditingIndicator(user, todoId) {
    const indicator = document.getElementById('editing-indicator');
    if (!indicator) return;

    const currentTodoId = document.querySelector('[data-todo-id]')?.dataset.todoId;
    if (user.id !== window.userId && todoId == currentTodoId) {
        indicator.textContent = `${user.name}がこのTodoを編集中です`;
        indicator.style.display = 'block';
    }
}
