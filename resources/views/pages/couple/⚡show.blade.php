<?php

use App\Actions\Couples\EndCouple;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Your partner')] class extends Component {
    /**
     * Single users have no couple to show (FR-001).
     */
    public function mount(): void
    {
        if (! Auth::user()->isInCouple()) {
            $this->redirectRoute('partner.search', navigate: true);
        }
    }

    /**
     * Only the partner's name and username are exposed.
     *
     * @return array{name: string, username: string}|null
     */
    #[Computed]
    public function partner(): ?array
    {
        $partner = Auth::user()->partner();

        return $partner ? ['name' => $partner->name, 'username' => $partner->username] : null;
    }

    /**
     * End the couple for both members and delete its data (FR-015 to FR-017).
     */
    public function endCouple(EndCouple $end): void
    {
        $couple = Auth::user()->couple;

        if (! $couple) {
            $this->redirectRoute('partner.search', navigate: true);

            return;
        }

        $this->authorize('end', $couple);

        $end($couple);

        Flux::toast(text: __('Your couple has ended.'));
        $this->redirectRoute('partner.search', navigate: true);
    }
}; ?>

<section class="w-full max-w-lg">
    <flux:heading size="xl" level="1">{{ __('Your partner') }}</flux:heading>
    <flux:subheading class="mb-6">{{ __('You and your partner share this list.') }}</flux:subheading>

    @if ($partner = $this->partner)
        <flux:card class="flex items-center gap-4" data-test="couple-partner">
            <flux:avatar :name="$partner['name']" />
            <div class="flex-1">
                <flux:heading>{{ $partner['name'] }}</flux:heading>
                <flux:text>{{ '@'.$partner['username'] }}</flux:text>
            </div>
        </flux:card>

        <div class="mt-10 space-y-4">
            <div>
                <flux:heading>{{ __('End couple') }}</flux:heading>
                <flux:subheading>{{ __('Disconnect from your partner. You will both be able to find a new partner.') }}</flux:subheading>
            </div>

            <flux:modal.trigger name="confirm-end-couple">
                <flux:button variant="danger" data-test="end-couple-button">
                    {{ __('End couple') }}
                </flux:button>
            </flux:modal.trigger>
        </div>

        <flux:modal name="confirm-end-couple" focusable class="max-w-lg">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('End your couple with :name?', ['name' => $partner['name']]) }}</flux:heading>

                    <flux:subheading>
                        {{ __("This will permanently delete all of your couple's reviews, for both of you. This cannot be undone.") }}
                    </flux:subheading>
                </div>

                <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>

                    <flux:button variant="danger" wire:click="endCouple" data-test="confirm-end-couple-button">
                        {{ __('End couple') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</section>
