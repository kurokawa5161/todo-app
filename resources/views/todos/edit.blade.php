<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>ToDo一覧</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 min-h-screen p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">
        <h1 class="text-3xl font-bold text-blue-600 mb-6">編集画面</h1>

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

        {{-- 編集フォーム --}}
        <form action="{{ route('todos.update', $item->id) }}" method="POST" class="space-y-3 border-t pt-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium mb-1">カテゴリ</label>
                <select name="category_id" class="w-full px-3 py-2 border rounded">
                    <option value="">（なし）</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}"
                            {{ old('category_id', $category->id) == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">優先度</label>
                <select name="priority" class="w-full px-3 py-2 border rounded">
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
                <label class="block text-sm font-medium mb-1">タイトル</label>
                <input type="text" name="title" value="{{ old('title', $item->title) }}"
                    class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">内容</label>
                <input type="text" name="content" value="{{ old('content', $item->content) }}"
                    class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div class="flex gap-3">
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1">開始日</label>
                    <input type="date" name="start_date"
                        value="{{ old('start_date', $item->start_date?->format('Y-m-d')) }}"
                        class="w-full px-3 py-2 border rounded">
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1">終了日</label>
                    <input type="date" name="end_date"
                        value="{{ old('end_date', $item->end_date->format('Y-m-d')) }}"
                        class="w-full px-3 py-2 border rounded">
                </div>
            </div>
            <button type="submit" class="w-full py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-medium">
                編集
            </button>
        </form>

        {{-- コメント --}}
        <div class="mt-6 pt-4 border-t">
            <h2 class="text-lg font-bold mb-3">コメント</h2>

            {{-- コメント一覧 --}}
            @if ($item->comments->count() > 0)
                <ul class="space-y-2 mb-4">
                    @foreach ($item->comments as $comment)
                        <li class="p-3 bg-gray-50 border rounded">
                            <div class="flex items-center justify-between mb-1">
                                <div class="text-sm">
                                    <span class="font-medium">{{ $comment->user->name }}</span>
                                    <span class="text-gray-500 ml-2">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                @if ($comment->user_id === auth()->id())
                                    <form action="{{ route('comments.destroy', $comment->id) }}" method="POST"
                                        class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-500 hover:underline">削除</button>
                                    </form>
                                @endif
                            </div>
                            <p class="text-sm whitespace-pre-wrap">{{ $comment->body }}</p>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-500 mb-4">まだコメントはありません</p>
            @endif

            {{-- コメント投稿フォーム --}}
            <form action="{{ route('comments.store', $item->id) }}" method="POST">
                @csrf
                <textarea name="body" rows="3" placeholder="コメントを書く..."
                    class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400"></textarea>
                @error('body')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
                <button type="submit" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    投稿
                </button>
            </form>
        </div>

    </div>
</body>

</html>
