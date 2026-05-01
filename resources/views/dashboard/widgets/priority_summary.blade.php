{{-- 優先度別サマリーウィジェット --}}
<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">🔥 優先度別サマリー</h3>

    <div class="space-y-3">
        @forelse ($priorities as $stat)
            @php
                $badgeColor = match($stat['priority']) {
                    1 => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200',
                    2 => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
                    3 => 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200',
                    default => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200'
                };
            @endphp
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-xs px-2 py-1 {{ $badgeColor }} rounded">
                            {{ $stat['priority_name'] }}
                        </span>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            優先度
                        </span>
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
