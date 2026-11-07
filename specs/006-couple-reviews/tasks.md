---

description: "Task list for Couple Reviews"
---

# Tasks: Couple Reviews

**Input**: Design documents from `/specs/006-couple-reviews/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/ui-routes.md, quickstart.md

**Tests**: Included. Constitution Principle IV requires feature and Livewire tests for the happy
path, validation errors and authorization. Tests are class-based PHPUnit
(`class XTest extends Tests\TestCase`, `use RefreshDatabase`, method names `test_...`). Pest is
not used. Copy the style of `tests/Feature/Couples/EndCoupleTest.php`: Kyle and Harry form a couple
through `Couple::factory()->withMembers($kyle, $harry)->create()`, then `$kyle->refresh()`.

**Organization**: Tasks are grouped by user story, so each story can be built and tested on its own.

**Commands**: Run everything inside Docker with `docker compose exec app …`, written `dc` below.
For example: `dc php artisan test --filter=Reviews` and `dc composer ci:check`. CI runs on SQLite,
so every behaviour MUST work on SQLite.

**Conventions**:
- Livewire 4 single-file pages live at `resources/views/pages/<area>/⚡<name>.blade.php` and are
  referenced as `pages::<area>.<name>`. Copy the structure of `resources/views/pages/couple/⚡show.blade.php`:
  - anonymous `new #[Title(...)] class extends Component`
  - `#[Computed]` arrays for view data, so models are never exposed
  - `Flux::toast(...)` for success messages
  - `flux:modal` for confirmations
- Actions copy the style of `app/Actions/Couples/SendCoupleRequest.php`: an invokable class, a
  `DB::transaction`, and a `lockForUpdate` re-check.
- Pages catch `ReviewActionException` and show `$e->getMessage()` with `$this->addError('form', …)`.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: User story from spec.md (US1–US4)

---

## Phase 1: Setup

**Purpose**: Shared types and middleware that have no dependencies

- [X] T001 [P] Create the string-backed enum `app/Enums/ReviewCategory.php` with the cases `Film = 'film'`, `TvShow = 'tv_show'`, `Book = 'book'`, `Food = 'food'` and `Restaurant = 'restaurant'`. Add `label(): string`, which returns `__('Film')`, `__('TV show')`, `__('Book')`, `__('Food')` and `__('Restaurant')`
- [X] T002 [P] Create `app/Exceptions/ReviewActionException.php`, a `final class ReviewActionException extends \RuntimeException`. Its message is the plain-language, user-facing reason (FR-021)
- [X] T003 [P] Create `app/Http/Middleware/EnsureUserIsInCouple.php`. If `! $request->user()->isInCouple()`, it returns `redirect()->route('partner.search')->with('status', __('Connect with your partner to start reviewing.'))`. Otherwise it calls `$next($request)`. Copy the shape of `app/Http/Middleware/EnsureUserHasUsername.php`
- [X] T004 Register the alias `'in-couple' => EnsureUserIsInCouple::class` next to `has-username` in `bootstrap/app.php` (depends on T003)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: The schema, models, factories, policies, shared form and star components that every story uses

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T005 Create the migration `database/migrations/2026_11_07_000002_create_items_table.php` with:
  - `id()`
  - `foreignId('couple_id')->constrained('couples')->cascadeOnDelete()`
  - `string('category', 20)`
  - `string('title', 255)`
  - `string('title_key', 255)`
  - `foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()`
  - `timestamps()`
  - `unique(['couple_id', 'category', 'title_key'])`
  - `index(['couple_id', 'updated_at'])`

  `down()` drops the table. Data model: "unique (`couple_id`, `category`, `title_key`) (FR-004, SC-006)"
- [X] T006 Create the migration `database/migrations/2026_11_07_000003_create_reviews_table.php` with:
  - `id()`
  - `foreignId('item_id')->constrained('items')->cascadeOnDelete()`
  - `foreignId('user_id')->constrained('users')->cascadeOnDelete()`
  - `unsignedTinyInteger('rating')`, with the comment "half-stars, 2–10"
  - `text('body')`
  - `timestamp('edited_at')->nullable()`
  - `timestamps()`
  - `unique(['item_id', 'user_id'])`

  (depends on T005)
- [X] T007 Create the migration `database/migrations/2026_11_07_000004_create_review_prompts_table.php` with:
  - `id()`
  - `foreignId('item_id')->constrained('items')->cascadeOnDelete()`
  - `foreignId('user_id')->constrained('users')->cascadeOnDelete()`
  - `timestamp('dismissed_at')->nullable()`
  - `timestamps()`
  - `unique(['item_id', 'user_id'])`

  (depends on T005)
- [X] T008 [P] Create `app/Models/Item.php`:
  - `HasFactory`, with `$fillable = ['couple_id', 'category', 'title', 'created_by']`. `couple_id` is set by the action and never by user input.
  - `casts()` returns `['category' => ReviewCategory::class]`.
  - A `title` Attribute mutator stores `trim($value)` in `title` and `mb_strtolower(trim($value))` in `title_key`.
  - Relations: `couple()` BelongsTo, `reviews()` HasMany `Review`, `prompts()` HasMany `ReviewPrompt`.
  - `reviewBy(User $user): ?Review`.
  - `scopeForCouple(Builder $q, Couple $couple)`, which is `whereBelongsTo($couple)`.
  - PHPDoc `@property` lines, as in `app/Models/Couple.php`.
- [X] T009 [P] Create `app/Models/Review.php`:
  - `HasFactory`, with `$fillable = ['user_id', 'rating', 'body']` and `$touches = ['item']`.
  - `casts()` returns `['rating' => 'integer', 'edited_at' => 'datetime']`.
  - `item()` BelongsTo, and `author()` BelongsTo `User` via `user_id`.
  - `stars(): float`, which returns `$this->rating / 2`, and `static starsToRating(string|float $stars): int`, which returns `(int) round(((float) $stars) * 2)`.
  - `isEdited(): bool`, which returns `$this->edited_at !== null` (research R3, R12).
- [X] T010 [P] Create `app/Models/ReviewPrompt.php`:
  - `HasFactory`, with `$fillable = ['user_id']`.
  - `casts()` returns `['dismissed_at' => 'datetime']`.
  - `item()` BelongsTo, and `recipient()` BelongsTo `User` via `user_id`.
  - `scopeOpen(Builder $q)`, which is `whereNull('dismissed_at')`.
- [X] T011 Add relations to the existing models:
  - In `app/Models/Couple.php`, add `items(): HasMany`.
  - In `app/Models/User.php`, add `reviews(): HasMany` and `reviewPrompts(): HasMany`.
  - In `User`, add `openReviewPromptCount(): int`, which returns `$this->reviewPrompts()->open()->count()`.

  Update the PHPDoc `@property-read` lines in both models (depends on T008–T010)
- [X] T012 [P] Create `database/factories/ItemFactory.php`:
  - The default creates a `Couple`, `category` is a random `ReviewCategory`, and `title` is `fake()->unique()->words(3, true)`.
  - Add a `forCouple(Couple $couple)` state.
- [X] T013 [P] Create `database/factories/ReviewFactory.php`:
  - The default has `item_id` from `Item::factory()`, a random `rating` from 2 to 10, and `fake()->paragraph()` for `body`.
  - Add a `by(User $user)` state, which sets `user_id`.
  - Add a `stars(float $stars)` state, which sets `rating` to `Review::starsToRating($stars)`.
- [X] T014 [P] Create `database/factories/ReviewPromptFactory.php`:
  - The default has `item_id` from `Item::factory()`.
  - Add a `for(User $user)` state, which sets `user_id`. If that name clashes with the base factory's `for()`, name it `recipient(User $user)` instead.
  - Add a `dismissed()` state, which sets `dismissed_at` to now.
- [X] T015 [P] Create `app/Policies/ItemPolicy.php`. `view(User $user, Item $item)` and `review(User $user, Item $item)` both return `$user->couple_id !== null && $user->couple_id === $item->couple_id`
- [X] T016 [P] Create `app/Policies/ReviewPolicy.php`. `update` and `delete` both return `$review->user_id === $user->id && $user->couple_id === $review->item->couple_id`. A partner is therefore refused (FR-012)
- [X] T017 [P] Create `app/Policies/ReviewPromptPolicy.php`. `dismiss(User $user, ReviewPrompt $prompt)` returns `$prompt->user_id === $user->id`. Policies are found automatically by naming convention. Check that `CouplePolicy` is not registered by hand in `app/Providers/AppServiceProvider.php`; if it is, register these three the same way
- [X] T018 [P] Create the Livewire Form object `app/Livewire/Forms/ReviewForm.php` (`extends Livewire\Form`):
  - Properties: `?string $category = null`, `string $title = ''`, `?string $stars = null`, `string $body = ''`.
  - `rulesForNewItem()`:
    - `category`: `['required', Rule::enum(ReviewCategory::class)]`
    - `title`: `['required', 'string', 'max:255']`
    - plus `rulesForReview()`
  - `rulesForReview()`:
    - `stars`: `['required', Rule::in(['1','1.5','2','2.5','3','3.5','4','4.5','5'])]`
    - `body`: `['required', 'string', 'max:5000']`
  - Trim `title` and `body` before validating, so whitespace-only text fails `required`.
  - Messages:
    - "Choose a category."
    - "Enter a title."
    - "The title must be 255 characters or fewer."
    - "Choose a rating from 1 to 5 stars."
    - "Write your review."
    - "Your review must be 5,000 characters or fewer."
  - `validateNewItem(): array` and `validateReview(): array` call `$this->validate($rules, $messages)` (data-model.md Validation table).
- [X] T019 [P] Create the anonymous Blade component `resources/views/components/star-rating.blade.php`:
  - Takes `@props(['stars'])` and renders five Heroicon `star` icons (`flux:icon.star variant="solid"`). Each star is full, half or empty. A half star is a solid star clipped to 50% width over an outline star.
  - The wrapper has `role="img"` and `aria-label="{{ __(':stars out of 5 stars', ['stars' => $stars]) }}"`.
  - Show the number next to the stars, e.g. "4.5" (research R11).
- [X] T020 [P] Create the anonymous Blade component `resources/views/components/star-rating-input.blade.php`:
  - It is a `<fieldset>` of nine radio inputs with values `1, 1.5 … 5`, which forwards `$attributes->whereStartsWith('wire:model')` to each radio.
  - The radios are visually hidden (`sr-only`), and each `<label>` draws half a star.
  - Use Alpine (`x-data` with a hover value) to highlight every half star up to the one hovered or selected.
  - It must work with the keyboard through native arrow-key radio navigation.
  - It has the legend "Rating". Each radio's label for screen readers reads ":n stars" (research R11). If this proves fiddly, use the fallback from R11 and contracts: a `flux:select` with nine options

**Checkpoint**: `dc php artisan migrate:fresh` succeeds. Factories create items, reviews and prompts in tinker

---

## Phase 3: User Story 1 - Review something new (Priority: P1) 🎯 MVP

**Goal**: A member of a couple can review a new item (category, title, half-star rating and text). It is saved under the couple with them as the author and appears in the couple's list.

**Independent Test**: With Kyle and Harry as a couple, sign in as Kyle, add a Film review of "Paddington 2" with 4.5 stars, and see it on `/reviews` and its item page. Submitting without stars or text is refused. A single user is redirected.

### Tests for User Story 1

> Write these tests first and make sure they fail before you implement.

- [X] T021 [P] [US1] Create `tests/Feature/Reviews/CreateReviewTest.php`. It has a couple of Kyle and Harry, and a single user Sam. Include these tests:
  - `test_member_can_review_a_new_item`: use `Livewire::actingAs($kyle)->test('pages::reviews.create')` to set `form.category=film`, `form.title='Paddington 2'`, `form.stars='4.5'` and `form.body`, then `call('save')`. It should redirect to `reviews.show`. Check that one `Item` exists with Kyle's `couple_id`, `category` Film and `title_key` 'paddington 2', and one `Review` with `user_id` Kyle and `rating` 9.
  - `test_rating_and_body_are_required`: a missing stars value and a body of `'   '` each give errors on `form.stars` and `form.body`, and nothing is saved.
  - `test_title_and_category_are_required`.
  - `test_only_half_star_steps_are_accepted`: use a data provider with `'0'`, `'0.5'`, `'3.3'`, `'6'` and `'abc'`. Each one fails on `form.stars`.
  - `test_body_over_5000_characters_is_refused` and `test_title_over_255_characters_is_refused`.
  - `test_same_category_and_title_ignoring_case_and_spaces_attaches_to_existing_item`: Harry saves Film `'  PADDINGTON 2 '` after Kyle saved "Paddington 2". There is still one `Item`, it has two reviews, and Harry is redirected to that item.
  - `test_same_title_in_another_category_is_a_separate_item`.
  - `test_second_review_of_same_item_by_same_user_is_refused`: the error message contains "You've already reviewed" and there is still one review.
  - `test_single_user_is_redirected_from_review_pages`: GET `reviews.index` and `reviews.create` as Sam both redirect to `partner.search`.
- [X] T022 [P] [US1] Update the existing tests that expect a user in a couple to land on `couple.show`, so they expect `reviews.index` instead. Leave every other assertion, including direct `couple.show` page tests, as it is. The two places are:
  - `tests/Feature/Couples/CouplePagesTest.php:53`: visiting `partner.search` while in a couple.
  - `tests/Feature/Couples/RespondToCoupleRequestTest.php:185`: the redirect after accepting a request.

### Implementation for User Story 1

- [X] T023 [US1] Create `app/Actions/Reviews/CreateReview.php`. It is invokable:
  `__invoke(User $author, ?Item $item, ?ReviewCategory $category, ?string $title, string $stars, string $body): array{0: Review, 1: bool}`, which returns `[$review, $itemExisted]`. Inside `DB::transaction`:
  1. Lock and refresh the author with `User::whereKey($author->id)->lockForUpdate()->first()`. If they're not in a couple, throw "You're no longer in a couple."
  2. **When `$item` is given**: re-read it with `Item::forCouple($author->couple)->whereKey($item->id)->lockForUpdate()->first()`. If that returns null, throw "This item no longer exists." Set `$itemExisted` to true.
  3. **When `$item` is not given**: use `Item::createOrFirst(['couple_id' => …, 'category' => $category, 'title_key' => mb_strtolower(trim($title))], ['title' => trim($title), 'created_by' => $author->id])`, then lock it. `$itemExisted` is `! $item->wasRecentlyCreated`. The unique index makes this safe in a race (research R4).
  4. If `$item->reviewBy($author)` exists, throw `__("You've already reviewed :title. Edit your review instead.", ['title' => $item->title])`.
  5. Create the review with `rating => Review::starsToRating($stars)` and `body => trim($body)`. Catch `UniqueConstraintViolationException` and rethrow it as the same "already reviewed" message (research R5).
  6. Return the result.

  Leave a `// US2: prompts` marker where T031 will add prompt handling.
- [X] T024 [US1] Add the routes in `routes/web.php`. Inside the existing `auth, verified, has-username` group, add `Route::middleware('in-couple')->group(...)` containing:
  - `Route::livewire('reviews', 'pages::reviews.index')->name('reviews.index')`
  - `Route::livewire('reviews/new', 'pages::reviews.create')->name('reviews.create')`
  - `Route::livewire('reviews/{item}', 'pages::reviews.show')->name('reviews.show')`

  Declare `reviews/new` before `reviews/{item}`. Add `->whereNumber('item')` to the show route.
- [X] T025 [US1] Create `resources/views/pages/reviews/⚡create.blade.php` (`#[Title('Add review')]`):
  - `public ReviewForm $form`.
  - `save(CreateReview $create)` calls `$this->form->validateNewItem()`, then the action with `item: null`.
  - On success, show `Flux::toast(...)` with "Added to your existing review of :title." when `$itemExisted` is true and "Review added." otherwise, then `redirectRoute('reviews.show', $item, navigate: true)`. Catch `ReviewActionException` and show `addError('form', $e->getMessage())`. For the "already reviewed" case, also render a link to the existing item.
  - **View**:
    - `flux:select wire:model="form.category"` with the `ReviewCategory::cases()` labels.
    - `flux:input wire:model="form.title"` with `maxlength="255"`.
    - `<x-star-rating-input wire:model="form.stars" />` with `flux:error name="form.stars"`.
    - `flux:textarea wire:model="form.body"` with `maxlength="5000"`.
    - A save button.
    - The wrapper has `data-test="add-review-form"`.
- [X] T026 [US1] Create `resources/views/pages/reviews/⚡show.blade.php` (`#[Title('Review')]`), first version:
  - `mount(Item $item)` re-resolves the item with `Item::forCouple(Auth::user()->couple)->findOrFail($item->id)`, which gives a 404 outside the couple (FR-020, research R9), then calls `$this->authorize('view', $item)`. Store `public int $itemId`.
  - `#[Computed]` values: `item` is `{id, title, category_label}`, and `myReview` is `{id, stars, body, created_at, edited}` or null.
  - **View**: a heading with the title and a category `flux:badge`, then the card `data-test="my-review"` showing `<x-star-rating>`, the body (escaped `{{ }}` with `whitespace-pre-line`) and the date.

  US2 and US3 extend this page.
- [X] T027 [US1] Create `resources/views/pages/reviews/⚡index.blade.php` (`#[Title('Reviews')]`), first version:
  - The `#[Computed]` value `items` loads `Item::forCouple($couple)->with('reviews')->latest('updated_at')->get()`, mapped to `{id, title, category_label, my_stars, partner_stars}`.
  - **View**: an "Add review" `flux:button` linking to `reviews.create`.
  - A list of `data-test="review-item"` rows. Each links to `reviews.show` and shows the title, a category badge and the user's stars as `<x-star-rating>`.
  - An empty state, "No reviews yet", that links to create.

  US2 and US4 extend this page.
- [X] T028 [US1] Update the navigation and landing page:
  - **`resources/views/layouts/app/header.blade.php`**: inside the `isInCouple()` branch of both the navbar and the mobile sidebar, add a **Reviews** item (`icon="star"`, `:href="route('reviews.index')"`, `:current="request()->routeIs('reviews.*')"`) *before* "Your partner".
  - **`resources/views/pages/partner/⚡search.blade.php`**: change both `redirectRoute('couple.show', …)` calls (in `mount()` and in the accept handler) to `redirectRoute('reviews.index', navigate: true)`.
  - **Flash message**: show the `session('status')` flash on the search page if it isn't already shown, so the `in-couple` redirect message is visible.

**Checkpoint**: `dc php artisan test --filter=CreateReviewTest` and the updated couple and search tests pass. US1 is usable on its own

---

## Phase 4: User Story 2 - Partner is prompted to review the same thing (Priority: P1)

**Goal**: The first review of an item prompts the partner on `/reviews` and in the nav badge. Answering opens the item with the category and title fixed. The partner's full review is always visible, and both reviews show side by side. Prompts can be dismissed for good.

**Independent Test**: After Kyle reviews "Paddington 2", Harry sees the badge and the prompt, opens it, sees Kyle's full review, and adds his own stars and text. The prompt is then gone, and both reviews show.

### Tests for User Story 2

- [X] T029 [P] [US2] Create `tests/Feature/Reviews/ReviewPromptTest.php` with these tests:
  - `test_first_review_prompts_partner_only`: after Kyle's review, Harry has one open `ReviewPrompt` for the item and Kyle has none.
  - `test_prompt_is_listed_on_index_with_partner_name`: Harry's `reviews.index` shows `data-test="review-prompt"` with "Kyle", "Paddington 2" and "Film".
  - `test_nav_badge_shows_open_prompt_count`: a GET to `reviews.index` as Harry shows `data-test="reviews-nav-badge"` with "1". Kyle doesn't see the badge.
  - `test_answering_prompt_from_item_page_removes_it`: as Harry, test `pages::reviews.show` with `['item' => $item]`, set `form.stars` and `form.body`, then call `addReview`. Harry then has a review on the same item, his prompt row is deleted, and the item has two reviews.
  - `test_partner_review_is_visible_before_writing_own`: Harry's show page shows Kyle's body text and `data-test="partner-review"` before Harry has reviewed (FR-014).
  - `test_dismissed_prompt_is_not_shown_again_but_item_can_still_be_reviewed`: Harry calls `dismissPrompt`. `dismissed_at` is set, the index no longer shows the prompt, and `addReview` still works afterwards.
  - `test_attaching_via_create_page_clears_own_prompt`: Harry adds Film "paddington 2" on the create page, and his prompt is deleted.
  - `test_add_review_on_item_page_validates_stars_and_body`.
  - `test_answering_after_item_deleted_shows_message`: delete the item, then Harry's `addReview` gives the error "This item no longer exists." and redirects to `reviews.index`.

### Implementation for User Story 2

- [X] T030 [US2] Create `app/Actions/Reviews/DismissReviewPrompt.php`. It is invokable as `__invoke(ReviewPrompt $prompt): void` and sets `$prompt->forceFill(['dismissed_at' => now()])->save()`
- [X] T031 [US2] Extend `app/Actions/Reviews/CreateReview.php` at the `// US2: prompts` marker, inside the same transaction:
  - If the item was created now, find the partner with `$author->partner()` and, if there is one, create `$item->prompts()->create(['user_id' => $partner->id])`.
  - Always delete the author's own prompt for this item with `$item->prompts()->where('user_id', $author->id)->delete()`.
  - No prompt is created when the item already existed (FR-013, FR-017, research R6).
- [X] T032 [US2] Extend `resources/views/pages/reviews/⚡show.blade.php`:
  - Add `public ReviewForm $form`, plus the `#[Computed]` values `partnerName` and `partnerReview` (`{name, stars, body, created_at, edited}` or null), which are always shown (FR-014).
  - **`addReview(CreateReview $create)`**:
    1. Call `$this->authorize('review', $item)` and `$this->form->validateReview()`.
    2. Call the action with the item and null category and title.
    3. On success, show the toast "Review added.", call `$this->form->reset()`, and unset the computed values.
    4. On `ReviewActionException`: if the message is "This item no longer exists.", flash a toast and redirect to `reviews.index`. Otherwise show it with `addError`.
  - **View**: a two-column grid (`md:grid-cols-2`) with "You" (`data-test="my-review"`) and the partner's name (`data-test="partner-review"`). The partner column shows their review, or ":name hasn't reviewed this yet".
  - When `myReview` is null, the "You" column shows the add form (`data-test="add-review-form"`). It shows the title and category as fixed text plus `<x-star-rating-input wire:model="form.stars">` and `flux:textarea wire:model="form.body"`.
- [X] T033 [US2] Extend `resources/views/pages/reviews/⚡index.blade.php`:
  - **`#[Computed] prompts`**: `Auth::user()->reviewPrompts()->open()->with('item')->latest()->get()`, mapped to `{id, item_id, title, category_label, partner_name}`.
  - **`dismissPrompt(int $id, DismissReviewPrompt $dismiss)`**:
    1. Look up `Auth::user()->reviewPrompts()->findOrFail($id)`, which gives a 404 for anyone else's prompt.
    2. Call `$this->authorize('dismiss', $prompt)`, then `$dismiss($prompt)`.
    3. Unset `prompts`, then `$this->dispatch('review-prompts-changed')`.
  - **View**: above the list, show a `flux:callout` or card for each prompt (`data-test="review-prompt"`) reading ":partner reviewed :title (:category)". It has a "Review it" link to `reviews.show` (`data-test="review-prompt-link"`) and a "Dismiss" button (`data-test="dismiss-prompt-button"`).
- [X] T034 [US2] In `resources/views/layouts/app/header.blade.php`, give the **Reviews** item in both the navbar and the sidebar a `badge` showing `auth()->user()->openReviewPromptCount()` when it is above 0. Use the `badge` attribute on `flux:navbar.item` or `flux:sidebar.item`, or put a `flux:badge` inside the item, with `data-test="reviews-nav-badge"`. Compute the count once per render with `@php($promptCount = …)`

**Checkpoint**: `dc php artisan test --filter=ReviewPromptTest` passes. US1 and US2 together give the full core loop

---

## Phase 5: User Story 3 - Edit or remove my own review (Priority: P2)

**Goal**: Authors can edit their stars and text, which marks the review as "Edited". They can also remove their review after confirming, and removing the last review removes the item. Partners can't do either.

**Independent Test**: Kyle edits his review to 5 stars and sees "Edited", then removes a review and confirms. Harry's attempts to edit or remove Kyle's review are refused.

### Tests for User Story 3

- [X] T035 [P] [US3] Create `tests/Feature/Reviews/EditDeleteReviewTest.php` with these tests:
  - `test_author_can_edit_stars_and_body`: run `startEditing`, set `form.stars='5'` and the body, then `updateReview`. The rating is 10, the body is updated, `edited_at` is not null, and the page shows `data-test="review-edited"`.
  - `test_edit_requires_stars_and_body`.
  - `test_edit_does_not_create_a_prompt`: Harry dismissed or answered the prompt, and after Kyle's edit Harry has no open prompts.
  - `test_removing_review_keeps_item_when_partner_has_reviewed`: both partners reviewed, Kyle calls `deleteReview`, and the item plus Harry's review remain. No prompt is created for Kyle.
  - `test_removing_last_review_deletes_item_and_prompts`: Kyle is the only reviewer and Harry has an open prompt. After `deleteReview`, the item is missing, the prompt is missing, and Kyle is redirected to `reviews.index`.
  - `test_readding_after_removal_does_not_prompt_again_when_partner_reviewed`.

### Implementation for User Story 3

- [X] T036 [P] [US3] Create `app/Actions/Reviews/UpdateReview.php`. It is invokable as `__invoke(Review $review, string $stars, string $body): Review` and, in a transaction, updates `rating` with `Review::starsToRating($stars)`, `body` with `trim($body)`, and `edited_at` with `now()`. `$touches` bumps the item. No prompt logic (FR-009, FR-017)
- [X] T037 [P] [US3] Create `app/Actions/Reviews/DeleteReview.php`. It is invokable as `__invoke(Review $review): bool` and returns whether the item was deleted. In `DB::transaction`:
  1. Lock the item with `Item::whereKey($review->item_id)->lockForUpdate()->first()`.
  2. Delete the review.
  3. If `$item->reviews()->doesntExist()`, delete the item, which cascades its prompts, and return true (FR-011, research R7).
- [X] T038 [US3] Extend `resources/views/pages/reviews/⚡show.blade.php`:
  - **State**: add `public bool $editing = false`.
  - **`startEditing()`**: fill `form.stars` with the review's `stars()` as a string (e.g. `'4.5'`, or `'4'` for whole numbers, matching the `Rule::in` values) and `form.body`, then set `editing` to true.
  - **`updateReview(UpdateReview $update)`**:
    1. Load `$review = Auth::user()->reviews()->where('item_id', $this->itemId)->firstOrFail()`.
    2. Call `$this->authorize('update', $review)`, then `$this->form->validateReview()`, then the action.
    3. Show the toast "Review updated.", set `editing` to false and reset the form.
  - **`cancelEditing()`**.
  - **`deleteReview(DeleteReview $delete)`**:
    1. Load the review the same way and call `$this->authorize('delete', $review)`.
    2. Run the action and show the toast "Review removed."
    3. If the item was deleted, `redirectRoute('reviews.index', navigate: true)`. Otherwise unset the computed values and close the modal.
  - **View**: inside `data-test="my-review"`, add "Edit" (`data-test="edit-review-button"`) and "Remove" (`data-test="delete-review-button"`, `flux:modal.trigger name="confirm-delete-review"`) buttons. When editing, show the edit form instead of the review.
  - When the review has been edited, show an "Edited" `flux:text` (`data-test="review-edited"`).
  - The partner column never shows these controls.
  - **Modal**: `flux:modal name="confirm-delete-review"` with the heading "Remove your review of :title?", the body "This cannot be undone." and the buttons Cancel and **Remove** (`variant="danger"`, `wire:click="deleteReview"`, `data-test="confirm-delete-review-button"`). Copy the pattern from `resources/views/pages/couple/⚡show.blade.php`.

**Checkpoint**: `dc php artisan test --filter=EditDeleteReviewTest` passes

---

## Phase 6: User Story 4 - Browse the couple's reviews (Priority: P2)

**Goal**: The index shows every item with each partner's rating, most recent activity first, and can be filtered by category.

**Independent Test**: With items in several categories, either partner sees both ratings on the index. Filtering by Restaurant shows only restaurants.

### Tests for User Story 4

- [X] T039 [P] [US4] Create `tests/Feature/Reviews/BrowseReviewsTest.php` with these tests:
  - `test_items_are_ordered_by_most_recent_activity`: create three items, then edit a review on the oldest one. It should now be listed first. Use `$this->travel()` to control timestamps.
  - `test_index_shows_both_partners_ratings_or_not_reviewed`.
  - `test_category_filter_shows_only_that_category`: set `category=restaurant`, through `->set('category', 'restaurant')` and through the query string `?category=restaurant`.
  - `test_invalid_category_filter_is_ignored`: `?category=nope` shows all items.
  - `test_item_page_shows_both_reviews_side_by_side`: check the author names, stars `aria-label`, bodies and dates.

### Implementation for User Story 4

- [X] T040 [US4] Extend `resources/views/pages/reviews/⚡index.blade.php`:
  - **Filter state**: add `#[Url] public ?string $category = null`. In the `items` computed value, apply `->where('category', $cat)` only when `ReviewCategory::tryFrom((string) $this->category)` is not null, so an invalid value is ignored.
  - **Partner rating**: add `partner_stars` from the partner's review. Find the partner's review in the eager-loaded `reviews` by `user_id !== auth id`.
  - **Filter control**: add a `flux:select wire:model.live="category"` (`data-test="category-filter"`) with an "All" option (empty value) and every `ReviewCategory`.
  - **Rows**: each row shows "You" and the partner's name, each with `<x-star-rating>` or "Not reviewed".

**Checkpoint**: `dc php artisan test --filter=BrowseReviewsTest` passes

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Privacy guarantees across all stories, the couple-end cascade, and quality gates

- [X] T041 [P] Create `tests/Feature/Reviews/ReviewAuthorizationTest.php`. It has the couples Kyle and Harry, and Sam and Alex. Include these tests:
  - `test_other_couples_item_page_is_404`: Sam GETs `reviews.show` for Kyle's item and gets a 404.
  - `test_partner_cannot_update_or_delete_others_review`: check `Gate::forUser($harry)->denies('update', $kylesReview)` and `denies('delete', …)`. On Harry's show page, there is no `edit-review-button` in the partner column, and `updateReview` and `deleteReview` only ever act on Harry's own review, so Kyle's review is unchanged.
  - `test_prompt_cannot_be_dismissed_by_someone_else`: Kyle calls `dismissPrompt($harrysPromptId)`, gets a 404, and `dismissed_at` stays null.
  - `test_other_couples_items_never_listed`: Sam's index doesn't contain Kyle's titles.
  - `test_ending_couple_deletes_all_items_reviews_and_prompts`: seed items, reviews and prompts, run `app(EndCouple::class)($couple)`, and check that the counts for that couple are 0 while Sam and Alex's data stays untouched (FR-022, SC-007).
  - `test_action_fails_when_couple_ended_mid_form`: delete the couple, then call `CreateReview` directly with Kyle. It throws "You're no longer in a couple."
- [X] T042 Run `dc vendor/bin/pint` and fix the style issues
- [X] T043 Run `dc vendor/bin/phpstan analyse` (`composer types:check`) and fix the issues. Add generics PHPDoc such as `@return HasMany<Review, $this>` and `@use HasFactory<…>` on the new models
- [X] T044 Run `dc php artisan migrate:fresh` on MySQL (done as a rollback of the 3 new migrations plus migrate, to keep the dev data), then `dc composer ci:check` (SQLite, full suite) and make sure everything is green
- [X] T045 Walk through the manual steps in `specs/006-couple-reviews/quickstart.md` in the browser. Check the star input with the keyboard and in dark mode
- [X] T046 [P] Update `README.md` (the features section added in feature 005) with a short "Reviews" entry

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: has no dependencies. T004 depends on T003.
- **Foundational (Phase 2)**: depends on Setup and blocks every story.
  - Order the migrations T005 → T006 and T007.
  - T011 comes after T008–T010.
- **US1 (Phase 3)**: depends on Foundational. It creates the routes and the three pages.
- **US2 (Phase 4)**: depends on US1, because it extends `CreateReview`, `⚡show` and `⚡index`.
- **US3 (Phase 5)**: depends on US1 for `⚡show`. It can run alongside US2 if the `⚡show` edits are merged carefully. Doing it after US2 is simpler.
- **US4 (Phase 6)**: depends on US1 for `⚡index`. It is independent of US3.
- **Polish (Phase 7)**: depends on every story.

### Within Each User Story

- Write the tests first, and make sure they fail.
- Then build the action, then the page or view.
- Story-level checkpoint tests pass before moving on.

### Parallel Opportunities

- **Setup**: T001, T002 and T003.
- **Foundational**: T008–T010 (models), T012–T014 (factories), T015–T017 (policies), T018 (form) and T019–T020 (components) all touch different files. They can run in parallel once the migrations exist.
- **US1**: T021 and T022 (tests).
- **US3**: T035, T036 and T037.
- **US3 and US4**: these touch different pages, `⚡show` and `⚡index`, so they can run in parallel after US2.
- **Polish**: T041 and T046.

---

## Parallel Example: Foundational

```text
Task: "T008 Create app/Models/Item.php"
Task: "T009 Create app/Models/Review.php"
Task: "T010 Create app/Models/ReviewPrompt.php"
Task: "T015 Create app/Policies/ItemPolicy.php"
Task: "T016 Create app/Policies/ReviewPolicy.php"
Task: "T017 Create app/Policies/ReviewPromptPolicy.php"
Task: "T018 Create app/Livewire/Forms/ReviewForm.php"
Task: "T019 Create resources/views/components/star-rating.blade.php"
Task: "T020 Create resources/views/components/star-rating-input.blade.php"
```

## Parallel Example: User Story 3

```text
Task: "T035 EditDeleteReviewTest in tests/Feature/Reviews/EditDeleteReviewTest.php"
Task: "T036 UpdateReview action in app/Actions/Reviews/UpdateReview.php"
Task: "T037 DeleteReview action in app/Actions/Reviews/DeleteReview.php"
```

---

## Implementation Strategy

### MVP First

1. Complete Phase 1 (Setup) and Phase 2 (Foundational).
2. Complete Phase 3 (US1). Members can now review new items and see them listed.
3. **Stop and validate** by running the US1 tests and quickstart steps 1–3.

### Incremental Delivery

1. Add US1, the MVP: solo reviewing.
2. Add US2: prompts and comparison. This is the core couple loop, and a good point to open the PR if you want to split the work.
3. Add US3: edit and remove.
4. Add US4: filter and both ratings on the index.
5. Finish with Polish: the authorization sweep, Pint, PHPStan and `ci:check`, then open the PR into `dev`.

## Notes

- The constitution's Principle III is a hard gate. Look up every item with `Item::forCouple(...)`. Never use `Item::find($id)` without scoping.
- Never put models in public Livewire properties. Keep integer ids and `#[Computed]` arrays, as the couple pages do.
- Blade must escape review text (`{{ }}`). Never use `{!! !!}`.
- Commit after each phase checkpoint.
