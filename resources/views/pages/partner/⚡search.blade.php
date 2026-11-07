<?php

use App\Actions\Couples\AcceptCoupleRequest;
use App\Actions\Couples\CancelCoupleRequest;
use App\Actions\Couples\DeclineCoupleRequest;
use App\Actions\Couples\SendCoupleRequest;
use App\Enums\CoupleRequestStatus;
use App\Exceptions\CoupleActionException;
use App\Models\CoupleRequest;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Find your partner')] class extends Component {
    /**
     * Maximum searches per user per minute (FR-012 of feature 002).
     */
    private const MAX_SEARCHES_PER_MINUTE = 30;

    /**
     * Session key holding outgoing request ids whose declined/closed notice was dismissed.
     */
    private const DISMISSED_KEY = 'couple-requests.dismissed';

    public string $query = '';

    /**
     * Only the username, display name and relation of a match, never the email (FR-009 of
     * feature 002) and never a coupled user's partner (FR-003).
     *
     * @var array{username: string, name: string, relation: string, request_id: int|null}|null
     */
    public ?array $result = null;

    public bool $searched = false;

    /**
     * Users already in a couple go to their reviews instead (FR-001).
     */
    public function mount(): void
    {
        if (Auth::user()->isInCouple()) {
            $this->redirectRoute('reviews.index', navigate: true);
        }
    }

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

        $user = $this->findUser($term);

        $this->result = $user ? $this->describe($user) : null;
        $this->searched = true;
    }

    /**
     * Send a couple request to the user in the current search result.
     */
    public function sendRequest(SendCoupleRequest $send): void
    {
        $this->resetErrorBag();

        // Re-resolve the receiver on the server; never trust client state.
        $receiver = $this->result ? $this->findUser($this->result['username']) : null;

        if (! $receiver) {
            $this->addError('query', __('No user found with that username.'));

            return;
        }

        try {
            $send(Auth::user(), $receiver);
        } catch (CoupleActionException $e) {
            $this->addError('query', $e->getMessage());
        }

        $this->refreshResult();
    }

    public function cancelRequest(int $id, CancelCoupleRequest $cancel): void
    {
        $request = CoupleRequest::findOrFail($id);
        $this->authorize('cancel', $request);

        $this->attempt(fn () => $cancel($request));
    }

    public function acceptRequest(int $id, AcceptCoupleRequest $accept): void
    {
        $request = CoupleRequest::findOrFail($id);
        $this->authorize('accept', $request);

        if ($this->attempt(fn () => $accept($request))) {
            Flux::toast(variant: 'success', text: __('You and :name are now a couple.', ['name' => $request->sender->name]));
            $this->redirectRoute('reviews.index', navigate: true);
        }
    }

    public function declineRequest(int $id, DeclineCoupleRequest $decline): void
    {
        $request = CoupleRequest::findOrFail($id);
        $this->authorize('decline', $request);

        $this->attempt(fn () => $decline($request));
    }

    /**
     * Hide the current declined or closed outgoing notice.
     */
    public function dismissOutgoing(): void
    {
        if ($this->outgoing && $this->outgoing['status'] !== CoupleRequestStatus::Pending->value) {
            session()->push(self::DISMISSED_KEY, $this->outgoing['id']);
            unset($this->outgoing);
        }
    }

    /**
     * Pending requests other users have sent to the current user.
     *
     * @return list<array{id: int, name: string, username: string}>
     */
    #[Computed]
    public function incoming(): array
    {
        return Auth::user()->receivedCoupleRequests()
            ->pending()
            ->with('sender:id,name,username')
            ->latest()
            ->get()
            ->map(fn (CoupleRequest $request) => [
                'id' => $request->id,
                'name' => $request->sender->name,
                'username' => $request->sender->username,
            ])
            ->all();
    }

    /**
     * The current user's latest outgoing request, when it is pending, or declined/closed and
     * not yet dismissed.
     *
     * @return array{id: int, name: string, username: string, status: string}|null
     */
    #[Computed]
    public function outgoing(): ?array
    {
        $request = Auth::user()->sentCoupleRequests()
            ->with('receiver:id,name,username')
            ->latest('id')
            ->first();

        $visible = [CoupleRequestStatus::Pending, CoupleRequestStatus::Declined, CoupleRequestStatus::Closed];

        if (! $request
            || ! in_array($request->status, $visible, true)
            || in_array($request->id, session(self::DISMISSED_KEY, []), true)) {
            return null;
        }

        return [
            'id' => $request->id,
            'name' => $request->receiver->name,
            'username' => $request->receiver->username,
            'status' => $request->status->value,
        ];
    }

    /**
     * Run a couple action, showing its refusal reason (FR-020). Returns whether it succeeded.
     */
    private function attempt(callable $action): bool
    {
        $this->resetErrorBag();

        try {
            $action();
        } catch (CoupleActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
            $this->addError('requests', $e->getMessage());

            return false;
        } finally {
            unset($this->incoming, $this->outgoing);
            $this->refreshResult();
        }

        return true;
    }

    private function findUser(string $username): ?User
    {
        return User::query()
            ->where('username', $username)
            ->whereKeyNot(Auth::id())
            ->first(['id', 'name', 'username', 'couple_id']);
    }

    /**
     * Recompute the search result's relation after something changed.
     */
    private function refreshResult(): void
    {
        unset($this->outgoing);

        if ($this->result && $user = $this->findUser($this->result['username'])) {
            $this->result = $this->describe($user);
        }
    }

    /**
     * @return array{username: string, name: string, relation: string, request_id: int|null}
     */
    private function describe(User $user): array
    {
        $me = Auth::user();
        $requestId = null;

        if ($user->isInCouple()) {
            $relation = 'unavailable';
        } elseif ($id = $me->sentCoupleRequests()->pending()->where('receiver_id', $user->id)->value('id')) {
            [$relation, $requestId] = ['pending_outgoing', $id];
        } elseif ($id = $me->receivedCoupleRequests()->pending()->where('sender_id', $user->id)->value('id')) {
            [$relation, $requestId] = ['pending_incoming', $id];
        } else {
            $relation = 'available';
        }

        return [
            'username' => $user->username,
            'name' => $user->name,
            'relation' => $relation,
            'request_id' => $requestId,
        ];
    }
}; ?>

<section class="w-full max-w-lg">
    <flux:heading size="xl" level="1">{{ __('Find your partner') }}</flux:heading>
    <flux:subheading class="mb-6">{{ __('Ask your partner for their username, then search for it here.') }}</flux:subheading>

    @if (session('status'))
        <flux:callout variant="secondary" icon="information-circle" class="mb-6" :text="session('status')" data-test="status-message" />
    @endif

    @error('requests')
        <flux:callout variant="danger" icon="exclamation-triangle" class="mb-6" :text="$message" />
    @enderror

    @if (count($this->incoming) > 0)
        <div class="mb-8 space-y-3">
            <flux:heading size="lg" level="2">{{ __('Requests for you') }}</flux:heading>

            @foreach ($this->incoming as $request)
                <flux:card class="flex items-center gap-4" wire:key="incoming-{{ $request['id'] }}" data-test="incoming-request">
                    <flux:avatar :name="$request['name']" />
                    <div class="flex-1">
                        <flux:heading>{{ $request['name'] }}</flux:heading>
                        <flux:text>{{ '@'.$request['username'] }}</flux:text>
                    </div>
                    <flux:button size="sm" variant="primary" wire:click="acceptRequest({{ $request['id'] }})" data-test="accept-request-button">
                        {{ __('Accept') }}
                    </flux:button>
                    <flux:button size="sm" variant="ghost" wire:click="declineRequest({{ $request['id'] }})" data-test="decline-request-button">
                        {{ __('Reject') }}
                    </flux:button>
                </flux:card>
            @endforeach
        </div>
    @endif

    @if ($outgoing = $this->outgoing)
        <flux:card class="mb-8 flex items-center gap-4" data-test="outgoing-request">
            <flux:avatar :name="$outgoing['name']" />
            <div class="flex-1">
                @if ($outgoing['status'] === 'pending')
                    <flux:heading>{{ __('Request sent, waiting for :name', ['name' => $outgoing['name']]) }}</flux:heading>
                @elseif ($outgoing['status'] === 'declined')
                    <flux:heading>{{ __(':name declined your request', ['name' => $outgoing['name']]) }}</flux:heading>
                @else
                    <flux:heading>{{ __(':name is no longer available', ['name' => $outgoing['name']]) }}</flux:heading>
                @endif
                <flux:text>{{ '@'.$outgoing['username'] }}</flux:text>
            </div>

            @if ($outgoing['status'] === 'pending')
                <flux:button size="sm" variant="ghost" wire:click="cancelRequest({{ $outgoing['id'] }})" data-test="cancel-request-button">
                    {{ __('Cancel') }}
                </flux:button>
            @else
                <flux:button size="sm" variant="ghost" wire:click="dismissOutgoing" data-test="dismiss-outgoing-button">
                    {{ __('Dismiss') }}
                </flux:button>
            @endif
        </flux:card>
    @endif

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
        <flux:card class="mt-6 flex flex-wrap items-center gap-4" data-test="partner-search-result">
            <flux:avatar :name="$result['name']" />
            <div class="flex-1">
                <flux:heading>{{ $result['name'] }}</flux:heading>
                <flux:text>{{ '@'.$result['username'] }}</flux:text>
            </div>

            @switch($result['relation'])
                @case('available')
                    @if ($this->outgoing && $this->outgoing['status'] === 'pending')
                        <div class="flex flex-col items-end gap-1">
                            <flux:button size="sm" variant="primary" disabled data-test="send-request-button">
                                {{ __('Send request') }}
                            </flux:button>
                            <flux:text size="sm">{{ __('Cancel your current request to send a new one') }}</flux:text>
                        </div>
                    @else
                        <flux:button size="sm" variant="primary" wire:click="sendRequest" data-test="send-request-button">
                            {{ __('Send request') }}
                        </flux:button>
                    @endif
                    @break

                @case('pending_outgoing')
                    <flux:badge>{{ __('Request pending') }}</flux:badge>
                    @break

                @case('pending_incoming')
                    <div class="flex w-full items-center justify-between gap-2">
                        <flux:text>{{ __(':name has sent you a request', ['name' => $result['name']]) }}</flux:text>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="primary" wire:click="acceptRequest({{ $result['request_id'] }})" data-test="accept-request-button">
                                {{ __('Accept') }}
                            </flux:button>
                            <flux:button size="sm" variant="ghost" wire:click="declineRequest({{ $result['request_id'] }})" data-test="decline-request-button">
                                {{ __('Reject') }}
                            </flux:button>
                        </div>
                    </div>
                    @break

                @default
                    <flux:badge color="zinc" data-test="partner-unavailable">{{ __('Not available') }}</flux:badge>
            @endswitch
        </flux:card>
    @elseif ($searched)
        <flux:text class="mt-6" data-test="partner-search-empty">
            {{ __('No user found with that username.') }}
        </flux:text>
    @endif
</section>
