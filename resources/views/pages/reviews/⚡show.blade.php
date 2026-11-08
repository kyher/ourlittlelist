<?php

use App\Actions\Reviews\CreateReview;
use App\Actions\Reviews\DeleteReview;
use App\Actions\Reviews\UpdateReview;
use App\Exceptions\ReviewActionException;
use App\Livewire\Forms\ReviewForm;
use App\Models\Item;
use App\Models\Review;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Review')] class extends Component {
    public int $itemId;

    public ReviewForm $form;

    public bool $editing = false;

    /**
     * Only items in the user's own couple resolve; anything else is a 404 (FR-020).
     */
    public function mount(int $item): void
    {
        $model = Item::forCouple(Auth::user()->couple)->findOrFail($item);
        $this->authorize('view', $model);

        $this->itemId = $model->id;
    }

    /**
     * Add the user's review of this item, answering any prompt for it (FR-015).
     */
    public function addReview(CreateReview $create): void
    {
        $item = Item::forCouple(Auth::user()->couple)->find($this->itemId);

        if (! $item) {
            $this->itemGone();

            return;
        }

        $this->authorize('review', $item);

        $data = $this->form->validateReview();

        try {
            $create(Auth::user(), $item, null, null, $data['stars'], $data['body']);
        } catch (ReviewActionException $e) {
            if ($e->getMessage() === __('This item no longer exists.')) {
                $this->itemGone();

                return;
            }

            $this->addError('form', $e->getMessage());

            return;
        }

        $this->form->reset();
        $this->refreshReviews();
        Flux::toast(variant: 'success', text: __('Review added.'));
    }

    public function startEditing(): void
    {
        $review = $this->ownReview();
        $this->authorize('update', $review);

        $this->resetErrorBag();
        $this->form->stars = $review->starsValue();
        $this->form->body = $review->body;
        $this->editing = true;
    }

    public function cancelEditing(): void
    {
        $this->resetErrorBag();
        $this->form->reset();
        $this->editing = false;
    }

    /**
     * Save new stars and text for the user's own review (FR-009).
     */
    public function updateReview(UpdateReview $update): void
    {
        $review = $this->ownReview();
        $this->authorize('update', $review);

        $data = $this->form->validateReview();

        $update($review, $data['stars'], $data['body']);

        $this->form->reset();
        $this->editing = false;
        $this->refreshReviews();
        Flux::toast(variant: 'success', text: __('Review updated.'));
    }

    /**
     * Permanently remove the user's own review, and the item if nobody else reviewed it (FR-010, FR-011).
     */
    public function deleteReview(DeleteReview $delete): void
    {
        $review = $this->ownReview();
        $this->authorize('delete', $review);

        $itemRemoved = $delete($review);

        Flux::toast(text: __('Review removed.'));

        if ($itemRemoved) {
            $this->redirectRoute('reviews.index', navigate: true);

            return;
        }

        $this->editing = false;
        $this->form->reset();
        $this->refreshReviews();
        $this->modal('confirm-delete-review')->close();
    }

    /**
     * @return array{id: int, title: string, category_label: string}
     */
    #[Computed]
    public function item(): array
    {
        $item = $this->model();

        return ['id' => $item->id, 'title' => $item->title, 'category_label' => $item->category->label()];
    }

    /**
     * @return array{id: int, stars: float, body: string, created_at: string, edited: bool}|null
     */
    #[Computed]
    public function myReview(): ?array
    {
        $review = $this->model()->reviewBy(Auth::user());

        return $review ? $this->present($review) : null;
    }

    #[Computed]
    public function partnerName(): string
    {
        return Auth::user()->partner()->name ?? __('Your partner');
    }

    /**
     * The partner's review is always visible, even before the user writes their own (FR-014).
     *
     * @return array{id: int, stars: float, body: string, created_at: string, edited: bool}|null
     */
    #[Computed]
    public function partnerReview(): ?array
    {
        $review = $this->model()->reviews()->where('user_id', '!=', Auth::id())->first();

        return $review ? $this->present($review) : null;
    }

    /**
     * The item was removed (its last review went) while this page was open.
     */
    private function itemGone(): void
    {
        Flux::toast(variant: 'danger', text: __('This item no longer exists.'));
        $this->redirectRoute('reviews.index', navigate: true);
    }

    private function model(): Item
    {
        return Item::forCouple(Auth::user()->couple)->findOrFail($this->itemId);
    }

    /**
     * The current user's review of this item. Only ever their own, so a partner's review can
     * never be edited or removed from here (FR-012).
     */
    private function ownReview(): Review
    {
        return Auth::user()->reviews()
            ->whereHas('item', fn ($query) => $query->forCouple(Auth::user()->couple))
            ->where('item_id', $this->itemId)
            ->firstOrFail();
    }

    private function refreshReviews(): void
    {
        unset($this->myReview, $this->partnerReview);
    }

    /**
     * @return array{id: int, stars: float, body: string, created_at: string, edited: bool}
     */
    private function present(Review $review): array
    {
        return [
            'id' => $review->id,
            'stars' => $review->stars(),
            'body' => $review->body,
            'created_at' => $review->created_at?->toFormattedDateString() ?? '',
            'edited' => $review->isEdited(),
        ];
    }
}; ?>

<section class="w-full">
    <flux:link :href="route('reviews.index')" wire:navigate class="text-sm">{{ __('← All reviews') }}</flux:link>

    <div class="mt-2 mb-6 flex flex-wrap items-center gap-3">
        <flux:heading size="xl" level="1">{{ $this->item['title'] }}</flux:heading>
        <flux:badge size="sm">{{ $this->item['category_label'] }}</flux:badge>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <flux:card class="space-y-3" data-test="my-review">
            <flux:heading size="lg" level="2">{{ __('You') }}</flux:heading>

            @if (($review = $this->myReview) && $editing)
                <form wire:submit="updateReview" class="space-y-5" data-test="edit-review-form">
                    <x-star-rating-input wire:model="form.stars" />

                    <flux:textarea wire:model="form.body" :label="__('Your review')" rows="5" maxlength="5000" />

                    <div class="flex items-center gap-2">
                        <flux:button type="submit" variant="primary" data-test="update-review-button">{{ __('Save changes') }}</flux:button>
                        <flux:button variant="ghost" wire:click="cancelEditing">{{ __('Cancel') }}</flux:button>
                    </div>
                </form>
            @elseif ($review)
                <x-star-rating :stars="$review['stars']" />
                <flux:text class="whitespace-pre-line">{{ $review['body'] }}</flux:text>
                <flux:text size="sm" class="text-zinc-500 dark:text-zinc-300">
                    {{ $review['created_at'] }}
                    @if ($review['edited'])
                        · <span data-test="review-edited">{{ __('Edited') }}</span>
                    @endif
                </flux:text>

                <div class="flex items-center gap-2 pt-2">
                    <flux:button size="sm" icon="pencil-square" wire:click="startEditing" data-test="edit-review-button">{{ __('Edit') }}</flux:button>

                    <flux:modal.trigger name="confirm-delete-review">
                        <flux:button size="sm" variant="ghost" icon="trash" data-test="delete-review-button">{{ __('Remove') }}</flux:button>
                    </flux:modal.trigger>
                </div>
            @else
                @error('form')
                    <flux:callout variant="danger" icon="exclamation-triangle" :text="$message" />
                @enderror

                <form wire:submit="addReview" class="space-y-5" data-test="add-review-form">
                    <flux:text>{{ __('Add your review of :title (:category).', ['title' => $this->item['title'], 'category' => $this->item['category_label']]) }}</flux:text>

                    <x-star-rating-input wire:model="form.stars" />

                    <flux:textarea wire:model="form.body" :label="__('Your review')" rows="5" maxlength="5000" />

                    <flux:button type="submit" variant="primary" data-test="save-review-button">{{ __('Save review') }}</flux:button>
                </form>
            @endif
        </flux:card>

        <flux:card class="space-y-3" data-test="partner-review">
            <flux:heading size="lg" level="2">{{ $this->partnerName }}</flux:heading>

            @if ($review = $this->partnerReview)
                <x-star-rating :stars="$review['stars']" />
                <flux:text class="whitespace-pre-line">{{ $review['body'] }}</flux:text>
                <flux:text size="sm" class="text-zinc-500 dark:text-zinc-300">
                    {{ $review['created_at'] }}
                    @if ($review['edited'])
                        · {{ __('Edited') }}
                    @endif
                </flux:text>
            @else
                <flux:text class="text-zinc-500 dark:text-zinc-300">{{ __(":name hasn't reviewed this yet.", ['name' => $this->partnerName]) }}</flux:text>
            @endif
        </flux:card>
    </div>

    @if ($this->myReview)
        <flux:modal name="confirm-delete-review" focusable class="max-w-lg">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Remove your review of :title?', ['title' => $this->item['title']]) }}</flux:heading>
                    <flux:subheading>{{ __('This cannot be undone.') }}</flux:subheading>
                </div>

                <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>

                    <flux:button variant="danger" wire:click="deleteReview" data-test="confirm-delete-review-button">
                        {{ __('Remove') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</section>
