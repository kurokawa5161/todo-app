<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>カテゴリ管理</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 min-h-screen p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">
        <h1 class="text-3xl font-bold text-blue-600 mb-6">カテゴリ管理</h1>

        <nav class="mb-4">
            <a href="{{ route('todos.index') }}" class="text-blue-600 hover:underline">
                → ToDo一覧へ
            </a>
        </nav>

        {{-- カテゴリ一覧 --}}
        <ul class="space-y-2 mb-6">
            @foreach ($categories as $category)
                <li class="flex items-center gap-2 p-3 border rounded hover:bg-gray-50">
                    <span class="flex-1">{{ $category->name }}</span>
                    <form action="{{ route('category.destroy', $category->id) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600">
                            削除
                        </button>
                    </form>
                </li>
            @endforeach
        </ul>

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
        <form action="{{ route('category.store') }}" method="post" class="space-y-3 border-t pt-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">カテゴリー名</label>
                <input type="text" name="name" value="{{ old('name') }}"
                    class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <button type="submit" class="w-full py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-medium">
                追加
            </button>
        </form>
    </div>
</body>

</html>
