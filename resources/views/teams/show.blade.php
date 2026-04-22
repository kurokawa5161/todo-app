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

            @can('update', $team)
                <div class="bg-white p-6 rounded mb-6">
                    <h3 class="text-lg font-semibold mb-4">メンバーを招待</h3>

                    <form method="POST" action="{{ route('teams.invite', $team) }}" class="mt-4">
                        @csrf

                        {{-- メールアドレス入力 --}}
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700">メールアドレス</label>
                            <input type="email" name="email" id="email" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('email')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- 権限選択 --}}
                        <div class="mb-4">
                            <label for="role" class="block text-sm font-medium text-gray-700">権限</label>
                            <select name="role" id="role" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="viewer">Viewer（閲覧のみ）</option>
                                <option value="member" selected>Member（メンバー）</option>
                                <option value="admin">Admin（管理者）</option>
                                <option value="owner">Owner（オーナー）</option>
                            </select>
                        </div>

                        {{-- 送信ボタン --}}
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                            招待を送信
                        </button>
                    </form>
                </div>
            @endcan

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
