<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            👥 チーム
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- チーム作成フォーム -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md mb-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">新しいチームを作成</h3>
                <form method="POST" action="{{ route('teams.store') }}" class="flex gap-2">
                    @csrf
                    <input type="text" name="name" placeholder="チーム名" required
                        class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-blue-500 focus:border-blue-500">
                    <button type="submit" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded transition">作成</button>
                </form>
            </div>

            <!-- チーム一覧 -->
            @foreach ($teams as $team)
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md mb-4">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $team->name }}</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        権限: {{ $team->pivot->role }} |
                        メンバー: {{ $team->users_count }}人
                    </p>
                    <a href="{{ route('teams.show', $team) }}" class="text-blue-500 dark:text-blue-400 hover:underline">詳細を見る</a>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
