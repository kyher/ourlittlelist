<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::auth')] #[Title('Choose a username')] class extends Component {
    use ProfileValidationRules;

    public string $username = '';

    /**
     * Users who already have a username have nothing to do here.
     */
    public function mount(): void
    {
        if (Auth::user()->username !== null) {
            $this->redirectRoute('partner.search', navigate: true);
        }
    }

    /**
     * Save the chosen username and continue to where the user was going.
     */
    public function save(): void
    {
        $this->username = $this->normaliseUsername($this->username);

        $this->validate(
            ['username' => $this->usernameRules(Auth::id())],
            $this->usernameMessages(),
        );

        try {
            Auth::user()->forceFill(['username' => $this->username])->save();
        } catch (UniqueConstraintViolationException) {
            $this->addError('username', __('That username is taken.'));

            return;
        }

        $this->redirectIntended(default: route('partner.search', absolute: false), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Choose a username')"
        :description="__('Your partner will use this to find you.')"
    />

    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:input
            wire:model="username"
            :label="__('Username')"
            :description:trailing="__('3–30 characters: letters, numbers, _ and .')"
            type="text"
            required
            autofocus
            autocomplete="username"
            :placeholder="__('e.g. harry')"
        />

        <flux:button variant="primary" type="submit" class="w-full" data-test="choose-username-button">
            {{ __('Save') }}
        </flux:button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="text-center">
        @csrf
        <flux:button variant="ghost" size="sm" type="submit">{{ __('Log out') }}</flux:button>
    </form>
</div>
