<?php

use App\Actions\Reviews\CreateReview;
use App\Enums\ReviewCategory;
use App\Exceptions\ReviewActionException;
use App\Livewire\Forms\ReviewForm;
use App\Models\Item;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add review')] class extends Component {
    public ReviewForm $form;

    /**
     * When the user already reviewed the item they named, a link to it (FR-008).
     */
    public ?int $existingItemId = null;

    /**
     * Review a new item, or attach to the couple's existing item with the same category and title.
     */
    public function save(CreateReview $create): void
    {
        $this->existingItemId = null;
        $data = $this->form->validateNewItem();

        try {
            [$review, $itemExisted] = $create(
                Auth::user(),
                null,
                ReviewCategory::from($data['category']),
                $data['title'],
                $data['stars'],
                $data['body'],
            );
        } catch (ReviewActionException $e) {
            $this->addError('form', $e->getMessage());

            if ($couple = Auth::user()->fresh()?->couple) {
                $this->existingItemId = Item::forCouple($couple)
                    ->where('category', $data['category'])
                    ->where('title_key', Item::keyFor($data['title']))
                    ->value('id');
            }

            return;
        }

        Flux::toast(
            variant: 'success',
            text: $itemExisted
                ? __('Added to your existing review of :title.', ['title' => $review->item->title])
                : __('Review added.'),
        );

        $this->redirectRoute('reviews.show', ['item' => $review->item_id], navigate: true);
    }
}; ?>

<section class="w-full max-w-lg">
    <flux:heading size="xl" level="1">{{ __('Add review') }}</flux:heading>
    <flux:subheading class="mb-6">{{ __('Review something you and your partner have tried.') }}</flux:subheading>

    @error('form')
        <flux:callout variant="danger" icon="exclamation-triangle" class="mb-6" data-test="review-error">
            <flux:callout.text>{{ $message }}</flux:callout.text>
            @if ($existingItemId)
                <x-slot name="actions">
                    <flux:button size="sm" :href="route('reviews.show', $existingItemId)" wire:navigate>{{ __('Go to your review') }}</flux:button>
                </x-slot>
            @endif
        </flux:callout>
    @enderror

    <form wire:submit="save" class="space-y-6" data-test="add-review-form">
        <flux:select wire:model="form.category" :label="__('Category')" :placeholder="__('Choose a category')">
            @foreach (ReviewCategory::cases() as $category)
                <flux:select.option :value="$category->value">{{ $category->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input wire:model="form.title" :label="__('Title')" maxlength="255" :placeholder="__('e.g. Paddington 2')" />

        <x-star-rating-input wire:model="form.stars" />

        <flux:textarea wire:model="form.body" :label="__('Your review')" rows="6" maxlength="5000" />

        <div class="flex items-center gap-3">
            <flux:button type="submit" variant="primary" data-test="save-review-button">{{ __('Save review') }}</flux:button>
            <flux:button variant="ghost" :href="route('reviews.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
