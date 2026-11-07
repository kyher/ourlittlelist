<?php

use App\Actions\Reviews\DismissReviewPrompt;
use App\Enums\ReviewCategory;
use App\Models\Item;
use App\Models\Review;
use App\Models\ReviewPrompt;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Reviews')] class extends Component {
    /**
     * Category filter from the query string. Unknown values are ignored (FR-018).
     */
    #[Url]
    public ?string $category = null;

    /**
     * Hide a prompt for good (FR-016). Only the user's own prompts resolve.
     */
    public function dismissPrompt(int $id, DismissReviewPrompt $dismiss): void
    {
        $prompt = Auth::user()->reviewPrompts()->findOrFail($id);
        $this->authorize('dismiss', $prompt);

        $dismiss($prompt);

        unset($this->prompts);
    }

    /**
     * Items the partner reviewed that are waiting for this user (FR-013).
     *
     * @return list<array{id: int, item_id: int, title: string, category_label: string, partner_name: string}>
     */
    #[Computed]
    public function prompts(): array
    {
        $partnerName = $this->partnerName;

        return Auth::user()->reviewPrompts()
            ->open()
            ->with('item')
            ->latest()
            ->latest('id')
            ->get()
            ->map(fn (ReviewPrompt $prompt) => [
                'id' => $prompt->id,
                'item_id' => $prompt->item_id,
                'title' => $prompt->item->title,
                'category_label' => $prompt->item->category->label(),
                'partner_name' => $partnerName,
            ])
            ->values()
            ->all();
    }

    #[Computed]
    public function filter(): ?ReviewCategory
    {
        return ReviewCategory::tryFrom((string) $this->category);
    }

    #[Computed]
    public function partnerName(): string
    {
        return Auth::user()->partner()->name ?? __('Your partner');
    }

    /**
     * The couple's items, most recent review activity first, with both partners' stars (FR-018).
     *
     * @return list<array{id: int, title: string, category_label: string, my_stars: float|null, partner_stars: float|null}>
     */
    #[Computed]
    public function items(): array
    {
        $user = Auth::user();

        return Item::forCouple($user->couple)
            ->when($this->filter, fn ($query, ReviewCategory $category) => $query->where('category', $category))
            ->with('reviews')
            ->latest('updated_at')
            ->latest('id')
            ->get()
            ->map(fn (Item $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'category_label' => $item->category->label(),
                'my_stars' => $item->reviews->firstWhere('user_id', $user->id)?->stars(),
                'partner_stars' => $item->reviews->first(fn (Review $review) => $review->user_id !== $user->id)?->stars(),
            ])
            ->values()
            ->all();
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Reviews') }}</flux:heading>
            <flux:subheading>{{ __('Everything you and your partner have reviewed.') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" :href="route('reviews.create')" wire:navigate data-test="add-review-button">
            {{ __('Add review') }}
        </flux:button>
    </div>

    @if (count($this->prompts) > 0)
        <div class="mb-8 space-y-3">
            <flux:heading size="lg" level="2">{{ __('Waiting for your review') }}</flux:heading>

            @foreach ($this->prompts as $prompt)
                <flux:card class="flex flex-wrap items-center gap-4" wire:key="prompt-{{ $prompt['id'] }}" data-test="review-prompt">
                    <flux:icon.chat-bubble-left-ellipsis class="text-amber-500" />
                    <flux:text class="min-w-0 flex-1">
                        {{ __(':partner reviewed', ['partner' => $prompt['partner_name']]) }}
                        <strong class="font-medium text-zinc-800 dark:text-white">{{ $prompt['title'] }}</strong>
                        ({{ $prompt['category_label'] }})
                    </flux:text>
                    <flux:button size="sm" variant="primary" :href="route('reviews.show', $prompt['item_id'])" wire:navigate data-test="review-prompt-link">
                        {{ __('Review it') }}
                    </flux:button>
                    <flux:button size="sm" variant="ghost" wire:click="dismissPrompt({{ $prompt['id'] }})" data-test="dismiss-prompt-button">
                        {{ __('Dismiss') }}
                    </flux:button>
                </flux:card>
            @endforeach
        </div>
    @endif

    <div class="mb-4 flex items-center gap-3">
        <flux:select wire:model.live="category" size="sm" class="max-w-48" :aria-label="__('Filter by category')" data-test="category-filter">
            <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
            @foreach (ReviewCategory::cases() as $option)
                <flux:select.option :value="$option->value">{{ $option->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @if (count($this->items) === 0 && $this->filter)
        <flux:text class="py-6 text-center" data-test="reviews-empty">
            {{ __('No :category reviews yet.', ['category' => mb_strtolower($this->filter->label())]) }}
        </flux:text>
    @elseif (count($this->items) === 0)
        <flux:card class="text-center" data-test="reviews-empty">
            <flux:heading>{{ __('No reviews yet') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Watched, read or eaten something together? Add the first review.') }}</flux:text>
            <flux:button class="mt-4" :href="route('reviews.create')" wire:navigate>{{ __('Add review') }}</flux:button>
        </flux:card>
    @else
        <div class="space-y-3">
            @foreach ($this->items as $item)
                <a href="{{ route('reviews.show', $item['id']) }}" wire:navigate wire:key="item-{{ $item['id'] }}" class="block" data-test="review-item">
                    <flux:card class="flex flex-wrap items-center gap-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                        <div class="min-w-0 flex-1">
                            <flux:heading class="truncate">{{ $item['title'] }}</flux:heading>
                            <flux:badge size="sm" class="mt-1">{{ $item['category_label'] }}</flux:badge>
                        </div>

                        <dl class="grid grid-cols-[auto_auto] items-center gap-x-3 gap-y-1">
                            @foreach ([[__('You'), $item['my_stars']], [$this->partnerName, $item['partner_stars']]] as [$who, $stars])
                                <dt class="text-sm text-zinc-600 dark:text-zinc-400">{{ $who }}</dt>
                                <dd>
                                    @if ($stars !== null)
                                        <x-star-rating :stars="$stars" />
                                    @else
                                        <flux:text size="sm" class="text-zinc-500">{{ __('Not reviewed') }}</flux:text>
                                    @endif
                                </dd>
                            @endforeach
                        </dl>
                    </flux:card>
                </a>
            @endforeach
        </div>
    @endif
</section>
