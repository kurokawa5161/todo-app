<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            🗄️ Database Viewer
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6">

                {{-- テーブル一覧 --}}
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-3">テーブル一覧</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($tables as $table)
                            @php
                                // SQLiteとMySQLで構造が異なる
                                $t = $driver === 'sqlite' ? $table->name : array_values((array) $table)[0];
                            @endphp
                            <a href="?table={{ $t }}"
                                class="px-4 py-2 rounded text-sm font-medium transition
                                    {{ $tableName === $t
                                        ? 'bg-green-600 text-white'
                                        : 'bg-blue-500 text-white hover:bg-blue-600' }}">
                                {{ $t }}
                            </a>
                        @endforeach
                    </div>
                </div>

                @if ($tableName)
                    {{-- テーブル詳細 --}}
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-3">
                            テーブル: <span class="text-blue-600 dark:text-blue-400">{{ $tableName }}</span>
                        </h3>
                    </div>

                    {{-- カラム構造 --}}
                    <div class="mb-6">
                        <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 mb-2">カラム構造</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-green-600">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">フィールド</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">型</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">NULL</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">キー</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">デフォルト</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($columns as $col)
                                        <tr>
                                            @if ($driver === 'sqlite')
                                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-200">{{ $col->name }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $col->type }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $col->notnull ? 'NO' : 'YES' }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $col->pk ? 'PRI' : '' }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $col->dflt_value }}</td>
                                            @else
                                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-200">{{ $col->Field }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $col->Type }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $col->Null }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $col->Key }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $col->Default }}</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- データ --}}
                    <div>
                        <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            データ ({{ $data->total() }}件)
                        </h4>
                        @if ($data->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-green-600">
                                        <tr>
                                            @foreach (array_keys((array) $data->first()) as $key)
                                                <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">
                                                    {{ $key }}
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach ($data as $row)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                @foreach ((array) $row as $value)
                                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-200">
                                                        {{ is_string($value) ? Str::limit($value, 50) : $value }}
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4">
                                {{ $data->links() }}
                            </div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400">データがありません</p>
                        @endif
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
