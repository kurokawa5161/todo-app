<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            📝 Todo編集
        </h2>
    </x-slot>

    <div class="py-12" data-todo-edit data-todo-id="{{ $item->id }}" data-user-name="{{ auth()->user()->name }}">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6">

                {{-- 編集インジケーター --}}
                <div id="editing-indicator" class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-300 dark:border-yellow-800 rounded text-yellow-800 dark:text-yellow-200 text-sm" style="display: none;"></div>

                {{-- エラー表示 --}}
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-300 dark:border-red-800 rounded">
                        <ul class="list-disc list-inside text-red-600 dark:text-red-400 text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- 編集フォーム --}}
                <form action="{{ route('todos.update', $item->id) }}" method="POST" class="space-y-3 border-t border-gray-200 dark:border-gray-700 pt-4"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @if($team)
                        <input type="hidden" name="team_id" value="{{ $team->id }}">
                    @endif
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">カテゴリ</label>
                        <select name="category_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="">（なし）</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ old('category_id', $item->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">優先度</label>
                        <select name="priority" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="1" {{ old('priority', $item->priority) == 1 ? 'selected' : '' }}>
                                高
                            </option>
                            <option value="2" {{ old('priority', $item->priority) == 2 ? 'selected' : '' }}>
                                中
                            </option>
                            <option value="3" {{ old('priority', $item->priority) == 3 ? 'selected' : '' }}>
                                低
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">タイトル</label>
                        <input type="text" name="title" value="{{ old('title', $item->title) }}"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">内容</label>
                        <input type="text" name="content" value="{{ old('content', $item->content) }}"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div class="flex gap-3">
                        <div class="flex-1">
                            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">開始日</label>
                            <input type="date" name="start_date"
                                value="{{ old('start_date', $item->start_date?->format('Y-m-d')) }}"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">終了日</label>
                            <input type="date" name="end_date"
                                value="{{ old('end_date', $item->end_date->format('Y-m-d')) }}"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">画像</label>
                        @if ($item->image_path)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $item->image_path) }}" class="max-w-xs rounded">
                            </div>
                        @endif
                        <input type="file" name="image" accept="image/*" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('image')
                            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
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

                                    // このTodoに付いているタグかチェック
                                    $isChecked = $item->tags->contains($tag->id);
                                @endphp

                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                        {{ $isChecked ? 'checked' : '' }} class="rounded">
                                    <span class="px-2 py-1 rounded text-xs {{ $colorClass }}">
                                        {{ $tag->name }}
                                    </span>
                                </label>
                            @endforeach

                            @if ($tags->count() == 0)
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    タグがありません。
                                    <a href="{{ route('tags.index') }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                        タグ管理
                                    </a>
                                    から作成してください。
                                </p>
                            @endif
                        </div>
                    </div>
                    <button type="submit" class="w-full py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded font-medium transition">
                        更新
                    </button>
                </form>

                {{-- コメント --}}
                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-bold mb-3 text-gray-900 dark:text-gray-100">コメント</h2>

                    {{-- コメント一覧 --}}
                    @if ($item->comments->count() > 0)
                        <ul class="space-y-2 mb-4">
                            @foreach ($item->comments as $comment)
                                <li class="p-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded">
                                    <div class="flex items-center justify-between mb-1">
                                        <div class="text-sm">
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $comment->user->name }}</span>
                                            <span class="text-gray-500 dark:text-gray-400 ml-2">{{ $comment->created_at->diffForHumans() }}</span>
                                        </div>
                                        @can('delete', $comment)
                                            <form action="{{ route('comments.destroy', $comment->id) }}" method="POST"
                                                class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-red-500 dark:text-red-400 hover:underline">削除</button>
                                            </form>
                                        @endcan
                                    </div>
                                    <p class="text-sm whitespace-pre-wrap text-gray-900 dark:text-gray-100">{{ $comment->body }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">まだコメントはありません</p>
                    @endif

                    {{-- タイピングインジケーター --}}
                    <div id="typing-indicator" class="mb-2 text-sm text-gray-500 dark:text-gray-400 italic" style="display: none;"></div>

                    {{-- コメント投稿フォーム --}}
                    <form action="{{ route('comments.store', $item->id) }}" method="POST">
                        @csrf
                        <textarea name="body" rows="3" placeholder="コメントを書く..."
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-400"></textarea>
                        @error('body')
                            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                        @enderror
                        <button type="submit" class="mt-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded transition">
                            投稿
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <script>
        window.userId = {{ auth()->id() }};
        @isset($team)
        window.teamId = {{ $team->id }};
        @endisset
    </script>
</x-app-layout>
