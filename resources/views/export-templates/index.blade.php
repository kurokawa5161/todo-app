<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            📋 エクスポートテンプレート管理
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- 成功メッセージ --}}
            @if (session('success'))
                <div
                    class="mb-4 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-700 dark:text-green-300">{{ session('success') }}</p>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6">
                {{-- ヘッダー --}}
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        テンプレート一覧
                    </h3>
                    <a href="{{ route('export-templates.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                        新規作成
                    </a>
                </div>

                {{-- テンプレート一覧 --}}
                @if ($templates->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($templates as $template)
                            <div
                                class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition">
                                {{-- テンプレート名 --}}
                                <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-2 flex items-center">
                                    @switch($template->format)
                                        @case('csv')
                                            <span class="mr-2">📄</span>
                                        @break

                                        @case('excel')
                                            <span class="mr-2">📊</span>
                                        @break

                                        @case('json')
                                            <span class="mr-2">🔧</span>
                                        @break

                                        @case('xml')
                                            <span class="mr-2">📋</span>
                                        @break
                                    @endswitch
                                    {{ $template->name }}
                                </h4>

                                {{-- 説明 --}}
                                @if ($template->description)
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        {{ Str::limit($template->description, 100) }}
                                    </p>
                                @endif

                                {{-- メタ情報 --}}
                                <div class="flex flex-wrap gap-2 mb-3">
                                    <span
                                        class="inline-flex items-center px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs font-semibold rounded">
                                        {{ strtoupper($template->format) }}
                                    </span>
                                    <span
                                        class="inline-flex items-center px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs rounded">
                                        {{ count($template->fields) }}項目
                                    </span>
                                </div>

                                {{-- アクションボタン --}}
                                <div class="flex gap-2">
                                    {{-- エクスポート実行ボタン --}}
                                    <a href="{{ route('export-templates.export', $template) }}"
                                        class="flex-1 px-3 py-2 bg-green-500 hover:bg-green-600 text-white rounded text-sm transition text-center">
                                        エクスポート
                                    </a>

                                    {{-- 編集ボタン --}}
                                    <a href="{{ route('export-templates.edit', $template) }}"
                                        class="px-3 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded text-sm transition">
                                        編集
                                    </a>

                                    {{-- 削除ボタン --}}
                                    <form action="{{ route('export-templates.destroy', $template) }}" method="POST"
                                        class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded text-sm transition"
                                            onclick="return confirm('「{{ $template->name }}」を削除しますか？')">
                                            削除
                                        </button>
                                    </form>
                                </div>

                                {{-- 作成日時 --}}
                                <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                    作成: {{ $template->created_at->format('Y/m/d H:i') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- 空の状態 --}}
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">テンプレートがありません</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            エクスポート用のテンプレートを作成しましょう
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('export-templates.create') }}"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                新規作成
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
