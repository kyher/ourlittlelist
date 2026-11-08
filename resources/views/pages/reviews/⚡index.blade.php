<?php

use App\Actions\Reviews\DismissReviewPrompt;
use App\Enums\ReviewCategory;
use App\Enums\ReviewShow;
use App\Enums\ReviewSort;
use App\Models\Item;
use App\Models\Review;
use App\Models\ReviewPrompt;
use App\Models\User;
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
     * Who reviewed it: a ReviewShow value. Unknown values mean "all" (spec 007, FR-001).
     */
    #[Url(except: 'all')]
    public string $show = 'all';

    /**
     * Title search (spec 007, FR-005).
     */
    #[Url(as: 'q', except: '')]
    public string $search = '';

    /**
     * Order: a ReviewSort value. Unknown values mean "recent" (spec 007, FR-007).
     */
    #[Url(except: 'recent')]
    public string $sort = 'recent';

    /**
     * Reset the filters and search, but keep the sort (spec 007, FR-011).
     */
    public function clearFilters(): void
    {
        $this->reset('category', 'show', 'search');
    }

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
    public function showFilter(): ReviewShow
    {
        return ReviewShow::tryFrom($this->show) ?? ReviewShow::All;
    }

    #[Computed]
    public function sortOrder(): ReviewSort
    {
        return ReviewSort::tryFrom($this->sort) ?? ReviewSort::Recent;
    }

    /**
     * The search text actually used: trimmed and at most 100 characters (spec 007, FR-005).
     */
    #[Computed]
    public function searchTerm(): string
    {
        return mb_substr(trim($this->search), 0, 100);
    }

    #[Computed]
    public function isFiltered(): bool
    {
        return $this->filter !== null || $this->showFilter !== ReviewShow::All || $this->searchTerm !== '';
    }

    #[Computed]
    public function partner(): ?User
    {
        return Auth::user()->partner();
    }

    #[Computed]
    public function partnerName(): string
    {
        return $this->partner->name ?? __('Your partner');
    }

    /**
     * Whether the couple has any items at all, to tell "no reviews yet" from "nothing matches".
     */
    #[Computed]
    public function hasItems(): bool
    {
        return Item::forCouple(Auth::user()->couple)->exists();
    }

    /**
     * The couple's items with both partners' stars, filtered by category, who reviewed them and
     * title, in the chosen order (FR-018; spec 007, FR-009).
     *
     * @return list<array{id: int, title: string, category_label: string, my_stars: float|null, partner_stars: float|null}>
     */
    #[Computed]
    public function items(): array
    {
        $user = Auth::user();
        $partner = $this->partner;

        $query = Item::forCouple($user->couple)
            ->when($this->filter, fn ($query, ReviewCategory $category) => $query->where('category', $category))
            ->titleContains($this->searchTerm)
            ->with('reviews');

        // Without a partner (the couple is ending), partner-based options match nothing (research R7).
        match ($this->showFilter) {
            ReviewShow::All => null,
            ReviewShow::Mine => $query->reviewedBy($user),
            ReviewShow::Partner => $partner ? $query->reviewedBy($partner) : $query->whereRaw('1 = 0'),
            ReviewShow::Waiting => $partner ? $query->reviewedBy($partner)->notReviewedBy($user) : $query->whereRaw('1 = 0'),
        };

        $ratedBy = match ($this->sortOrder) {
            ReviewSort::Recent => null,
            ReviewSort::Mine => $user,
            ReviewSort::Partner => $partner,
        };

        if ($ratedBy) {
            $query->orderByRatingOf($ratedBy);
        } else {
            $query->latest('updated_at')->latest('id');
        }

        return $query
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
                    <flux:icon.chat-bubble-left-ellipsis class="text-amber-600 dark:text-amber-500" />
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

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-64">
            <flux:input wire:model.live.debounce.300ms="search" size="sm" icon="magnifying-glass" :placeholder="__('Search by name')" :aria-label="__('Search by name')" maxlength="100" clearable data-test="review-search" />
        </div>

        <div class="w-full sm:w-56">
            <flux:select wire:model.live="show" size="sm" :aria-label="__('Show')" data-test="show-filter">
                @foreach (ReviewShow::cases() as $option)
                    <flux:select.option :value="$option->value">{{ $option->label($this->partnerName) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="w-full sm:w-48">
            <flux:select wire:model.live="category" size="sm" :aria-label="__('Filter by category')" data-test="category-filter">
                <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
                @foreach (ReviewCategory::cases() as $option)
                    <flux:select.option :value="$option->value">{{ $option->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="w-full sm:w-56">
            <flux:select wire:model.live="sort" size="sm" :aria-label="__('Sort')" data-test="sort-select">
                @foreach (ReviewSort::cases() as $option)
                    <flux:select.option :value="$option->value">{{ $option->label($this->partnerName) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @if ($this->isFiltered)
            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters" data-test="clear-filters-button">
                {{ __('Clear filters') }}
            </flux:button>
        @endif
    </div>

    @if (count($this->items) === 0 && $this->hasItems)
        <div class="py-6 text-center" data-test="reviews-no-matches">
            <flux:text>{{ __('Nothing matches your search and filters.') }}</flux:text>
            <flux:button class="mt-3" size="sm" wire:click="clearFilters">{{ __('Clear filters') }}</flux:button>
        </div>
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
                                <dt class="text-sm text-zinc-600 dark:text-zinc-300">{{ $who }}</dt>
                                <dd>
                                    @if ($stars !== null)
                                        <x-star-rating :stars="$stars" />
                                    @else
                                        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-300">{{ __('Not reviewed') }}</flux:text>
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
