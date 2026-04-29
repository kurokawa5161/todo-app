<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Update Reminder') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('リマインダー設定を行う') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.reminder') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="reminder_days_before" :value="__('通知設定')" />

            <select id="reminder_days_before" name="reminder_days_before"
                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                <option value="">通知しない</option>
                <option value="1"
                    {{ old('reminder_days_before', $user->reminder_days_before) == 1 ? 'selected' : '' }}>1日前</option>
                <option value="2"
                    {{ old('reminder_days_before', $user->reminder_days_before) == 2 ? 'selected' : '' }}>2日前</option>
                <option value="3"
                    {{ old('reminder_days_before', $user->reminder_days_before) == 3 ? 'selected' : '' }}>3日前</option>
                <option value="7"
                    {{ old('reminder_days_before', $user->reminder_days_before) == 7 ? 'selected' : '' }}>7日前</option>
            </select>

            <x-input-error class="mt-2" :messages="$errors->get('reminder_days_before')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
