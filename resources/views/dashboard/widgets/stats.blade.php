{{-- 統計サマリーウィジェット --}}
<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">📊 統計サマリー</h3>

    {{-- サマリーカード --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- 総Todo数 --}}
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">総Todo数</div>
            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $total }}</div>
        </div>

        {{-- 完了数 --}}
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">完了</div>
            <div class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">{{ $done }}</div>
        </div>

        {{-- 未完了数 --}}
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">未完了</div>
            <div class="mt-2 text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $active }}</div>
        </div>
    </div>

    {{-- 完了率カード --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        {{-- 全体完了率 --}}
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">完了率（全体）</div>
            <div class="mt-2 text-2xl font-bold text-blue-600 dark:text-blue-400">
                {{ number_format($completed_all, 1) }}%
            </div>
        </div>

        {{-- 今週完了率 --}}
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">完了率（今週）</div>
            <div class="mt-2 text-2xl font-bold text-blue-600 dark:text-blue-400">
                {{ number_format($completed_week, 1) }}%
            </div>
        </div>

        {{-- 今月完了率 --}}
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">完了率（今月）</div>
            <div class="mt-2 text-2xl font-bold text-blue-600 dark:text-blue-400">
                {{ number_format($completed_month, 1) }}%
            </div>
        </div>

        {{-- 期限遵守率 --}}
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">期限遵守率</div>
            <div class="mt-2 text-2xl font-bold text-purple-600 dark:text-purple-400">
                {{ number_format($deadline_comp_todo_pct, 1) }}%
            </div>
        </div>
    </div>
</div>
