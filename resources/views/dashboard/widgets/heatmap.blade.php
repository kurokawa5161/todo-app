{{-- ヒートマップウィジェット --}}
<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">🔥 アクティビティヒートマップ（過去30日間）</h3>

    <div class="overflow-x-auto">
        <div class="inline-flex flex-col gap-1">
            {{-- 曜日ラベル --}}
            <div class="flex gap-1">
                <div class="w-8"></div>
                @foreach ($heatmapData['months'] as $month)
                    <div class="text-xs text-gray-600 dark:text-gray-400 px-1">{{ $month }}</div>
                @endforeach
            </div>

            {{-- ヒートマップグリッド --}}
            @foreach (['日', '月', '火', '水', '木', '金', '土'] as $index => $day)
                <div class="flex gap-1 items-center">
                    <div class="w-8 text-xs text-gray-600 dark:text-gray-400">{{ $day }}</div>
                    @foreach ($heatmapData['calendar'][$index] as $cell)
                        @php
                            $bgColor = match(true) {
                                $cell['count'] === 0 => 'bg-gray-100 dark:bg-gray-700',
                                $cell['count'] <= 2 => 'bg-green-200 dark:bg-green-800',
                                $cell['count'] <= 4 => 'bg-green-400 dark:bg-green-600',
                                default => 'bg-green-600 dark:bg-green-400'
                            };
                        @endphp
                        <div class="w-3 h-3 rounded-sm {{ $bgColor }}"
                             title="{{ $cell['date'] }}: {{ $cell['count'] }}件完了"></div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</div>
