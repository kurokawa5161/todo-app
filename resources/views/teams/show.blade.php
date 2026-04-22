<x-app-layout>
    <x-slot name="header">
        <h2>{{ $team->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- メンバー一覧 -->
            <div class="bg-white p-6 rounded mb-6">
                <h3 class="text-lg font-semibold mb-4">メンバー</h3>
                @foreach ($team->users as $member)
                    <div class="flex justify-between items-center py-2">
                        <div>
                            <span>{{ $member->name }}</span>
                            <span class="text-gray-500">({{ $member->pivot->role }})</span>
                        </div>

                        @can('update', $team)
                            <form method="POST" action="{{ route('teams.members.remove', [$team, $member]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit">削除</button>
                            </form>
                        @endcan
                    </div>
                @endforeach
            </div>

            <!-- メンバー追加フォーム -->
            @can('update', $team)
                <div class="bg-white p-6 rounded mb-6">
                    <h3 class="text-lg font-semibold mb-4">メンバーを追加</h3>
                    <form method="POST" action="{{ route('teams.members.add', $team) }}">
                        @csrf
                        <input type="number" name="user_id" placeholder="ユーザーID" required>
                        <select name="role" required>
                            <option value="member">Member</option>
                            <option value="admin">Admin</option>
                            <option value="owner">Owner</option>
                            <option value="viewer">Viewer</option>
                        </select>
                        <button type="submit">追加</button>
                    </form>
                </div>
            @endcan

            <!-- チーム設定 -->
            @can('delete', $team)
                <div class="bg-white p-6 rounded">
                    <form method="POST" action="{{ route('teams.destroy', $team) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-500">チームを削除</button>
                    </form>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
