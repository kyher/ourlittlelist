---

description: "Task list for Filter, Search and Sort Reviews"
---

# Tasks: Filter, Search and Sort Reviews

**Input**: Design documents from `/specs/007-filter-search-reviews/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/ui-routes.md, quickstart.md

**Tests**: Included. Constitution Principle IV requires feature and Livewire tests, including
couple-scoping. Tests are class-based PHPUnit (`class XTest extends Tests\TestCase`,
`use RefreshDatabase`, method names `test_...`). Pest is not used. Copy the set-up and the
`review()` helper from `tests/Feature/Reviews/BrowseReviewsTest.php`: Kyle and Harry form a couple
through `Couple::factory()->withMembers($this->kyle, $this->harry)->create()`, then both are
`refresh()`ed, and reviews are made with `app(CreateReview::class)($user, null, $category, $title, $stars, $body)[0]`.

**Asserting the list**: partner-reviewed items also appear as prompt cards above the list, so
`assertSee`/`assertSeeInOrder` on titles can match the wrong section. Assert on the list itself:
`array_column($component->instance()->items, 'title')` with `assertSame` (order matters) or
`assertEqualsCanonicalizing` (order doesn't).

**Commands**: Run everything inside Docker with `docker compose exec app …`, written `dc` below.
For example: `dc php artisan test --filter=Reviews` and `dc composer ci:check`. CI runs on SQLite,
so every query MUST work on both SQLite and MySQL.

**Conventions**:
- The page is the Livewire 4 single-file component `resources/views/pages/reviews/⚡index.blade.php`
  (`pages::reviews.index`). Keep its existing style: `#[Computed]` arrays for view data, no
  models in public properties, Flux components for controls.
- Every item query MUST start with `Item::forCouple($user->couple)` (Principle III).
- Each item in `items` keeps its existing shape: `{id, title, category_label, my_stars, partner_stars}`.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: User story from spec.md (US1–US4)

---

## Phase 1: Setup

**Purpose**: The two enums that hold the valid "Show" and sort values

- [X] T001 [P] Create the string-backed enum `app/Enums/ReviewShow.php` with cases `All = 'all'`, `Mine = 'mine'`, `Partner = 'partner'`, `Waiting = 'waiting'`. Add `label(string $partnerName): string` returning `__('All reviews')`, `__('Reviewed by me')`, `__('Reviewed by :partner', ['partner' => $partnerName])` and `__('Waiting for my review')`. Copy the shape of `app/Enums/ReviewCategory.php`
- [X] T002 [P] Create the string-backed enum `app/Enums/ReviewSort.php` with cases `Recent = 'recent'`, `Mine = 'mine'`, `Partner = 'partner'`. Add `label(string $partnerName): string` returning `__('Most recent')`, `__('My highest rated')` and `__(":partner's highest rated", ['partner' => $partnerName])`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Prepare the index page so each story only adds its own control and scope

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T003 In `resources/views/pages/reviews/⚡index.blade.php`, add a `#[Computed] public function partner(): ?User` returning `Auth::user()->partner()`, and change `partnerName()` to use `$this->partner?->name ?? __('Your partner')`. Add `use App\Models\User;`
- [X] T004 In `resources/views/pages/reviews/⚡index.blade.php`, add `#[Computed] public function hasItems(): bool` returning `Item::forCouple(Auth::user()->couple)->exists()`. Restructure `items()` so the query is built in a local `$query` variable: `Item::forCouple($user->couple)`, then the existing category `when(...)`, then `->with('reviews')`, then the order (`latest('updated_at')->latest('id')`), then `get()` and the existing `map`. Behaviour stays the same; this just leaves room for the scopes added in each story (depends on T003)
- [X] T005 In `resources/views/pages/reviews/⚡index.blade.php`, replace the empty-state branches. When `! $this->hasItems`: show the existing "No reviews yet" card (`data-test="reviews-empty"`), unchanged. When `$this->hasItems` and `count($this->items) === 0`: show `<flux:text class="py-6 text-center" data-test="reviews-no-matches">{{ __('Nothing matches your search and filters.') }}</flux:text>`. Remove the old category-only "No :category reviews yet." branch (FR-012) (depends on T004)
- [X] T006 In `tests/Feature/Reviews/BrowseReviewsTest.php`, change the assertion at line 104, `->assertSee('No book reviews yet.')`, to expect `reviews-no-matches` / "Nothing matches your search and filters." instead. Run `dc php artisan test --filter=BrowseReviewsTest`; everything else MUST still pass unchanged (depends on T005)

**Checkpoint**: The page behaves as before, with the new "nothing matches" message.

---

## Phase 3: User Story 1 - Filter by who reviewed it (Priority: P1) 🎯 MVP

**Goal**: A "Show" select with All reviews / Reviewed by me / Reviewed by :partner / Waiting for my review

**Independent Test**: With items reviewed by Kyle only, Harry only, and both, each Show option as Kyle lists exactly the expected items (spec US1)

### Tests for User Story 1

- [X] T007 [P] [US1] Create `tests/Feature/Reviews/FilterReviewsTest.php`. Set up Kyle and Harry as in `BrowseReviewsTest`, and items: "Both" (Kyle 4, Harry 3), "Kyle only" (Kyle 5), "Harry only" (Harry 2), "Harry dismissed" (Harry 4; then dismiss Kyle's prompt with `app(DismissReviewPrompt::class)($this->kyle->reviewPrompts()->first())` after looking it up for that item). Tests, as Kyle via `Livewire::actingAs($this->kyle)->test('pages::reviews.index')`:
  - `test_all_shows_every_item`: no `show` set → all 4 titles
  - `test_reviewed_by_me`: `->set('show', 'mine')` → "Both", "Kyle only"
  - `test_reviewed_by_partner`: `->set('show', 'partner')` → "Both", "Harry only", "Harry dismissed"
  - `test_waiting_for_my_review_includes_dismissed_prompts`: `->set('show', 'waiting')` → "Harry only", "Harry dismissed" (FR-003)
  - `test_show_combines_with_category`: give "Harry only" category Book and the rest Film; `->set('show', 'partner')->set('category', 'book')` → only "Harry only" (FR-004)
  - `test_show_options_are_from_each_members_point_of_view`: as Harry, `->set('show', 'mine')` → "Both", "Harry only", "Harry dismissed"
  - `test_unknown_show_value_falls_back_to_all`: `Livewire::withQueryParams(['show' => 'nope'])->actingAs($this->kyle)->test(...)` → all 4 titles, `assertOk()` (FR-010)
  - `test_show_select_names_the_partner`: `assertSee('Reviewed by Harry')`
  - `test_no_matches_message_when_filter_hides_everything`: with only "Kyle only" in the couple, `->set('show', 'waiting')` → `assertSeeHtml('data-test="reviews-no-matches"')` and `assertDontSeeHtml('data-test="reviews-empty"')`

### Implementation for User Story 1

- [X] T008 [US1] In `app/Models/Item.php`, add the scopes `scopeReviewedBy(Builder $query, User $user): void` (`$query->whereHas('reviews', fn (Builder $q) => $q->where('user_id', $user->id))`) and `scopeNotReviewedBy(Builder $query, User $user): void` (same with `whereDoesntHave`). Add PHPDoc `@param Builder<Item> $query` as on `scopeForCouple` (research R1)
- [X] T009 [US1] In `resources/views/pages/reviews/⚡index.blade.php`, add `#[Url(except: 'all')] public string $show = 'all';` and `#[Computed] public function showFilter(): ReviewShow` returning `ReviewShow::tryFrom($this->show) ?? ReviewShow::All`. In `items()`, after the category filter, apply with a `match ($this->showFilter)`: `All` → nothing; `Mine` → `reviewedBy($user)`; `Partner` → `reviewedBy($partner)`; `Waiting` → `reviewedBy($partner)->notReviewedBy($user)`. When `$this->partner` is `null`, `Partner` and `Waiting` MUST apply `whereRaw('1 = 0')` instead (research R7). Add `use App\Enums\ReviewShow;` (depends on T001, T008)
- [X] T010 [US1] In `resources/views/pages/reviews/⚡index.blade.php`, add a `flux:select` to the controls row before the category select: `wire:model.live="show"`, `size="sm"`, `class="max-w-56"`, `:aria-label="__('Show')"`, `data-test="show-filter"`, with one `flux:select.option` per `ReviewShow::cases()` using `:value="$option->value"` and `$option->label($this->partnerName)`. Make the row `flex flex-wrap items-center gap-3` so it wraps on phones (depends on T009)
- [X] T011 [US1] Run `dc php artisan test --filter=FilterReviewsTest` and `--filter=BrowseReviewsTest`; all MUST pass

**Checkpoint**: Kyle can list his reviews, Harry's, or what's waiting for him.

---

## Phase 4: User Story 2 - Search by name (Priority: P1)

**Goal**: A search box that narrows the list to items whose title contains the text

**Independent Test**: Typing part of a title in any case lists only the matching items; another couple's same-titled item never appears (spec US2)

### Tests for User Story 2

- [X] T012 [P] [US2] Create `tests/Feature/Reviews/SearchReviewsTest.php`, set up as in `BrowseReviewsTest`, with Kyle's items "The Grand Budapest Hotel" (Film), "Paddington 2" (Film) and "100%_Juice" (Food), and an item "Juice bar" (Restaurant). Tests as Kyle:
  - `test_search_matches_part_of_the_title`: `->set('search', 'budapest')` → only "The Grand Budapest Hotel"
  - `test_search_ignores_case_and_surrounding_spaces`: `'BUDAPEST'` and `'  budapest  '` → only "The Grand Budapest Hotel" (FR-005)
  - `test_percent_and_underscore_are_matched_literally`: `'%_'` → only "100%_Juice"; `'_'` → only "100%_Juice"; `'%'` → only "100%_Juice"
  - `test_exclamation_mark_is_matched_literally`: add "Wow!" and search `'!'` → only "Wow!" (the escape character itself, research R3)
  - `test_clearing_search_restores_the_list`: set `'budapest'`, then `''` → all items
  - `test_spaces_only_search_is_ignored`: `'   '` → all items
  - `test_search_is_cut_to_100_characters`: `str_repeat('a', 150)` → `assertOk()`, and the no-matches message is shown
  - `test_search_combines_with_category`: `->set('search', 'juice')->set('category', 'food')` → only "100%_Juice"
  - `test_search_never_shows_another_couples_items`: create a second couple (`User::factory()` ×2, `Couple::factory()->withMembers(...)`) with an item "Paddington 2" reviewed by one of them; as Kyle, `'paddington'` → exactly one item, whose `id` is Kyle's couple's item (FR-013, SC-006)
  - `test_search_is_kept_in_the_query_string`: `Livewire::withQueryParams(['q' => 'budapest'])` → only "The Grand Budapest Hotel" (FR-010)
  - `test_no_matches_message_for_unmatched_search`: `'zzz'` → `assertSeeHtml('data-test="reviews-no-matches"')`

### Implementation for User Story 2

- [X] T013 [US2] In `app/Models/Item.php`, add `scopeTitleContains(Builder $query, string $search): void`. Compute `$term = mb_strtolower(trim($search))`; if `$term === ''` return without changes. Otherwise escape with `str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term)` and apply `$query->whereRaw("title_key like ? escape '!'", ['%'.$escaped.'%'])`. Do NOT use a backslash escape: it needs different quoting in MySQL and SQLite (research R3)
- [X] T014 [US2] In `resources/views/pages/reviews/⚡index.blade.php`, add `#[Url(as: 'q', except: '')] public string $search = '';`. In `items()`, after the Show filter, apply `->titleContains(mb_substr(trim($this->search), 0, 100))` ("Search text MUST be at most 100 characters", FR-005) (depends on T013)
- [X] T015 [US2] In `resources/views/pages/reviews/⚡index.blade.php`, add as the first control in the row: `<flux:input wire:model.live.debounce.300ms="search" size="sm" icon="magnifying-glass" :placeholder="__('Search by name')" :aria-label="__('Search by name')" class="max-w-64" maxlength="100" clearable data-test="review-search" />` (FR-006) (depends on T014)
- [X] T016 [US2] Run `dc php artisan test --filter=SearchReviewsTest` and `--filter=BrowseReviewsTest`; all MUST pass

**Checkpoint**: Kyle can find an item by name.

---

## Phase 5: User Story 3 - Sort by highest rated (Priority: P2)

**Goal**: A sort select with Most recent / My highest rated / :partner's highest rated

**Independent Test**: With items rated differently by each member, each sort gives the expected order, with unrated items last and ties by most recent activity (spec US3)

### Tests for User Story 3

- [X] T017 [P] [US3] Create `tests/Feature/Reviews/SortReviewsTest.php`, set up as in `BrowseReviewsTest`. Create items one minute apart with `$this->travel(1)->minutes()` between them, in this order: "A" (Kyle 5, Harry 2), "B" (Kyle 3.5), "C" (Harry 4.5), "D" (Kyle 4, Harry 3). Most recent activity order is therefore D, C, B, A. Tests as Kyle, asserting with `assertSame` on titles:
  - `test_default_order_is_most_recent`: no sort → `['D', 'C', 'B', 'A']`
  - `test_my_highest_rated`: `->set('sort', 'mine')` → `['A', 'D', 'B', 'C']` (C unrated by Kyle, last)
  - `test_partners_highest_rated`: `->set('sort', 'partner')` → `['C', 'D', 'A', 'B']` (B unrated by Harry, last)
  - `test_ties_are_ordered_by_most_recent_activity`: add "E" (Kyle 5) a minute later → `'mine'` gives `['E', 'A', 'D', 'B', 'C']`
  - `test_unrated_items_keep_most_recent_order`: with `'mine'`, add "F" (Harry 1) a minute later → Kyle-unrated tail is `['F', 'C']`
  - `test_sort_is_from_each_members_point_of_view`: as Harry, `'mine'` → `['C', 'D', 'A', 'B']`
  - `test_unknown_sort_falls_back_to_most_recent`: `Livewire::withQueryParams(['sort' => 'nope'])` → `['D', 'C', 'B', 'A']`
  - `test_sort_select_names_the_partner`: `assertSee("Harry's highest rated")` (escaped apostrophe: use `assertSee("Harry's highest rated", false)` if needed)

### Implementation for User Story 3

- [X] T018 [US3] In `app/Models/Item.php`, add `scopeOrderByRatingOf(Builder $query, User $user): void`. Build `$rating = Review::query()->select('rating')->whereColumn('reviews.item_id', 'items.id')->where('reviews.user_id', $user->id)`, then `$query->addSelect(['sort_rating' => $rating])->orderByRaw('sort_rating is null')->orderByDesc('sort_rating')->latest('updated_at')->latest('id')`. Items without a rating from `$user` MUST come last, and ties MUST be ordered by most recent activity (FR-008, research R4). `addSelect` keeps `items.*` because no columns were selected before
- [X] T019 [US3] In `resources/views/pages/reviews/⚡index.blade.php`, add `#[Url(except: 'recent')] public string $sort = 'recent';` and `#[Computed] public function sortOrder(): ReviewSort` returning `ReviewSort::tryFrom($this->sort) ?? ReviewSort::Recent`. In `items()`, replace the fixed `latest('updated_at')->latest('id')` with a `match ($this->sortOrder)`: `Recent` → `latest('updated_at')->latest('id')`; `Mine` → `orderByRatingOf($user)`; `Partner` → `orderByRatingOf($partner)`, or the `Recent` order when `$this->partner` is `null` (research R7). Add `use App\Enums\ReviewSort;` (depends on T002, T018)
- [X] T020 [US3] In `resources/views/pages/reviews/⚡index.blade.php`, add a `flux:select` as the last control in the row: `wire:model.live="sort"`, `size="sm"`, `class="max-w-56"`, `:aria-label="__('Sort')"`, `data-test="sort-select"`, one option per `ReviewSort::cases()` with `$option->label($this->partnerName)` (depends on T019)
- [X] T021 [US3] Run `dc php artisan test --filter=SortReviewsTest` and `--filter=BrowseReviewsTest`; all MUST pass

**Checkpoint**: Kyle can see his or Harry's favourites first.

---

## Phase 6: User Story 4 - Combine filters, search and sort (Priority: P2)

**Goal**: All controls apply together, survive reloads, and can be cleared in one action

**Independent Test**: Set category, Show, search and sort together; only items matching all of them are listed in order; reopening with the same query string gives the same result; Clear filters resets all but sort (spec US4)

### Tests for User Story 4

- [X] T022 [P] [US4] Add to `tests/Feature/Reviews/FilterReviewsTest.php` (create a separate set-up in each test so it does not depend on the US1 fixture):
  - `test_all_controls_combine`: items "Dishoom" (Restaurant, Harry 4.5), "Hoppers" (Restaurant, Harry 3), "Dishoom Express" (Restaurant, Kyle 5), "Dishoom the Film" (Film, Harry 5). `Livewire::withQueryParams(['category' => 'restaurant', 'show' => 'partner', 'q' => 'dish', 'sort' => 'partner'])` → `['Dishoom']`; then `->set('search', '')` (the property behind `q`) → `['Dishoom', 'Hoppers']` (FR-009, FR-010)
  - `test_unknown_values_fall_back_to_defaults`: `withQueryParams(['show' => 'nope', 'sort' => 'nope', 'category' => 'nope'])` → `assertOk()` and every item listed in most recent order
  - `test_clear_filters_resets_category_show_and_search_but_keeps_sort`: set `category`, `show`, `search` and `sort` to non-defaults, call `clearFilters()` → `assertSet('category', null)`, `assertSet('show', 'all')`, `assertSet('search', '')`, `assertSet('sort', 'partner')` (FR-011)
  - `test_clear_filters_button_only_shows_when_filtered`: no filters → `assertDontSeeHtml('data-test="clear-filters-button"')`; after `->set('search', 'x')` → `assertSeeHtml(...)`; after only `->set('sort', 'mine')` → not shown
  - `test_prompts_are_not_affected_by_filters`: Harry reviews "Dishoom"; as Kyle `->set('search', 'zzz')->set('show', 'mine')` → `assertSeeHtml('data-test="review-prompt"')` still shows the Dishoom prompt (FR-014)
  - `test_page_still_requires_a_couple`: a single user `get(route('reviews.index', ['q' => 'x']))` → redirect to `partner.search` (FR-013)

### Implementation for User Story 4

- [X] T023 [US4] In `resources/views/pages/reviews/⚡index.blade.php`, add `public function clearFilters(): void` that calls `$this->reset('category', 'show', 'search')` (not `sort`; research R6), and `#[Computed] public function isFiltered(): bool` returning `$this->filter !== null || $this->showFilter !== ReviewShow::All || trim($this->search) !== ''`
- [X] T024 [US4] In `resources/views/pages/reviews/⚡index.blade.php`, add at the end of the controls row, `@if ($this->isFiltered)`: `<flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters" data-test="clear-filters-button">{{ __('Clear filters') }}</flux:button>`. Also add the same button (without the icon is fine) under the "Nothing matches your search and filters." message (depends on T023)
- [X] T025 [US4] Run `dc php artisan test --filter=Reviews`; all MUST pass

**Checkpoint**: All four controls work together and links are shareable.

---

## Phase 7: Polish & Cross-Cutting Concerns

- [X] T026 Check the controls row on a narrow screen (about 375px): controls wrap with no horizontal scroll, and the list below is unchanged. Adjust the `max-w-*` classes in `resources/views/pages/reviews/⚡index.blade.php` if needed
- [X] T027 [P] If `README.md` has a feature list mentioning reviews, add one line for filtering, search and sorting
- [X] T028 Walk through `specs/007-filter-search-reviews/quickstart.md` manual scenarios 1–7 in the running app
- [X] T029 Run `dc composer ci:check` (Pint, static analysis and tests on SQLite); fix anything it reports

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies. T001 and T002 run in parallel.
- **Foundational (Phase 2)**: Depends on nothing in Phase 1; blocks every story because all stories edit `items()` in the same page.
- **US1 (Phase 3)**: Depends on Phase 2 and T001.
- **US2 (Phase 4)**: Depends on Phase 2 only.
- **US3 (Phase 5)**: Depends on Phase 2 and T002.
- **US4 (Phase 6)**: Depends on US1, US2 and US3 (it combines them and uses `showFilter`).
- **Polish (Phase 7)**: Depends on all stories.

### Within Each User Story

- Write the test file first and see it fail, then add the `Item` scope, then the page property and query, then the control.
- Page tasks in different stories all edit `⚡index.blade.php` and `items()`, so do the stories one after another, not in parallel.

### Parallel Opportunities

- T001 and T002 (different enum files).
- Each story's test file task (T007, T012, T017) can be written in parallel with that story's `Item` scope task (T008, T013, T018): different files.
- T027 in Polish is independent of the rest.

---

## Parallel Example: User Story 2

```text
Task: "T012 [US2] Create tests/Feature/Reviews/SearchReviewsTest.php"
Task: "T013 [US2] Add scopeTitleContains to app/Models/Item.php"
```

---

## Implementation Strategy

### MVP First

1. Phase 1 and Phase 2.
2. Phase 3 (US1, the Show filter). This alone closes issue #6's "filter by just one member or type".
3. Stop and validate with `FilterReviewsTest` and a manual check.

### Incremental Delivery

1. US1 → Show filter.
2. US2 → search.
3. US3 → rating sorts.
4. US4 → combinations, clear filters, link behaviour.
5. Polish and `ci:check`.

---

## Notes

- No migrations. If a task seems to need one, stop: the design says the existing indexes are enough.
- Never put models in public Livewire properties. `partner()` is a `#[Computed]`, not a property.
- Blade must escape item titles (`{{ }}`). Never use `{!! !!}`.
- 006 task T047 (crash when the couple ends while the page is open) is out of scope here; it is in the stash from `add-reviews`.
- Commit after each phase checkpoint.
