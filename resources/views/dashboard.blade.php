<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                📊 ダッシュボード
            </h2>
            <button onclick="openWidgetSettings()"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                ⚙️ ウィジェット設定
            </button>
        </div>
    </x-slot>

    <style>
        /* ガントチャート カテゴリー色 */
        @foreach ($categories ?? [] as $category)
            @if ($category->color)
                .gantt-category-{{ $category->id }} .bar {
                    fill: {{ $category->color }} !important;
                }

                .gantt-category-{{ $category->id }} .bar-progress {
                    fill: {{ $category->color }} !important;
                    opacity: 0.8;
                }
            @endif
        @endforeach

        .gantt-default .bar {
            fill: #94a3b8 !important;
        }

        .gantt-default .bar-progress {
            fill: #94a3b8 !important;
            opacity: 0.8;
        }

        /* ガントチャート全体のスタイル */
        #gantt {
            overflow: visible !important;
        }

        .gantt-container {
            background: #f9fafb !important;
        }

        /* ガントチャートのポップアップスタイル */
        .gantt-popup {
            background: white;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            min-width: 200px;
        }

        .dark .gantt-popup {
            background: #1f2937;
            color: #f3f4f6;
        }

        /* ガントチャートのグリッド線（背景を白系で統一） */
        .gantt .grid-row,
        .gantt .grid-header {
            fill: #f9fafb !important;
        }

        .gantt .grid-row:hover {
            fill: #f3f4f6 !important;
        }

        .dark .gantt-container {
            background: #1f2937 !important;
        }

        .dark .gantt .grid-row,
        .dark .gantt .grid-header {
            fill: #1f2937 !important;
        }

        .dark .gantt .grid-row:hover {
            fill: #374151 !important;
        }

        /* ガントチャートのテキスト */
        .gantt .lower-text,
        .gantt .upper-text {
            fill: #374151;
        }

        .dark .gantt .lower-text,
        .dark .gantt .upper-text {
            fill: #f3f4f6;
        }

        /* グリッド線を薄く */
        .gantt .grid-tick {
            stroke: #e5e7eb !important;
        }

        .dark .gantt .grid-tick {
            stroke: #4b5563 !important;
        }
    </style>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- ウィジェット一覧 --}}
            @forelse ($widgets as $widget)
                @if ($widget->is_visible)
                    @include("dashboard.widgets.{$widget->widget_type}", [
                        'total' => $total,
                        'done' => $done,
                        'active' => $active,
                        'completed_all' => $completed_all,
                        'completed_week' => $completed_week,
                        'completed_month' => $completed_month,
                        'deadline_comp_todo_pct' => $deadline_comp_todo_pct,
                        'weeklyData' => $weeklyData,
                        'monthlyData' => $monthlyData,
                        'yearlyData' => $yearlyData,
                        'heatmapData' => $heatmapData,
                        'gantData' => $gantData,
                        'categoryStats' => $categoryStats,
                        'tags' => $tags,
                        'priorities' => $priorities,
                        'categories' => $categories,
                    ])
                @endif
            @empty
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                    <p class="text-gray-500 dark:text-gray-400">ウィジェットがありません</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">
                        設定からウィジェットを追加してください
                    </p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ウィジェット設定モーダル --}}
    <div id="widgetSettingsModal"
        class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">ウィジェット設定</h3>
                <button onclick="closeWidgetSettings()"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400">✕</button>
            </div>

            <div id="widgetList" class="space-y-2 mb-4">
                {{-- JavaScriptで動的生成 --}}
            </div>

            <div class="flex gap-2">
                <button onclick="resetWidgets()"
                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    リセット
                </button>
                <button onclick="applyWidgetSettings()"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    更新
                </button>
                <button onclick="closeWidgetSettings()"
                    class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                    閉じる
                </button>
            </div>
        </div>
    </div>

    @stack('scripts')

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        (function() {
            let widgetsList = @json($widgets);

            // モーダル開閉
            window.openWidgetSettings = function() {
                loadWidgetList();
                document.getElementById('widgetSettingsModal').classList.remove('hidden');
            }

            window.closeWidgetSettings = function() {
                document.getElementById('widgetSettingsModal').classList.add('hidden');
            }

            window.applyWidgetSettings = function() {
                location.reload();
            }

            // ウィジェットリストを読み込み
            function loadWidgetList() {
                const listEl = document.getElementById('widgetList');
                listEl.innerHTML = widgetsList.map(widget => `
        <div class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-700 rounded cursor-move" data-id="${widget.id}">
            <span class="text-gray-500">☰</span>
            <label class="flex-1 flex items-center gap-2">
                <input type="checkbox"
                       ${widget.is_visible ? 'checked' : ''}
                       onchange="toggleWidget(${widget.id}, this.checked)"
                       class="rounded">
                <span class="text-sm text-gray-900 dark:text-gray-100">${getWidgetLabel(widget.widget_type)}</span>
            </label>
        </div>
    `).join('');

                // Sortable.js でドラッグ&ドロップを有効化
                new Sortable(listEl, {
                    animation: 150,
                    onEnd: function(evt) {
                        reorderWidgets();
                    }
                });
            }

            // ウィジェット名のラベル取得
            function getWidgetLabel(type) {
                const labels = {
                    'stats': '統計サマリー',
                    'chart_weekly': '週次完了数',
                    'chart_monthly': '月次完了数',
                    'chart_yearly': '年間完了数',
                    'heatmap': 'ヒートマップ',
                    'gantt': 'タスクタイムライン',
                    'recent_todos': '最近のTodo',
                    'category_summary': 'カテゴリ別サマリー',
                    'tag_summary': 'タグ別サマリー',
                    'priority_summary': '優先度別サマリー'
                };
                return labels[type] || type;
            }

            // 表示/非表示切り替え
            window.toggleWidget = function(id, isVisible) {
                fetch(`/dashboard/widgets/${id}/toggle`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            is_visible: isVisible
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        // 成功時もリロードしない（「更新」ボタンで一括反映）
                    });
            }

            // 並び替え
            function reorderWidgets() {
                const items = document.querySelectorAll('#widgetList > div');
                const newOrder = Array.from(items).map((item, index) => ({
                    id: parseInt(item.dataset.id),
                    position: index
                }));

                fetch('/dashboard/widgets/reorder', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            widgets: newOrder
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            widgetsList = newOrder.map(item => widgetsList.find(w => w.id === item.id));
                        }
                    });
            }

            // リセット
            window.resetWidgets = function() {
                if (confirm('ウィジェット設定をリセットしますか？')) {
                    fetch('/dashboard/widgets/reset', {
                            method: 'PATCH',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            }
                        });
                }
            }
        })();
    </script>
</x-app-layout>
