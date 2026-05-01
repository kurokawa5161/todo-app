{{-- カテゴリ別サマリーウィジェット --}}
<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">📁 カテゴリ別サマリー</h3>

    <div class="space-y-3">
        @forelse ($categoryStats as $stat)
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex-1">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ $stat['category_name'] ?? '未分類' }}
                    </div>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs text-gray-600 dark:text-gray-400">
                            総数: {{ $stat['total'] }}
                        </span>
                        <span class="text-xs text-green-600 dark:text-green-400">
                            完了: {{ $stat['done'] }}
                        </span>
                        <span class="text-xs text-orange-600 dark:text-orange-400">
                            未完了: {{ $stat['active'] }}
                        </span>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-lg font-bold text-blue-600 dark:text-blue-400">
                        {{ number_format($stat['completed'], 1) }}%
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">完了率</div>
                </div>
            </div>
        @empty
            <p class="text-gray-500 dark:text-gray-400 text-center py-4">データがありません</p>
        @endforelse
    </div>
</div>
