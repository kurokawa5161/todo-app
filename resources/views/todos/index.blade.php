<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            📝 Todo一覧
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6">

                {{-- 保存済み検索条件 --}}
                @if ($savedSearches->count() > 0)
                    <div
                        class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/30 rounded border border-blue-200 dark:border-blue-800">
                        <h3 class="text-sm font-bold text-blue-700 dark:text-blue-300 mb-2">📌 保存済み検索</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($savedSearches as $savedSearch)
                                @can('view', $savedSearch)
                                    <div
                                        class="flex items-center gap-1 bg-white dark:bg-gray-700 px-3 py-1 rounded border border-blue-300 dark:border-blue-700">
                                        <a href="{{ route('saved-searches.apply', $savedSearch) }}"
                                            class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                                            {{ $savedSearch->name }}
                                        </a>
                                        @can('delete', $savedSearch)
                                            <form action="{{ route('saved-searches.destroy', $savedSearch) }}" method="POST"
                                                class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 text-xs ml-1"
                                                    onclick="return confirm('削除しますか？')">
                                                    ✕
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                @endcan
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex gap-2 mb-4">
                    <a href="{{ route('todos.index', array_merge(request()->query(), ['filter' => null])) }}"
                        class="px-3 py-1 rounded transition {{ !$filter ? 'bg-blue-600 dark:bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100' }}">
                        全て({{ $counts->total }})
                    </a>
                    <a href="{{ route('todos.index', array_merge(request()->query(), ['filter' => 'active'])) }}"
                        class="px-3 py-1 rounded transition {{ $filter === 'active' ? 'bg-blue-600 dark:bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100' }}">
                        未完了({{ $counts->active }})
                    </a>
                    <a href="{{ route('todos.index', array_merge(request()->query(), ['filter' => 'done'])) }}"
                        class="px-3 py-1 rounded transition {{ $filter === 'done' ? 'bg-blue-600 dark:bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100' }}">
                        完了済({{ $counts->done }})
                    </a>
                </div>
                <form action="{{ route('todos.index') }}" method="GET" class="mb-4 space-y-3">
                    <input type="hidden" name="filter" value="{{ $filter }}">

                    {{-- 検索欄（タイトル・内容） --}}
                    <div class="flex gap-2 relative">
                        <div class="flex-1 relative">
                            <input type="text" id="search-input" name="q" value="{{ request('q') }}"
                                placeholder="🔍 タイトルや内容で検索" autocomplete="off"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-blue-500 focus:border-blue-500">

                            {{-- サジェストドロップダウン --}}
                            <div id="suggestions"
                                class="hidden absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded shadow-lg max-h-60 overflow-y-auto">
                            </div>
                        </div>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded transition">
                            検索
                        </button>
                        {{-- ローディング表示 --}}
                        <div id="search-loading" class="hidden">
                            <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span class="text-sm">検索中...</span>
                            </div>
                        </div>
                    </div>

                    {{-- 検索履歴 --}}
                    @if (request('q') === null && $recentSearches->count() > 0)
                        <div class="flex flex-wrap gap-2 items-center text-sm">
                            <span class="text-gray-600 dark:text-gray-400">🕒 最近の検索:</span>
                            @foreach ($recentSearches as $search)
                                <a href="{{ route('todos.index', ['q' => $search->keyword, 'filter' => $filter]) }}"
                                    class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                    {{ $search->keyword }}
                                    <span
                                        class="text-xs text-gray-500 dark:text-gray-500">({{ $search->result_count }}件)</span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- フィルター行 --}}
                    <div class="flex gap-2 items-center flex-wrap">
                        {{-- カテゴリ選択 --}}
                        <select name="category_id"
                            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">カテゴリ: すべて</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>

                        {{-- 優先度選択 --}}
                        <select name="priority"
                            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">優先度: すべて</option>
                            <option value="1" {{ request('priority') == '1' ? 'selected' : '' }}>🔴 高</option>
                            <option value="2" {{ request('priority') == '2' ? 'selected' : '' }}>🟡 中</option>
                            <option value="3" {{ request('priority') == '3' ? 'selected' : '' }}>🟢 低</option>
                        </select>

                        {{-- 並び替え --}}
                        <select name="sort"
                            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">並び替え</option>
                            <option value="end_date_asc" {{ request('sort') == 'end_date_asc' ? 'selected' : '' }}>
                                締切が近い順
                            </option>
                            <option value="end_date_desc" {{ request('sort') == 'end_date_desc' ? 'selected' : '' }}>
                                締切が遠い順
                            </option>
                            <option value="created_at_desc"
                                {{ request('sort') == 'created_at_desc' ? 'selected' : '' }}>
                                作成日 新しい順
                            </option>
                            <option value="priority_asc" {{ request('sort') == 'priority_asc' ? 'selected' : '' }}>
                                優先度 高→低
                            </option>
                            <option value="title_asc" {{ request('sort') == 'title_asc' ? 'selected' : '' }}>
                                タイトル昇順
                            </option>
                        </select>

                        {{-- 期間指定 --}}
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">期間:</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}"
                                class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                            <span class="text-gray-700 dark:text-gray-300">〜</span>
                            <input type="date" name="date_to" value="{{ request('date_to') }}"
                                class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        {{-- 表示件数選択 --}}
                        <select name="per_page" onchange="this.form.submit()"
                            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                            <option value="5" {{ request('per_page') == 5 ? 'selected' : '' }}>5件</option>
                            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10件</option>
                            <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20件</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50件</option>
                        </select>
                    </div>
                </form>

                {{-- 検索条件を保存 --}}
                @if (request()->hasAny(['q', 'category_id', 'priority', 'date_from', 'date_to', 'filter', 'sort']))
                    <form action="{{ route('saved-searches.store') }}" method="POST"
                        class="mb-4 flex gap-2 items-end">
                        @csrf
                        @foreach (request()->query() as $key => $value)
                            @if ($value !== null && $value !== '')
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach

                        <div class="flex-1">
                            <label
                                class="block text-xs font-medium mb-1 text-gray-700 dark:text-gray-300">検索条件に名前を付けて保存</label>
                            <input type="text" name="name" placeholder="例: 高優先度・未完了"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-blue-500 focus:border-blue-500"
                                required>
                        </div>
                        <button type="submit"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded transition">
                            💾 保存
                        </button>
                    </form>
                @endif

                {{-- タスク一覧 --}}
                <ul id="todos-list" class="space-y-2 mb-6">
                    @if ($items->count() === 0)
                            <li
                                class="p-8 text-center border-2 border-dashed border-gray-300 dark:border-gray-600 rounded bg-gray-50 dark:bg-gray-800">
                                <div class="text-gray-500 dark:text-gray-400">
                                    @if (request('q'))
                                        <p class="text-lg mb-2">🔍 検索結果が見つかりませんでした</p>
                                        <p class="text-sm">キーワード: <strong>{{ request('q') }}</strong></p>
                                        <p class="text-sm mt-2">別のキーワードで検索してみてください</p>
                                    @else
                                        <p class="text-lg mb-2">📝 タスクがありません</p>
                                        <p class="text-sm">新しいタスクを作成してみましょう</p>
                                    @endif
                                </div>
                            </li>
                        @endif

                        @foreach ($items as $item)
                            @php
                                $colorClass = match ($item->category->color ?? 'purple') {
                                    'red' => 'bg-red-100 text-red-700',
                                    'yellow' => 'bg-yellow-100 text-yellow-700',
                                    'green' => 'bg-green-100 text-green-700',
                                    'blue' => 'bg-blue-100 text-blue-700',
                                    'purple' => 'bg-purple-100 text-purple-700',
                                    'pink' => 'bg-pink-100 text-pink-700',
                                    'gray' => 'bg-gray-100 text-gray-700',
                                    default => 'bg-purple-100 text-purple-700',
                                };
                            @endphp

                            <li class="p-3 border border-gray-200 dark:border-gray-700 rounded bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                                data-todo-item>
                                {{-- 親タスクの情報（タイトル、バッジ、ボタンなど） --}}
                                <div class="flex items-center gap-2">
                                    @if ($item->completed_at)
                                        <span data-checkbox>✅</span>
                                        <s class="flex-1 text-gray-400 dark:text-gray-500"
                                            data-title>{{ $item->title }}</s>
                                    @else
                                        <span data-checkbox>⬜</span>
                                        <span class="flex-1 text-gray-900 dark:text-gray-100"
                                            data-title>{{ $item->title }}</span>
                                    @endif
                                    @if ($item->image_path)
                                        <img src="{{ asset('storage/' . $item->image_path) }}"
                                            class="w-20 h-20 object-cover rounded">
                                    @endif

                                    @if ($item->category)
                                        <span class="text-xs px-2 py-1 rounded {{ $colorClass }}">
                                            {{ $item->category->name }}
                                        </span>
                                    @endif

                                    @php
                                        $priorityBadge = match ($item->priority) {
                                            1 => ['🔴 高', 'bg-red-100 text-red-700'],
                                            2 => ['🟡 中', 'bg-yellow-100 text-yellow-700'],
                                            3 => ['🟢 低', 'bg-green-100 text-green-700'],
                                            default => null,
                                        };
                                    @endphp

                                    @if ($priorityBadge)
                                        <span class="text-xs px-2 py-1 rounded {{ $priorityBadge[1] }}">
                                            {{ $priorityBadge[0] }}
                                        </span>
                                    @endif

                                    {{-- タグ表示 --}}
                                    @if ($item->tags->count() > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($item->tags as $tag)
                                                @php
                                                    $tagColorClass = match ($tag->color) {
                                                        'red' => 'bg-red-100 text-red-700',
                                                        'yellow' => 'bg-yellow-100 text-yellow-700',
                                                        'green' => 'bg-green-100 text-green-700',
                                                        'blue' => 'bg-blue-100 text-blue-700',
                                                        'purple' => 'bg-purple-100 text-purple-700',
                                                        'pink' => 'bg-pink-100 text-pink-700',
                                                        'gray' => 'bg-gray-100 text-gray-700',
                                                        default => 'bg-gray-100 text-gray-700',
                                                    };
                                                @endphp
                                                <span class="text-xs px-2 py-0.5 rounded {{ $tagColorClass }}">
                                                    🏷️ {{ $tag->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <button type="button" data-pin-url="{{ route('todos.pin', $item->id) }}"
                                        class="text-lg hover:scale-110 transition">
                                        {{ $item->is_pinned ? '⭐' : '☆' }}
                                    </button>

                                    @php
                                        $isOverdue = !$item->completed_at && $item->end_date->isPast();
                                        $isSoon =
                                            !$item->completed_at &&
                                            !$isOverdue &&
                                            $item->end_date->lte(now()->addDay());
                                    @endphp

                                    <span
                                        class="text-sm px-2 py-1 rounded
                            {{ $isOverdue ? 'bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 font-bold' : '' }}
                            {{ $isSoon ? 'bg-orange-100 dark:bg-orange-900 text-orange-700 dark:text-orange-300' : '' }}
                            {{ !$isOverdue && !$isSoon ? 'text-gray-500 dark:text-gray-400' : '' }}">
                                        @if ($isOverdue)
                                            ⚠️
                                        @elseif ($isSoon)
                                            ⏰
                                        @endif
                                        締切: {{ $item->end_date->format('Y-m-d') }}
                                    </span>

                                    @can('update', $item)
                                        <form action="{{ route('todos.edit', $item->id) }}" method="GET"
                                            class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 transition">
                                                編集
                                            </button>
                                        </form>

                                        <button type="button" data-toggle-url="{{ route('todos.toggle', $item->id) }}"
                                            class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 transition">
                                            <span>{{ $item->completed_at ? '戻す' : '完了' }}</span>
                                        </button>
                                    @endcan

                                    @can('delete', $item)
                                        <form action="{{ route('todos.destroy', $item->id) }}" method="POST"
                                            class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition">
                                                削除
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                                {{-- サブタスク --}}
                                @if ($item->children->count() > 0)
                                    <ul class="ml-8 mt-2 space-y-1" data-subtask-list>
                                        @foreach ($item->children as $child)
                                            <li
                                                class="flex items-center gap-2 p-2 border border-gray-200 dark:border-gray-700 rounded bg-gray-50 dark:bg-gray-700">
                                                @if ($child->completed_at)
                                                    ✅ <s
                                                        class="flex-1 text-gray-400 dark:text-gray-500">{{ $child->title }}</s>
                                                @else
                                                    ⬜ <span
                                                        class="flex-1 text-gray-900 dark:text-gray-100">{{ $child->title }}</span>
                                                @endif
                                                {{-- 完了・削除ボタン --}}
                                                @can('update', $child)
                                                    <form action="{{ route('todos.toggle', $child->id) }}" method="POST"
                                                        class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit"
                                                            class="px-2 py-0.5 text-xs bg-blue-500 text-white rounded hover:bg-blue-600 transition">
                                                            {{ $child->completed_at ? '戻す' : '完了' }}
                                                        </button>
                                                    </form>
                                                @endcan
                                                @can('delete', $child)
                                                    <form action="{{ route('todos.destroy', $child->id) }}"
                                                        method="POST" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="px-2 py-0.5 text-xs bg-red-500 text-white rounded hover:bg-red-600 transition">
                                                            削除
                                                        </button>
                                                    </form>
                                                @endcan
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                {{-- サブタスク追加フォーム --}}
                                <form action="{{ route('todos.store') }}" method="POST"
                                    class="ml-8 mt-2 flex gap-2" data-subtask-form
                                    data-parent-id="{{ $item->id }}">
                                    @csrf
                                    <input type="hidden" name="parent_id" value="{{ $item->id }}">
                                    <input type="text" name="title" placeholder="サブタスク追加"
                                        class="flex-1 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-blue-500 focus:border-blue-500">
                                    <button type="submit"
                                        class="px-3 py-1 text-sm bg-gray-600 text-white rounded hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 transition">
                                        追加
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                    <div class="my-6">
                        {{ $items->links() }}
                    </div>

                    {{-- エラー表示 --}}
                    @if ($errors->any())
                        <div
                            class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-300 dark:border-red-800 rounded">
                            <ul class="list-disc list-inside text-red-600 dark:text-red-400 text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- 追加フォーム --}}
                    <form action="{{ route('todos.store') }}" method="post"
                        class="space-y-3 border-t border-gray-200 dark:border-gray-700 pt-4"
                        enctype="multipart/form-data">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">カテゴリ</label>
                            <select name="category_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">（なし）</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">優先度</label>
                            <select name="priority"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                                <option value="1" {{ old('priority') == 1 ? 'selected' : '' }}>
                                    高
                                </option>
                                <option value="2" {{ old('priority') == 2 ? 'selected' : '' }}>
                                    中
                                </option>
                                <option value="3" {{ old('priority') == 3 ? 'selected' : '' }}>
                                    低
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">タイトル</label>
                            <input type="text" name="title" value="{{ old('title') }}"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">内容</label>
                            <input type="text" name="content" value="{{ old('content') }}"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                        <div class="flex gap-3">
                            <div class="flex-1">
                                <label
                                    class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">開始日</label>
                                <input type="date" name="start_date" value="{{ old('start_date') }}"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div class="flex-1">
                                <label
                                    class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">終了日</label>
                                <input type="date" name="end_date" value="{{ old('end_date') }}"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        <input type="file" name="image" accept="image/*">
                        <div>
                            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">タグ</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($tags as $tag)
                                    @php
                                        $colorClass = match ($tag->color) {
                                            'red' => 'bg-red-100 text-red-700',
                                            'yellow' => 'bg-yellow-100 text-yellow-700',
                                            'green' => 'bg-green-100 text-green-700',
                                            'blue' => 'bg-blue-100 text-blue-700',
                                            'purple' => 'bg-purple-100 text-purple-700',
                                            'pink' => 'bg-pink-100 text-pink-700',
                                            'gray' => 'bg-gray-100 text-gray-700',
                                            default => 'bg-gray-100 text-gray-700',
                                        };
                                    @endphp

                                    <label class="flex items-center gap-1 cursor-pointer">
                                        <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                            class="rounded">
                                        <span class="px-2 py-1 rounded text-xs {{ $colorClass }}">
                                            {{ $tag->name }}
                                        </span>
                                    </label>
                                @endforeach

                                @if ($tags->count() == 0)
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        タグがありません。
                                        <a href="{{ route('tags.index') }}"
                                            class="text-blue-600 dark:text-blue-400 hover:underline">
                                            タグ管理
                                        </a>
                                        から作成してください。
                                    </p>
                                @endif
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded font-medium transition">
                            追加
                        </button>
                    </form>
            </div>
        </div>
    </div>

    <script>
        window.userId = {{ auth()->id() }};
        @isset($team)
            window.teamId = {{ $team->id }};
        @endisset

        //検索キーワードハイライト
        @if (request('q'))
            document.addEventListener('DOMContentLoaded', function() {
                const keyword = @json(request('q'));
                const todosList = document.getElementById('todos-list');

                if (!keyword || !todosList) return;

                // タイトルと内容のテキストをハイライト
                const highlightText = (element) => {
                    const walker = document.createTreeWalker(
                        element,
                        NodeFilter.SHOW_TEXT,
                        null,
                        false
                    );

                    const nodes = [];
                    let node;
                    while (node = walker.nextNode()) {
                        if (node.nodeValue.trim()) nodes.push(node);
                    }

                    nodes.forEach(textNode => {
                        const text = textNode.nodeValue;
                        const regex = new RegExp(`(${keyword})`, 'gi');

                        if (regex.test(text)) {
                            const span = document.createElement('span');
                            span.innerHTML = text.replace(regex,
                                '<mark class="bg-yellow-200 dark:bg-yellow-600 px-1 rounded">$1</mark>'
                            );
                            textNode.replaceWith(span);
                        }
                    });
                };

                // 各タスクアイテムに適用
                todosList.querySelectorAll('[data-todo-item]').forEach(item => {
                    highlightText(item);
                });
            });
        @endif

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const suggestionsDiv = document.getElementById('suggestions');
            let debounceTimer;

            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                const query = this.value.trim();

                if (query.length < 2) {
                    suggestionsDiv.classList.add('hidden');
                    return;
                }

                debounceTimer = setTimeout(() => {
                    fetch(`{{ route('todos.suggest') }}?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.length === 0) {
                                suggestionsDiv.classList.add('hidden');
                                return;
                            }

                            suggestionsDiv.innerHTML = data.map(keyword => `
                        <div class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer text-gray-900 dark:text-gray-100 suggestion-item"
                             data-keyword="${keyword}">
                            ${keyword}
                        </div>
                    `).join('');

                            suggestionsDiv.classList.remove('hidden');

                            // クリックイベント
                            document.querySelectorAll('.suggestion-item').forEach(item => {
                                item.addEventListener('click', function() {
                                    searchInput.value = this.dataset.keyword;
                                    suggestionsDiv.classList.add('hidden');
                                    searchInput.form.submit();
                                });
                            });
                        });
                }, 300);
            });

            // 外側クリックで閉じる
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                    suggestionsDiv.classList.add('hidden');
                }
            });
        });

        // 検索フォーム送信時のローディング表示
        document.querySelector('form[action="{{ route('todos.index') }}"]').addEventListener('submit', function() {
            const submitButton = this.querySelector('button[type="submit"]');
            const loadingDiv = document.getElementById('search-loading');

            if (submitButton && loadingDiv) {
                submitButton.classList.add('hidden');
                loadingDiv.classList.remove('hidden');
            }
        });
    </script>

</x-app-layout>
