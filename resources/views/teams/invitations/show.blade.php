<x-app-layout>
    <x-slot name="header">
        <h2>チーム招待</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 rounded shadow">

                {{-- チーム情報 --}}
                <h3 class="text-2xl font-bold mb-4">
                    {{ $invitation->team->name }} への招待
                </h3>

                <div class="mb-6">
                    <p class="text-gray-700 mb-2">
                        <strong>{{ $invitation->team->name }}</strong> に招待されました。
                    </p>
                    <p class="text-gray-600 mb-2">
                        招待された権限: <strong>{{ $invitation->role }}</strong>
                    </p>
                    <p class="text-gray-600 text-sm">
                        有効期限: {{ $invitation->expires_at->format('Y年m月d日 H:i') }}
                    </p>
                </div>

                {{-- 参加ボタン --}}
                @auth
                    <form method="POST" action="{{ route('teams.invitations.accept', $invitation->token) }}">
                        @csrf
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-6 rounded">
                            チームに参加する
                        </button>
                    </form>
                @else
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
                        <p class="font-bold">ログインが必要です</p>
                        <p class="text-sm">チームに参加するにはログインしてください。</p>
                    </div>

                    <div class="space-x-4">
                        <a href="{{ route('login') }}"
                            class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded inline-block">
                            ログイン
                        </a>
                        <a href="{{ route('register') }}"
                            class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded inline-block">
                            新規登録
                        </a>
                    </div>
                @endauth

            </div>
        </div>
    </div>

    <script>
        window.userId = {{ auth()->id() }};
        @isset($team)
            window.teamId = {{ $team->id }};
        @endisset
    </script>

</x-app-layout>
