<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            ✏️ エクスポートテンプレート編集
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6">
                <form action="{{ route('export-templates.update', $template) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    {{-- テンプレート名 --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            テンプレート名 <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name"
                            value="{{ old('name', $template->name) }}" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="例: 週次レポート用">
                        @error('name')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- 説明 --}}
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            説明 <span class="text-red-500">*</span>
                        </label>
                        <textarea name="description" id="description" rows="3" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="このテンプレートの用途を記入">{{ old('description', $template->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- フォーマット選択 --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            出力形式 <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach (['csv' => 'CSV', 'excel' => 'Excel', 'json' => 'JSON', 'xml' => 'XML'] as $value => $label)
                                <label
                                    class="relative flex items-center justify-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition
                                    {{ old('format', $template->format) === $value ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30' : 'border-gray-300 dark:border-gray-600' }}">
                                    <input type="radio" name="format" value="{{ $value }}"
                                        {{ old('format', $template->format) === $value ? 'checked' : '' }} required
                                        class="sr-only peer">
                                    <span
                                        class="text-center font-medium text-gray-900 dark:text-gray-100 peer-checked:text-blue-600 dark:peer-checked:text-blue-400">
                                        {{ $label }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('format')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- フィールド選択 --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            出力項目 <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            @php
                                $availableFields = [
                                    'id' => 'ID',
                                    'title' => 'タイトル',
                                    'content' => '内容',
                                    'category' => 'カテゴリー',
                                    'tags' => 'タグ',
                                    'priority' => '優先度',
                                    'start_date' => '開始日',
                                    'end_date' => '終了日',
                                    'completed_at' => '完了日',
                                    'status' => 'ステータス',
                                ];
                                $selectedFields = old('fields', $template->fields ?? []);
                            @endphp
                            @foreach ($availableFields as $value => $label)
                                <label
                                    class="flex items-center p-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                    <input type="checkbox" name="fields[]" value="{{ $value }}"
                                        {{ in_array($value, $selectedFields) ? 'checked' : '' }}
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <span class="ml-2 text-sm text-gray-900 dark:text-gray-100">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('fields')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- 並び順選択 --}}
                    <div>
                        <label for="order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            並び順（複数選択可）
                        </label>
                        @php
                            $selectedOrder = old('order', $template->order ?? []);
                        @endphp
                        <select name="order[]" id="order" multiple size="5"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                            @foreach ($availableFields as $value => $label)
                                <option value="{{ $value }}"
                                    {{ in_array($value, $selectedOrder) ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Ctrl（Mac: Cmd）を押しながらクリックで複数選択
                        </p>
                        @error('order')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- フィルター設定 --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            フィルター設定
                        </label>
                        <div class="space-y-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            {{-- ステータスフィルター --}}
                            <div>
                                <label for="filter_status"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    ステータス
                                </label>
                                @php
                                    $filterStatus = old('filters.status', $template->filters['status'] ?? '');
                                @endphp
                                <select name="filters[status]" id="filter_status"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="" {{ $filterStatus === '' ? 'selected' : '' }}>すべて
                                    </option>
                                    <option value="completed" {{ $filterStatus === 'completed' ? 'selected' : '' }}>
                                        完了のみ</option>
                                    <option value="active" {{ $filterStatus === 'active' ? 'selected' : '' }}>
                                        未完了のみ</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- ボタン --}}
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <a href="{{ route('export-templates.index') }}"
                            class="px-6 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-800 dark:text-gray-100 font-semibold rounded-lg transition">
                            キャンセル
                        </a>
                        <button type="submit"
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                            更新
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
