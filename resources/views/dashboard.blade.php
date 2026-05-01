<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            📊 ダッシュボード
        </h2>
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
            background: transparent !important;
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

        /* ガントチャートのグリッド線 */
        .gantt .grid-row,
        .gantt .grid-header {
            fill: transparent;
        }

        .gantt .grid-row:hover {
            fill: rgba(0, 0, 0, 0.05);
        }

        .dark .gantt .grid-row:hover {
            fill: rgba(255, 255, 255, 0.05);
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
    </style>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- サマリーカード --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- 総Todo数 --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">総Todo数</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $total }}
                        </div>
                    </div>
                </div>

                {{-- 完了数 --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">完了</div>
                        <div class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">{{ $done }}
                        </div>
                    </div>
                </div>

                {{-- 未完了数 --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">未完了</div>
                        <div class="mt-2 text-3xl font-bold text-orange-600 dark:text-orange-400">
                            {{ $active }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- 完了率カード --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- 全体完了率 --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">完了率（全体）</div>
                        <div class="mt-2 text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ number_format($completed_all, 1) }}%</div>
                    </div>
                </div>

                {{-- 今週完了率 --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">完了率（今週）</div>
                        <div class="mt-2 text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ number_format($completed_week, 1) }}%</div>
                    </div>
                </div>

                {{-- 今月完了率 --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">完了率（今月）</div>
                        <div class="mt-2 text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ number_format($completed_month, 1) }}%</div>
                    </div>
                </div>

                {{-- 期限遵守率 --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">期限遵守率</div>
                        <div class="mt-2 text-2xl font-bold text-purple-600 dark:text-purple-400">
                            {{ number_format($deadline_comp_todo_pct, 1) }}%</div>
                    </div>
                </div>
            </div>

            {{-- 完了状況グラフ --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">✅ 完了状況</h3>
                    <div class="h-64">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- 推移グラフ --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- 週次完了数推移 --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">📈 週次完了数推移（過去4週間）
                        </h3>
                        <div class="h-64">
                            <canvas id="weeklyChart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- 月次完了数推移 --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">📅 月次完了数推移（過去6ヶ月）
                        </h3>
                        <div class="h-64">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 年間完了数推移 --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">📊 年間完了数推移（過去12ヶ月）</h3>
                    <div class="h-80">
                        <canvas id="yearlyChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- ヒートマップ（日別活動状況） --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">🔥 日別活動状況（過去30日間）</h3>
                    <div class="overflow-x-auto">
                        <div class="flex gap-2">
                            {{-- 曜日ラベル（左側） --}}
                            <div class="flex flex-col gap-1 pt-5">
                                <div class="h-4 text-xs text-gray-500 dark:text-gray-400 flex items-center">月</div>
                                <div class="h-4 text-xs text-gray-500 dark:text-gray-400 flex items-center">火</div>
                                <div class="h-4 text-xs text-gray-500 dark:text-gray-400 flex items-center">水</div>
                                <div class="h-4 text-xs text-gray-500 dark:text-gray-400 flex items-center">木</div>
                                <div class="h-4 text-xs text-gray-500 dark:text-gray-400 flex items-center">金</div>
                                <div class="h-4 text-xs text-gray-500 dark:text-gray-400 flex items-center">土</div>
                                <div class="h-4 text-xs text-gray-500 dark:text-gray-400 flex items-center">日</div>
                            </div>

                            {{-- ヒートマップ本体 --}}
                            <div>
                                {{-- 月ラベル（上部） --}}
                                <div id="heatmap-month-labels"
                                    class="flex gap-1 mb-1 h-4 text-xs text-gray-500 dark:text-gray-400">
                                    <!-- 月ラベル -->
                                </div>

                                {{-- セルグリッド --}}
                                <div id="heatmap" class="inline-flex gap-1">
                                    <!-- ヒートマップグリッド -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <span>少ない</span>
                        <div class="flex gap-1">
                            <div class="w-3 h-3 bg-gray-200 dark:bg-gray-700 rounded-sm"></div>
                            <div class="w-3 h-3 bg-green-200 dark:bg-green-900 rounded-sm"></div>
                            <div class="w-3 h-3 bg-green-400 dark:bg-green-700 rounded-sm"></div>
                            <div class="w-3 h-3 bg-green-600 dark:bg-green-500 rounded-sm"></div>
                            <div class="w-3 h-3 bg-green-800 dark:bg-green-300 rounded-sm"></div>
                        </div>
                        <span>多い</span>
                    </div>
                </div>
            </div>

            {{-- ガントチャート --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">📅 タスクタイムライン</h3>
                    <div class="overflow-x-auto">
                        <svg id="gantt"></svg>
                    </div>
                </div>
            </div>

            {{-- カテゴリ別集計 --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">📊 カテゴリ別集計</h3>

                    {{-- グラフ --}}
                    <div class="mb-6">
                        <div class="h-64">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>

                    {{-- テーブル --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        カテゴリ名</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        総数</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        完了</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        未完了</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        完了率</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($categoryStats as $category)
                                    <tr>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $category['category_name'] ?? '未分類' }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $category['total'] }}</td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400">
                                            {{ $category['done'] }}</td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-orange-600 dark:text-orange-400">
                                            {{ $category['active'] }}</td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <span
                                                class="font-semibold">{{ number_format($category['completed'], 1) }}%</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5"
                                            class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            データがありません</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- タグ別集計 --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">🏷️ タグ別集計</h3>

                    {{-- グラフ --}}
                    <div class="mb-6">
                        <div class="h-64">
                            <canvas id="tagChart"></canvas>
                        </div>
                    </div>

                    {{-- テーブル --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        タグ名</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        総数</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        完了</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        未完了</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        完了率</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($tags as $tag)
                                    <tr>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $tag['tag_name'] }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $tag['total'] }}</td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400">
                                            {{ $tag['done'] }}</td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-orange-600 dark:text-orange-400">
                                            {{ $tag['active'] }}</td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <span
                                                class="font-semibold">{{ number_format($tag['completed'], 1) }}%</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5"
                                            class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            データがありません</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- 優先度別集計 --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">🎯 優先度別集計</h3>

                    {{-- グラフ --}}
                    <div class="mb-6">
                        <div class="h-64">
                            <canvas id="priorityChart"></canvas>
                        </div>
                    </div>

                    {{-- テーブル --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        優先度</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        総数</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        完了</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        未完了</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        完了率</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($priorities as $priority)
                                    <tr>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $priority['priority_name'] }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $priority['total'] }}</td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400">
                                            {{ $priority['done'] }}</td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-orange-600 dark:text-orange-400">
                                            {{ $priority['active'] }}</td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <span
                                                class="font-semibold">{{ number_format($priority['completed'], 1) }}%</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5"
                                            class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            データがありません</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- エクスポートボタン --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">📥 エクスポート</h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('dashboard.export.csv') }}"
                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            CSV エクスポート
                        </a>

                        <a href="{{ route('dashboard.export.pdf.weekly') }}"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            週次レポート（PDF）
                        </a>

                        <a href="{{ route('dashboard.export.pdf.monthly') }}"
                            class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            月次レポート（PDF）
                        </a>

                        <a href="{{ route('dashboard.export.pdf.yearly') }}"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            年間レポート（PDF）
                        </a>

                        <a href="{{ route('dashboard.export.excel') }}"
                            class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            Excel エクスポート
                        </a>
                        <a href="{{ route('dashboard.export.json') }}"
                            class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded transition">
                            📄 JSON
                        </a>
                        <a href="{{ route('dashboard.export.xml') }}"
                            class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded transition">
                            📄 XML
                        </a>

                    </div>
                </div>
            </div>



        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const isDarkMode = document.documentElement.classList.contains('dark');

                // ヒートマップデータ
                const heatmapData = @json($heatmapData);

                // ヒートマップ描画
                const heatmapContainer = document.getElementById('heatmap');
                const monthLabelsContainer = document.getElementById('heatmap-month-labels');
                const maxCount = Math.max(...heatmapData.map(d => d.count));

                // 週ごとにグループ化（7日単位）
                const weeks = [];
                for (let i = 0; i < heatmapData.length; i += 7) {
                    weeks.push(heatmapData.slice(i, i + 7));
                }

                // 月ラベル生成
                let currentMonth = '';
                weeks.forEach((week, weekIndex) => {
                    const firstDay = week[0];
                    const month = new Date(firstDay.date).getMonth() + 1; // 1-12
                    const monthStr = month + '月';

                    const monthLabel = document.createElement('div');
                    monthLabel.className = 'text-xs';
                    monthLabel.style.width = '20px'; // セル幅(16px) + gap(4px)

                    // 月が変わったら表示
                    if (currentMonth !== monthStr) {
                        monthLabel.textContent = monthStr;
                        currentMonth = monthStr;
                    } else {
                        monthLabel.textContent = '';
                    }

                    monthLabelsContainer.appendChild(monthLabel);
                });

                // セルグリッド生成
                weeks.forEach(week => {
                    const weekColumn = document.createElement('div');
                    weekColumn.className = 'flex flex-col gap-1';

                    week.forEach(day => {
                        const cell = document.createElement('div');
                        // サイズを大きく、枠線追加で見やすく
                        cell.className =
                            'w-4 h-4 rounded border border-gray-600 transition-all hover:ring-2 hover:ring-blue-400 cursor-pointer';

                        // 色の強度を計算（0-4のレベル）
                        let colorLevel = 0;
                        if (day.count > 0) {
                            colorLevel = Math.min(4, Math.ceil((day.count / maxCount) * 4));
                        }

                        // 色を設定（ダークモード固定）
                        const colors = ['bg-gray-700', 'bg-green-900', 'bg-green-700', 'bg-green-500',
                            'bg-green-300'
                        ];
                        cell.className += ' ' + colors[colorLevel];

                        // ツールチップ
                        cell.title = `${day.date}: ${day.count}件完了`;

                        weekColumn.appendChild(cell);
                    });

                    heatmapContainer.appendChild(weekColumn);
                });

                // カテゴリ別円グラフ
                new Chart(document.getElementById('categoryChart'), {
                    type: 'doughnut',
                    data: {
                        labels: @json(array_column($categoryStats, 'category_name')),
                        datasets: [{
                            data: @json(array_column($categoryStats, 'total')),
                            backgroundColor: [
                                '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
                                '#EC4899', '#14B8A6', '#F97316', '#06B6D4', '#84CC16'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: '#f3f4f6',
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    },
                                    padding: 10
                                }
                            }
                        }
                    }
                });

                // 完了/未完了ドーナツグラフ
                new Chart(document.getElementById('statusChart'), {
                    type: 'doughnut',
                    data: {
                        labels: ['完了', '未完了'],
                        datasets: [{
                            data: [{{ $done }}, {{ $active }}],
                            backgroundColor: ['#10B981', '#F59E0B']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: '#f3f4f6',
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    },
                                    padding: 10
                                }
                            }
                        }
                    }
                });

                // 優先度別棒グラフ
                new Chart(document.getElementById('priorityChart'), {
                    type: 'bar',
                    data: {
                        labels: @json(array_column($priorities, 'priority_name')),
                        datasets: [{
                                label: '完了',
                                data: @json(array_column($priorities, 'done')),
                                backgroundColor: '#10B981'
                            },
                            {
                                label: '未完了',
                                data: @json(array_column($priorities, 'active')),
                                backgroundColor: '#F59E0B'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: {
                                    color: '#f3f4f6',
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    },
                                    padding: 10
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: '#f3f4f6',
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    color: '#f3f4f6',
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    }
                                },
                                grid: {
                                    color: isDarkMode ? '#374151' : '#e5e7eb'
                                }
                            }
                        }
                    }
                });

                // タグ別円グラフ
                new Chart(document.getElementById('tagChart'), {
                    type: 'pie',
                    data: {
                        labels: @json(array_column($tags, 'tag_name')),
                        datasets: [{
                            data: @json(array_column($tags, 'total')),
                            backgroundColor: [
                                '#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6',
                                '#EC4899', '#14B8A6', '#F97316', '#06B6D4', '#84CC16'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: '#f3f4f6',
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    },
                                    padding: 10
                                }
                            }
                        }
                    }
                });

                // 週次完了数推移グラフ
                new Chart(document.getElementById('weeklyChart'), {
                    type: 'line',
                    data: {
                        labels: @json(array_column($weeklyData, 'label')),
                        datasets: [{
                            label: '完了数',
                            data: @json(array_column($weeklyData, 'count')),
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: {
                                    color: '#f3f4f6',
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    },
                                    padding: 10
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: '#f3f4f6',
                                    font: {
                                        size: 13
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    color: '#f3f4f6',
                                    font: {
                                        size: 13
                                    }
                                },
                                grid: {
                                    color: '#374151'
                                }
                            }
                        }
                    }
                });

                // 月次完了数推移グラフ
                new Chart(document.getElementById('monthlyChart'), {
                    type: 'line',
                    data: {
                        labels: @json(array_column($monthlyData, 'label')),
                        datasets: [{
                            label: '完了数',
                            data: @json(array_column($monthlyData, 'count')),
                            borderColor: '#3B82F6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: {
                                    color: '#f3f4f6',
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    },
                                    padding: 10
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: '#f3f4f6',
                                    font: {
                                        size: 13
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    color: '#f3f4f6',
                                    font: {
                                        size: 13
                                    }
                                },
                                grid: {
                                    color: '#374151'
                                }
                            }
                        }
                    }
                });

                // 年間完了数推移グラフ
                new Chart(document.getElementById('yearlyChart'), {
                    type: 'line',
                    data: {
                        labels: @json(array_column($yearlyData, 'label')),
                        datasets: [{
                            label: '完了数',
                            data: @json(array_column($yearlyData, 'count')),
                            borderColor: '#8B5CF6',
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: {
                                    color: '#f3f4f6',
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    },
                                    padding: 10
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: '#f3f4f6',
                                    font: {
                                        size: 13
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    color: '#f3f4f6',
                                    font: {
                                        size: 13
                                    }
                                },
                                grid: {
                                    color: '#374151'
                                }
                            }
                        }
                    }
                });
            });

            // ガントチャートデータ
            const gantData = @json($gantData);

            // ガントチャート初期化
            if (gantData.length > 0) {
                try {
                    // カテゴリー色をカスタムCSSとして動的追加
                    const categoryColors = new Map();
                    gantData.forEach(task => {
                        if (task.custom_class && task.category_color) {
                            categoryColors.set(task.custom_class, task.category_color);
                        }
                    });

                    if (categoryColors.size > 0) {
                        const styleElement = document.createElement('style');
                        let cssRules = '';
                        categoryColors.forEach((color, className) => {
                            cssRules += `.${className} .bar { fill: ${color} !important; }\n`;
                            cssRules += `.${className} .bar-progress { fill: ${color} !important; opacity: 0.8; }\n`;
                        });
                        styleElement.textContent = cssRules;
                        document.head.appendChild(styleElement);
                    }

                    // Frappe Ganttの初期化（オプション設定付き）
                    const gantt = new Gantt("#gantt", gantData, {
                        view_mode: 'Week', // デフォルトビュー：Week（他: Day, Month, Year）
                        date_format: 'YYYY-MM-DD',
                        language: 'ja',
                        bar_height: 30,
                        bar_corner_radius: 3,
                        padding: 18,
                        view_modes: ['Day', 'Week', 'Month'],
                        custom_popup_html: function(task) {
                            const startDate = new Date(task._start);
                            const endDate = new Date(task._end);
                            const duration = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));

                            return `
                                <div class="gantt-popup">
                                    <div class="font-bold text-sm mb-1">${task.name}</div>
                                    <div class="text-xs text-gray-600">
                                        <div>開始: ${task.start}</div>
                                        <div>終了: ${task.end}</div>
                                        <div>期間: ${duration}日</div>
                                        <div>進捗: ${task.progress}%</div>
                                    </div>
                                </div>
                            `;
                        }
                    });

                    console.log('Gantt chart initialized successfully with', gantData.length, 'tasks');
                } catch (error) {
                    console.error('Gantt initialization error:', error);
                    document.getElementById('gantt').innerHTML =
                        '<p class="text-red-500 dark:text-red-400 p-4">ガントチャートの初期化に失敗しました: ' +
                        error.message + '</p>';
                }
            } else {
                document.getElementById('gantt').innerHTML =
                    '<p class="text-gray-500 dark:text-gray-400 p-4">表示するタスクがありません。タスクを追加すると、ここにタイムラインが表示されます。</p>';
            }
        </script>
    @endpush
</x-app-layout>
