<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            📁 カテゴリ
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6">

        {{-- カテゴリ一覧 --}}
        <ul class="space-y-2 mb-6">
            @foreach ($categories as $category)
                <li class="flex items-center gap-2 p-3 border border-gray-200 dark:border-gray-700 rounded bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <span class="flex-1 text-gray-900 dark:text-gray-100">{{ $category->name }}</span>
                    @can('delete', $category)
                        <form action="{{ route('categories.destroy', $category->id) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition">
                                削除
                            </button>
                        </form>
                    @endcan
                </li>
            @endforeach
        </ul>

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

        {{-- 追加フォーム --}}
        <form action="{{ route('categories.store') }}" method="post" class="space-y-3 border-t border-gray-200 dark:border-gray-700 pt-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">カテゴリー名</label>
                <input type="text" name="name" value="{{ old('name') }}"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            {{-- カテゴリカラー --}}
            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">色</label>
            <select name="color" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                <option value="">（なし）</option>
                <option value="red" {{ old('color') == 'red' ? 'selected' : '' }}>🔴 赤</option>
                <option value="yellow" {{ old('color') == 'yellow' ? 'selected' : '' }}>🟡 黄</option>
                <option value="green" {{ old('color') == 'green' ? 'selected' : '' }}>🟢 緑</option>
                <option value="blue" {{ old('color') == 'blue' ? 'selected' : '' }}>🔵 青</option>
                <option value="purple" {{ old('color') == 'purple' ? 'selected' : '' }}>🟣 紫</option>
                <option value="pink" {{ old('color') == 'pink' ? 'selected' : '' }}>💗 ピンク</option>
                <option value="gray" {{ old('color') == 'gray' ? 'selected' : '' }}>⚫ グレー</option>
            </select>
            <button type="submit" class="w-full py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded font-medium transition">
                追加
            </button>
        </form>
            </div>
        </div>
    </div>
</x-app-layout>
