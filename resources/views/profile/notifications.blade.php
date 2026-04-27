<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('通知設定') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                通知設定
                            </h2>

                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                受け取る通知の種類を選択してください。
                            </p>
                        </header>

                        <form method="post" action="{{ route('profile.notifications.update') }}" class="mt-6 space-y-6">
                            @csrf
                            @method('patch')

                            <!-- プッシュ通知 -->
                            <div class="flex items-center">
                                <input
                                    type="checkbox"
                                    name="push_enabled"
                                    id="push_enabled"
                                    value="1"
                                    {{ $setting->push_enabled ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700"
                                >
                                <label for="push_enabled" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    プッシュ通知を有効にする
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">ブラウザ通知でリアルタイムに受け取ります</span>
                                </label>
                            </div>

                            <!-- タスク割り当て通知 -->
                            <div class="flex items-center">
                                <input
                                    type="checkbox"
                                    name="task_assigned_enabled"
                                    id="task_assigned_enabled"
                                    value="1"
                                    {{ $setting->task_assigned_enabled ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700"
                                >
                                <label for="task_assigned_enabled" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    タスク割り当て通知（メール）
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">タスクが割り当てられたときにメールで通知</span>
                                </label>
                            </div>

                            <!-- コメント通知 -->
                            <div class="flex items-center">
                                <input
                                    type="checkbox"
                                    name="comment_email_enabled"
                                    id="comment_email_enabled"
                                    value="1"
                                    {{ $setting->comment_email_enabled ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700"
                                >
                                <label for="comment_email_enabled" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    コメント通知（メール）
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">コメントが投稿されたときにメールで通知</span>
                                </label>
                            </div>

                            <!-- 週次レポート -->
                            <div class="flex items-center">
                                <input
                                    type="checkbox"
                                    name="weekly_report_enabled"
                                    id="weekly_report_enabled"
                                    value="1"
                                    {{ $setting->weekly_report_enabled ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700"
                                >
                                <label for="weekly_report_enabled" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    週次レポート
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">毎週月曜日の朝に進捗レポートをメールで受け取ります</span>
                                </label>
                            </div>

                            <div class="flex items-center gap-4">
                                <x-primary-button>{{ __('保存') }}</x-primary-button>

                                @if (session('status') === 'notifications-updated')
                                    <p
                                        x-data="{ show: true }"
                                        x-show="show"
                                        x-transition
                                        x-init="setTimeout(() => show = false, 2000)"
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                    >{{ __('保存しました。') }}</p>
                                @endif
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
