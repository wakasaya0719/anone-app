<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />

            <!-- Family Role -->
            <flux:field>
                <flux:label>あなたの続柄（任意）</flux:label>
                <select name="family_role"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700 dark:bg-gray-800">
                    <option value="">選択してください</option>
                    <option value="father" {{ old('family_role') === 'father' ? 'selected' : '' }}>父親</option>
                    <option value="mother" {{ old('family_role') === 'mother' ? 'selected' : '' }}>母親</option>
                    <option value="grandfather" {{ old('family_role') === 'grandfather' ? 'selected' : '' }}>祖父</option>
                    <option value="grandmother" {{ old('family_role') === 'grandmother' ? 'selected' : '' }}>祖母</option>
                    <option value="other" {{ old('family_role') === 'other' ? 'selected' : '' }}>その他</option>
                </select>
                <flux:error name="family_role" />
            </flux:field>

            <!-- Display Name -->
            <flux:field>
                <flux:label>表示名（任意）</flux:label>
                <flux:input
                    name="display_name"
                    type="text"
                    :value="old('display_name')"
                    placeholder="例：お父さん、パパ"
                />
                <flux:description>メモの「FROM」欄に自動的に表示される名前です</flux:description>
                <flux:error name="display_name" />
            </flux:field>

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts.auth>
