{{-- 最近のTodoウィジェット --}}
<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">📝 最近のTodo（直近10件）</h3>

    @php
        $recentTodos = App\Models\Todo::where('user_id', auth()->id())
            ->with(['category', 'tags'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    @endphp

    <div class="space-y-2">
        @forelse ($recentTodos as $todo)
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ $todo->title }}
                        </span>
                        @if ($todo->completed_at)
                            <span class="text-xs px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded">
                                完了
                            </span>
                        @endif
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $todo->category?->name ?? '未分類' }} | {{ $todo->end_date?->format('Y/m/d') }}
                    </div>
                </div>
                <a href="{{ route('todos.edit', $todo) }}" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                    詳細
                </a>
            </div>
        @empty
            <p class="text-gray-500 dark:text-gray-400 text-center py-4">Todoがありません</p>
        @endforelse
    </div>
</div>
