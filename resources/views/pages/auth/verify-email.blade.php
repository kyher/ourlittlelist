<x-layouts::auth :title="__('Email verification')">
    <div class="mt-4 flex flex-col gap-6">
        <flux:text class="text-center">
            {{ __('Please verify :email using the link in your verification email. If you can\'t find it, you can send a new one below.', ['email' => auth()->user()->email]) }}
        </flux:text>

        @if (session('status') == 'verification-link-sent')
            <flux:text class="text-center font-medium text-green-700! dark:text-green-400!">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </flux:text>
        @endif

        @if (session('status') == 'verification-link-invalid')
            <flux:text class="text-center font-medium text-red-700! dark:text-red-400!">
                {{ __('That link has expired or is invalid. Please request a new one.') }}
            </flux:text>
        @endif

        <div class="flex flex-col items-center justify-between space-y-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Resend verification email') }}
                </flux:button>
            </form>

            <flux:link :href="route('profile.edit')" wire:navigate class="text-sm">
                {{ __('Change your email address') }}
            </flux:link>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button variant="ghost" type="submit" class="text-sm cursor-pointer" data-test="logout-button">
                    {{ __('Log out') }}
                </flux:button>
            </form>
        </div>
    </div>
</x-layouts::auth>
