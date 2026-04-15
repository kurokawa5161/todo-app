import './bootstrap';

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
});
