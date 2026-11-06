<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Find your partner')] class extends Component {
    /**
     * Maximum searches per user per minute (FR-012).
     */
    private const MAX_SEARCHES_PER_MINUTE = 30;

    public string $query = '';

    /**
     * Only the username and display name of a match, never the email (FR-009).
     *
     * @var array{username: string, name: string}|null
     */
    public ?array $result = null;

    public bool $searched = false;

    /**
     * Look up another user by exact username, ignoring case and surrounding whitespace.
     */
    public function search(): void
    {
        $this->resetErrorBag();
        $this->result = null;

        $term = Str::lower(trim($this->query));

        if ($term === '') {
            $this->searched = false;
            $this->addError('query', __('Enter a username to search for.'));

            return;
        }

        $key = 'partner-search:'.Auth::id();

        if (! RateLimiter::attempt($key, self::MAX_SEARCHES_PER_MINUTE, fn () => true, 60)) {
            $this->searched = false;
            $this->addError('query', __('Too many searches. Try again in :seconds seconds.', [
                'seconds' => RateLimiter::availableIn($key),
            ]));

            return;
        }

        $user = User::query()
            ->where('username', $term)
            ->whereKeyNot(Auth::id())
            ->first(['id', 'name', 'username']);

        $this->result = $user ? ['username' => $user->username, 'name' => $user->name] : null;
        $this->searched = true;
    }
}; ?>

<section class="w-full max-w-lg">
    <flux:heading size="xl" level="1">{{ __('Find your partner') }}</flux:heading>
    <flux:subheading class="mb-6">{{ __('Ask your partner for their username, then search for it here.') }}</flux:subheading>

    <form wire:submit="search" class="flex items-end gap-3">
        <div class="flex-1">
            <flux:input
                wire:model="query"
                :label="__('Username')"
                type="text"
                autocomplete="off"
                :placeholder="__('e.g. harry')"
                data-test="partner-search-input"
            />
        </div>

        <flux:button variant="primary" type="submit" data-test="partner-search-button">
            {{ __('Search') }}
        </flux:button>
    </form>

    @if ($result)
        <flux:card class="mt-6 flex items-center gap-4" data-test="partner-search-result">
            <flux:avatar :name="$result['name']" />
            <div>
                <flux:heading>{{ $result['name'] }}</flux:heading>
                <flux:text>{{ '@'.$result['username'] }}</flux:text>
            </div>
        </flux:card>
    @elseif ($searched)
        <flux:text class="mt-6" data-test="partner-search-empty">
            {{ __('No user found with that username.') }}
        </flux:text>
    @endif
</section>
