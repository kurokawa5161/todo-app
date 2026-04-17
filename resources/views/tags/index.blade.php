<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>タグ管理</title>
    @vite(['resources/css/app.css'])
</head>

<body class="bg-gray-100 min-h-screen p-8">

    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">

        <h1 class="text-3xl font-bold text-blue-600 mb-6">タグ管理</h1>

        <nav class="mb-4">
            <a href="{{ route('todos.index') }}" class="text-blue-600 hover:underline">
                ← ToDo一覧へ戻る
            </a>
        </nav>

        {{-- タグ一覧 --}}
        <div class="mb-6">
            <h2 class="text-xl font-bold mb-3">登録済みタグ</h2>

            @if ($tags->count() > 0)
                <ul class="space-y-2">
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

                        <li class="flex items-center gap-2 p-3 border rounded hover:bg-gray-50">
                            <span class="px-3 py-1 rounded text-sm {{ $colorClass }}">
                                🏷️ {{ $tag->name }}
                            </span>

                            <span class="text-sm text-gray-500">
                                ({{ $tag->todos->count() }}個のTodoに使用中)
                            </span>

                            @can('delete', $tag)
                                <div class="ml-auto">
                                    <form action="{{ route('tags.destroy', $tag->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-sm"
                                            onclick="return confirm('このタグを削除しますか？')">
                                            削除
                                        </button>
                                    </form>
                                </div>
                            @endcan
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-gray-500">タグがまだありません。下のフォームから作成してください。</p>
            @endif
        </div>

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

        {{-- タグ作成フォーム --}}
        <div class="border-t pt-4">
            <h2 class="text-xl font-bold mb-3">新しいタグを作成</h2>

            <form action="{{ route('tags.store') }}" method="POST" class="space-y-3">
                @csrf

                <div>
                    <label class="block text-sm font-medium mb-1">タグ名</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="例: 重要、急ぎ、相談中"
                        maxlength="20"
                        class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">色</label>
                    <div class="flex gap-2">
                        <label class="flex items-center gap-1">
                            <input type="radio" name="color" value="red"
                                {{ old('color') == 'red' ? 'checked' : '' }}>
                            <span class="px-3 py-1 rounded bg-red-100 text-red-700 text-sm">赤</span>
                        </label>
                        <label class="flex items-center gap-1">
                            <input type="radio" name="color" value="yellow"
                                {{ old('color') == 'yellow' ? 'checked' : '' }}>
                            <span class="px-3 py-1 rounded bg-yellow-100 text-yellow-700 text-sm">黄</span>
                        </label>
                        <label class="flex items-center gap-1">
                            <input type="radio" name="color" value="green"
                                {{ old('color') == 'green' ? 'checked' : '' }}>
                            <span class="px-3 py-1 rounded bg-green-100 text-green-700 text-sm">緑</span>
                        </label>
                        <label class="flex items-center gap-1">
                            <input type="radio" name="color" value="blue"
                                {{ old('color') == 'blue' ? 'checked' : '' }}>
                            <span class="px-3 py-1 rounded bg-blue-100 text-blue-700 text-sm">青</span>
                        </label>
                        <label class="flex items-center gap-1">
                            <input type="radio" name="color" value="purple"
                                {{ old('color') == 'purple' ? 'checked' : '' }}>
                            <span class="px-3 py-1 rounded bg-purple-100 text-purple-700 text-sm">紫</span>
                        </label>
                        <label class="flex items-center gap-1">
                            <input type="radio" name="color" value="gray"
                                {{ old('color', 'gray') == 'gray' ? 'checked' : '' }}>
                            <span class="px-3 py-1 rounded bg-gray-100 text-gray-700 text-sm">灰</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="w-full py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-medium">
                    タグを作成
                </button>
            </form>
        </div>

    </div>

</body>

</html>
