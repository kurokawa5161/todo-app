<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>ToDo一覧</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 min-h-screen p-8">

    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">

        <h1 class="text-3xl font-bold text-blue-600 mb-6">ToDo一覧</h1>

        <nav class="mb-4 flex items-center justify-between">
            <a href="{{ route('category.index') }}" class="text-blue-600 hover:underline">
                → カテゴリ管理へ
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="px-3 py-1 text-sm bg-gray-500 text-white rounded hover:bg-gray-600">
                    ログアウト
                </button>
            </form>
        </nav>

        <div class="flex gap-2 mb-4">
            <a href="{{ route('todos.index', array_merge(request()->query(), ['filter' => null])) }}"
                class="px-3 py-1 rounded {{ !$filter ? 'bg-blue-600 text-white' : 'bg-gray-200' }}">
                全て({{ $counts->total }})
            </a>
            <a href="{{ route('todos.index', array_merge(request()->query(), ['filter' => 'active'])) }}"
                class="px-3 py-1 rounded {{ $filter === 'active' ? 'bg-blue-600 text-white' : 'bg-gray-200' }}">
                未完了({{ $counts->active }})
            </a>
            <a href="{{ route('todos.index', array_merge(request()->query(), ['filter' => 'done'])) }}"
                class="px-3 py-1 rounded {{ $filter === 'done' ? 'bg-blue-600 text-white' : 'bg-gray-200' }}">
                完了済({{ $counts->done }})
            </a>
        </div>
        <form action="{{ route('todos.index') }}" method="GET" class="mb-4 flex gap-2">
            <input type="hidden" name="filter" value="{{ $filter }}">

            {{-- 検索欄 --}}
            <input type="text" name="q" value="{{ request('q') }}" placeholder="🔍 タイトルで検索"
                class="flex-1 px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400">

            {{-- 並び替え --}}
            <select name="sort" onchange="this.form.submit()"
                class="w-48 px-3 py-2 border rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option value="">並び替え</option>
                <option value="end_date_asc" {{ $sort == 'end_date_asc' ? 'selected' : '' }}>締切が近い順</option>
                <option value="end_date_desc" {{ $sort == 'end_date_desc' ? 'selected' : '' }}>締切が遠い順</option>
                <option value="created_at_desc" {{ $sort == 'created_at_desc' ? 'selected' : '' }}>作成日 新しい順</option>
                <option value="priority_asc" {{ $sort == 'priority_asc' ? 'selected' : '' }}>優先度 高→低</option>
                <option value="title_asc" {{ $sort == 'title_asc' ? 'selected' : '' }}>タイトル昇順</option>
            </select>
        </form>


        {{-- タスク一覧 --}}
        <ul class="space-y-2 mb-6">
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

                <li class="p-3 border rounded hover:bg-gray-50">
                    {{-- 親タスクの情報（タイトル、バッジ、ボタンなど） --}}
                    <div class="flex items-center gap-2">
                        @if ($item->completed_at)
                            <span>✅</span>
                            <s class="flex-1 text-gray-400">{{ $item->title }}</s>
                        @else
                            <span>⬜</span>
                            <span class="flex-1">{{ $item->title }}</span>
                        @endif
                        @if ($item->image_path)
                            <img src="{{ asset('storage/' . $item->image_path) }}" class="w-20 h-20 object-cover rounded">
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

                        <form action="{{ route('todos.pin', $item->id) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-lg hover:scale-110 transition">
                                {{ $item->is_pinned ? '⭐' : '☆' }}
                            </button>
                        </form>

                        @php
                            $isOverdue = !$item->completed_at && $item->end_date->isPast();
                            $isSoon = !$item->completed_at && !$isOverdue && $item->end_date->lte(now()->addDay());
                        @endphp

                        <span
                            class="text-sm px-2 py-1 rounded
                            {{ $isOverdue ? 'bg-red-100 text-red-700 font-bold' : '' }}
                            {{ $isSoon ? 'bg-orange-100 text-orange-700' : '' }}
                            {{ !$isOverdue && !$isSoon ? 'text-gray-500' : '' }}">
                            @if ($isOverdue)
                                ⚠️
                            @elseif ($isSoon)
                                ⏰
                            @endif
                            締切: {{ $item->end_date->format('Y-m-d') }}
                        </span>

                        <form action="{{ route('todos.edit', $item->id) }}" method="GET" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                                編集
                            </button>
                        </form>

                        <form action="{{ route('todos.toggle', $item->id) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                                {{ $item->completed_at ? '戻す' : '完了' }}
                            </button>
                        </form>

                        <form action="{{ route('todos.destroy', $item->id) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600">
                                削除
                            </button>
                        </form>
                    </div>
                    {{-- サブタスク --}}
                    @if ($item->children->count() > 0)
                        <ul class="ml-8 mt-2 space-y-1">
                            @foreach ($item->children as $child)
                                <li class="flex items-center gap-2 p-2 border rounded bg-gray-50">
                                    @if ($child->completed_at)
                                        ✅ <s class="flex-1 text-gray-400">{{ $child->title }}</s>
                                    @else
                                        ⬜ <span class="flex-1">{{ $child->title }}</span>
                                    @endif
                                    {{-- 完了・削除ボタン --}}
                                    <form action="{{ route('todos.toggle', $child->id) }}" method="POST"
                                        class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="px-2 py-0.5 text-xs bg-blue-500 text-white rounded hover:bg-blue-600">
                                            {{ $child->completed_at ? '戻す' : '完了' }}
                                        </button>
                                    </form>

                                    <form action="{{ route('todos.destroy', $child->id) }}" method="POST"
                                        class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="px-2 py-0.5 text-xs bg-red-500 text-white rounded hover:bg-red-600">
                                            削除
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    {{-- サブタスク追加フォーム --}}
                    <form action="{{ route('todos.store') }}" method="POST" class="ml-8 mt-2 flex gap-2">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $item->id }}">
                        <input type="text" name="title" placeholder="サブタスク追加"
                            class="flex-1 px-2 py-1 text-sm border rounded">
                        <button type="submit"
                            class="px-3 py-1 text-sm bg-gray-600 text-white rounded hover:bg-gray-700">
                            追加
                        </button>
                    </form>
                </li>
            @endforeach
        </ul>
        {{ $items->links() }}

        {{-- エラー表示 --}}
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-300 rounded">
                <ul class="list-disc list-inside text-red-600 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- 追加フォーム --}}
        <form action="{{ route('todos.store') }}" method="post" class="space-y-3 border-t pt-4"
            enctype="multipart/form-data">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">カテゴリ</label>
                <select name="category_id" class="w-full px-3 py-2 border rounded">
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
                <label class="block text-sm font-medium mb-1">優先度</label>
                <select name="priority" class="w-full px-3 py-2 border rounded">
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
                <label class="block text-sm font-medium mb-1">タイトル</label>
                <input type="text" name="title" value="{{ old('title') }}"
                    class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">内容</label>
                <input type="text" name="content" value="{{ old('content') }}"
                    class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div class="flex gap-3">
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1">開始日</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}"
                        class="w-full px-3 py-2 border rounded">
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1">終了日</label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}"
                        class="w-full px-3 py-2 border rounded">
                </div>
            </div>
            <input type="file" name="image" accept="image/*">
            <button type="submit" class="w-full py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-medium">
                追加
            </button>
        </form>
    </div>
</body>

</html>
