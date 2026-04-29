<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            👥 {{ $team->name }}
        </h2>
    </x-slot>

    <div class="py-12" data-team-page>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- メンバー一覧 -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md mb-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">メンバー</h3>
                @foreach ($team->users as $member)
                    <div
                        class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700 last:border-0">
                        <div>
                            <span class="text-gray-900 dark:text-gray-100">{{ $member->name }}</span>
                            <span class="text-gray-500 dark:text-gray-400">({{ $member->pivot->role }})</span>
                        </div>

                        @can('update', $team)
                            <form method="POST" action="{{ route('teams.members.remove', [$team, $member]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded transition">削除</button>
                            </form>
                        @endcan
                    </div>
                @endforeach
            </div>

            <!-- オンラインメンバー -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md mb-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">🟢 オンラインメンバー</h3>
                <div id="online-members" class="space-y-2">
                    <p class="text-sm text-gray-500 dark:text-gray-400">読み込み中...</p>
                </div>
            </div>

            @can('update', $team)
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md mb-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">メンバーを招待</h3>

                    <form method="POST" action="{{ route('teams.invite', $team) }}" class="mt-4">
                        @csrf

                        {{-- メールアドレス入力 --}}
                        <div class="mb-4">
                            <label for="email"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">メールアドレス</label>
                            <input type="email" name="email" id="email" required
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                            @error('email')
                                <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- 権限選択 --}}
                        <div class="mb-4">
                            <label for="role"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">権限</label>
                            <select name="role" id="role" required
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                                <option value="viewer">Viewer（閲覧のみ）</option>
                                <option value="member" selected>Member（メンバー）</option>
                                <option value="admin">Admin（管理者）</option>
                                <option value="owner">Owner（オーナー）</option>
                            </select>
                        </div>

                        {{-- 送信ボタン --}}
                        <button type="submit"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded transition">
                            招待を送信
                        </button>
                    </form>
                </div>
            @endcan

            <!-- メンバー追加フォーム -->
            @can('update', $team)
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md mb-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">メンバーを追加</h3>
                    <form method="POST" action="{{ route('teams.members.add', $team) }}" class="flex gap-2">
                        @csrf
                        <input type="number" name="user_id" placeholder="ユーザーID" required
                            class="flex-1 px-3 py-2 rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                        <select name="role" required
                            class="px-3 py-2 rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                            <option value="member">Member</option>
                            <option value="admin">Admin</option>
                            <option value="owner">Owner</option>
                            <option value="viewer">Viewer</option>
                        </select>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded transition">追加</button>
                    </form>
                </div>
            @endcan

            <!-- チーム設定 -->
            @can('delete', $team)
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md mb-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">チーム設定</h3>
                    <form method="POST" action="{{ route('teams.destroy', $team) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded transition">
                            チームを削除
                        </button>
                    </form>
                </div>
            @endcan


            {{-- タスク一覧 --}}
            <ul id="todos-list" class="space-y-2 mb-6">
                @foreach ($items as $item)
                    @php
                        $colorClass = match ($item->category->color ?? 'purple') {
                            'red' => 'bg-red-100 text-red-700',
                            'yellow' => 'bg-yellow-100 text-yellow-700',
                            'green' => 'bg-green-100 text-green-700',
                            'blue' => 'bg-blue-100 text-blue-700',
                            'purple' => 'bg-purple-100 text-purple-700',
                            'pink' => 'bg-pink-100 text-pink-700',
                            'gray' => 'bg-gray-100 text-gray-700',
                            default => 'bg-purple-100 text-purple-700',
                        };
                    @endphp

                    <li class="p-3 border border-gray-200 dark:border-gray-700 rounded bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                        data-todo-item>
                        {{-- 親タスクの情報（タイトル、バッジ、ボタンなど） --}}
                        <div class="flex items-center gap-2">
                            @if ($item->completed_at)
                                <span data-checkbox>✅</span>
                                <s class="flex-1 text-gray-400 dark:text-gray-500" data-title>{{ $item->title }}</s>
                            @else
                                <span data-checkbox>⬜</span>
                                <span class="flex-1 text-gray-900 dark:text-gray-100"
                                    data-title>{{ $item->title }}</span>
                            @endif
                            @if ($item->image_path)
                                <img src="{{ asset('storage/' . $item->image_path) }}"
                                    class="w-20 h-20 object-cover rounded">
                            @endif

                            @if ($item->category)
                                <span class="text-xs px-2 py-1 rounded {{ $colorClass }}">
                                    {{ $item->category->name }}
                                </span>
                            @endif

                            @php
                                $priorityBadge = match ($item->priority) {
                                    1 => ['🔴 高', 'bg-red-100 text-red-700'],
                                    2 => ['🟡 中', 'bg-yellow-100 text-yellow-700'],
                                    3 => ['🟢 低', 'bg-green-100 text-green-700'],
                                    default => null,
                                };
                            @endphp

                            @if ($priorityBadge)
                                <span class="text-xs px-2 py-1 rounded {{ $priorityBadge[1] }}">
                                    {{ $priorityBadge[0] }}
                                </span>
                            @endif

                            {{-- タグ表示 --}}
                            @if ($item->tags->count() > 0)
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($item->tags as $tag)
                                        @php
                                            $tagColorClass = match ($tag->color) {
                                                'red' => 'bg-red-100 text-red-700',
                                                'yellow' => 'bg-yellow-100 text-yellow-700',
                                                'green' => 'bg-green-100 text-green-700',
                                                'blue' => 'bg-blue-100 text-blue-700',
                                                'purple' => 'bg-purple-100 text-purple-700',
                                                'pink' => 'bg-pink-100 text-pink-700',
                                                'gray' => 'bg-gray-100 text-gray-700',
                                                default => 'bg-gray-100 text-gray-700',
                                            };
                                        @endphp
                                        <span class="text-xs px-2 py-0.5 rounded {{ $tagColorClass }}">
                                            🏷️ {{ $tag->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            <button type="button" data-pin-url="{{ route('todos.pin', $item->id) }}"
                                class="text-lg hover:scale-110 transition">
                                {{ $item->is_pinned ? '⭐' : '☆' }}
                            </button>

                            @php
                                $isOverdue = !$item->completed_at && $item->end_date->isPast();
                                $isSoon = !$item->completed_at && !$isOverdue && $item->end_date->lte(now()->addDay());
                            @endphp

                            <span
                                class="text-sm px-2 py-1 rounded
                            {{ $isOverdue ? 'bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 font-bold' : '' }}
                            {{ $isSoon ? 'bg-orange-100 dark:bg-orange-900 text-orange-700 dark:text-orange-300' : '' }}
                            {{ !$isOverdue && !$isSoon ? 'text-gray-500 dark:text-gray-400' : '' }}">
                                @if ($isOverdue)
                                    ⚠️
                                @elseif ($isSoon)
                                    ⏰
                                @endif
                                締切: {{ $item->end_date->format('Y-m-d') }}
                            </span>

                            @can('updateTeamTodo', [$team, $item])
                                <form action="{{ route('todos.edit', $item->id) }}" method="GET" class="inline">
                                    @csrf
                                    <button type="submit"
                                        class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                                        編集
                                    </button>
                                </form>

                                <button type="button" data-toggle-url="{{ route('todos.toggle', $item->id) }}"
                                    class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                                    <span>{{ $item->completed_at ? '戻す' : '完了' }}</span>
                                </button>
                            @endcan

                            @can('deleteTeamTodo', [$team, $item])
                                <form action="{{ route('todos.destroy', $item->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600">
                                        削除
                                    </button>
                                </form>
                            @endcan
                        </div>
                        {{-- サブタスク --}}
                        @if ($item->children->count() > 0)
                            <ul class="ml-8 mt-2 space-y-1" data-subtask-list>
                                @foreach ($item->children as $child)
                                    <li
                                        class="flex items-center gap-2 p-2 border border-gray-200 dark:border-gray-700 rounded bg-gray-50 dark:bg-gray-700">
                                        @if ($child->completed_at)
                                            ✅ <s
                                                class="flex-1 text-gray-400 dark:text-gray-500">{{ $child->title }}</s>
                                        @else
                                            ⬜ <span
                                                class="flex-1 text-gray-900 dark:text-gray-100">{{ $child->title }}</span>
                                        @endif
                                        {{-- 完了・削除ボタン --}}
                                        @can('updateTeamTodo', [$team, $child])
                                            <form action="{{ route('todos.toggle', $child->id) }}" method="POST"
                                                class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                    class="px-2 py-0.5 text-xs bg-blue-500 text-white rounded hover:bg-blue-600">
                                                    {{ $child->completed_at ? '戻す' : '完了' }}
                                                </button>
                                            </form>
                                        @endcan
                                        @can('deleteTeamTodo', [$team, $child])
                                            <form action="{{ route('todos.destroy', $child->id) }}" method="POST"
                                                class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="px-2 py-0.5 text-xs bg-red-500 text-white rounded hover:bg-red-600">
                                                    削除
                                                </button>
                                            </form>
                                        @endcan
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        {{-- サブタスク追加フォーム --}}
                        @can('createTeamTodo', $team)
                            <form action="{{ route('todos.store') }}" method="POST" class="ml-8 mt-2 flex gap-2"
                                data-subtask-form data-parent-id="{{ $item->id }}">
                                @csrf
                                <input type="hidden" name="team_id" value="{{ $team->id }}">
                                <input type="hidden" name="parent_id" value="{{ $item->id }}">
                                <input type="text" name="title" placeholder="サブタスク追加"
                                    class="flex-1 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-blue-500 focus:border-blue-500">
                                <button type="submit"
                                    class="px-3 py-1 text-sm bg-gray-600 text-white rounded hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 transition">
                                    追加
                                </button>
                            </form>
                        @endcan
                    </li>
                @endforeach
            </ul>
            <div class="my-6">
                {{ $items->links() }}
            </div>

            {{-- 追加フォーム --}}
            @can('createTeamTodo', $team)
                <form action="{{ route('todos.store') }}" method="post"
                    class="space-y-3 border-t border-gray-200 dark:border-gray-700 pt-4" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="team_id" value="{{ $team->id }}">
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">カテゴリ</label>
                        <select name="category_id"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">（なし）</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">優先度</label>
                        <select name="priority"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                            <option value="1" {{ old('priority') == 1 ? 'selected' : '' }}>
                                高
                            </option>
                            <option value="2" {{ old('priority') == 2 ? 'selected' : '' }}>
                                中
                            </option>
                            <option value="3" {{ old('priority') == 3 ? 'selected' : '' }}>
                                低
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">タイトル</label>
                        <input type="text" name="title" value="{{ old('title') }}"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">内容</label>
                        <input type="text" name="content" value="{{ old('content') }}"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div class="flex gap-3">
                        <div class="flex-1">
                            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">開始日</label>
                            <input type="date" name="start_date" value="{{ old('start_date') }}"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">終了日</label>
                            <input type="date" name="end_date" value="{{ old('end_date') }}"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <input type="file" name="image" accept="image/*"
                        class="block w-full text-sm text-gray-900 dark:text-gray-100
                               file:mr-4 file:py-2 file:px-4
                               file:rounded file:border-0
                               file:text-sm file:font-medium
                               file:bg-blue-50 file:text-blue-700
                               dark:file:bg-blue-900 dark:file:text-blue-300
                               hover:file:bg-blue-100 dark:hover:file:bg-blue-800
                               cursor-pointer">
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">タグ</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($tags as $tag)
                                @php
                                    $colorClass = match ($tag->color) {
                                        'red' => 'bg-red-100 text-red-700',
                                        'yellow' => 'bg-yellow-100 text-yellow-700',
                                        'green' => 'bg-green-100 text-green-700',
                                        'blue' => 'bg-blue-100 text-blue-700',
                                        'purple' => 'bg-purple-100 text-purple-700',
                                        'pink' => 'bg-pink-100 text-pink-700',
                                        'gray' => 'bg-gray-100 text-gray-700',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp

                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="checkbox" name="tags[]" value="{{ $tag->id }}" class="rounded">
                                    <span class="px-2 py-1 rounded text-xs {{ $colorClass }}">
                                        {{ $tag->name }}
                                    </span>
                                </label>
                            @endforeach

                            @if ($tags->count() == 0)
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    タグがありません。
                                    <a href="{{ route('tags.index') }}"
                                        class="text-blue-600 dark:text-blue-400 hover:underline">
                                        タグ管理
                                    </a>
                                    から作成してください。
                                </p>
                            @endif
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded font-medium transition">
                        追加
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <script>
        window.userId = {{ auth()->id() }};
        window.teamId = {{ $team->id }};
    </script>
</x-app-layout>
