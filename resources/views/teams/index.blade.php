<x-app-layout>
    <x-slot name="header">
        <h2>マイチーム</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- チーム作成フォーム -->
            <div class="bg-white p-6 rounded mb-6">
                <h3 class="text-lg font-semibold mb-4">新しいチームを作成</h3>
                <form method="POST" action="{{ route('teams.store') }}">
                    @csrf
                    <input type="text" name="name" placeholder="チーム名" required>
                    <button type="submit">作成</button>
                </form>
            </div>

            <!-- チーム一覧 -->
            @foreach ($teams as $team)
                <div class="bg-white p-6 rounded mb-4">
                    <h3 class="text-xl font-bold">{{ $team->name }}</h3>
                    <p class="text-gray-600">
                        権限: {{ $team->pivot->role }} |
                        メンバー: {{ $team->users_count }}人
                    </p>
                    <a href="{{ route('teams.show', $team) }}" class="text-blue-500">詳細を見る</a>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
