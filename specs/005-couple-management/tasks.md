---

description: "Task list for Couple Management"
---

# Tasks: Couple Management

**Input**: Design documents from `/specs/005-couple-management/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/ui-routes.md, quickstart.md

**Tests**: Included. Constitution Principle IV requires feature and Livewire tests for every
user-facing feature, covering the happy path, validation errors and authorization. Tests are
class-based PHPUnit (`class XTest extends Tests\TestCase`, `use RefreshDatabase`, method names
`test_...`). Pest is not used. Follow the style of `tests/Feature/PartnerSearchTest.php`.

**Organization**: Tasks are grouped by user story so each story can be built and tested on its own.

**Commands**: Run everything inside Docker with `docker compose exec app …`, written `dc` below.
For example: `dc php artisan test --filter=Couple` and `dc composer ci:check`. CI has no Docker
services and runs on SQLite, so every behaviour MUST work on SQLite. Livewire 4 single-file pages
live at `resources/views/pages/<area>/⚡<name>.blade.php` and are referenced as
`pages::<area>.<name>`. Copy the structure of `resources/views/pages/partner/⚡search.blade.php`.
For the confirmation modal, copy the pattern in `resources/views/pages/settings/⚡delete-user-modal.blade.php`.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: User story from spec.md (US1, US2, US3)

---

## Phase 1: Setup

**Purpose**: Shared types that have no dependencies

- [X] T001 [P] Create the string-backed enum `app/Enums/CoupleRequestStatus.php` with the cases `Pending = 'pending'`, `Accepted = 'accepted'`, `Declined = 'declined'`, `Cancelled = 'cancelled'` and `Closed = 'closed'`. Add `isFinal(): bool`, which returns `$this !== self::Pending` (data-model.md: "Only `pending` can change. Every other status is final.")
- [X] T002 [P] Create `app/Exceptions/CoupleActionException.php`, a `final class CoupleActionException extends \RuntimeException`. Its message is the plain-language, user-facing reason (FR-020, research R7). The Livewire pages show `$e->getMessage()` to the user

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Schema, models, factories and policies that every story depends on

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T003 Create the migration `database/migrations/2026_11_07_000000_create_couples_table.php`. In `up()`:
  - `Schema::create('couples', fn (Blueprint $t) => [$t->id(), $t->timestamps()])`, written as normal statements.
  - Then `Schema::table('users', fn ($t) => $t->foreignId('couple_id')->nullable()->after('username')->constrained('couples')->nullOnDelete())`.

  In `down()`, drop the foreign key and the column with `$table->dropConstrainedForeignId('couple_id')`, then `Schema::dropIfExists('couples')`. Data model: "`couple_id` | bigint, nullable, FK → `couples.id`, `nullOnDelete`, indexed | `null` means the user is single"
- [X] T004 Create the migration `database/migrations/2026_11_07_000001_create_couple_requests_table.php` with:
  - `id()`
  - `foreignId('sender_id')->constrained('users')->cascadeOnDelete()`
  - `foreignId('receiver_id')->constrained('users')->cascadeOnDelete()`
  - `string('status', 16)->default('pending')`
  - `timestamp('responded_at')->nullable()`
  - `timestamps()`
  - `index(['receiver_id', 'status'])`
  - `index(['sender_id', 'status'])`

  `down()` drops the table. This depends on T003 only for migration order
- [X] T005 [P] Create the model `app/Models/Couple.php` with `HasFactory`:
  - `members(): HasMany` returns `$this->hasMany(User::class)`, using `couple_id`.
  - `partnerOf(User $user): User` returns the member whose id is not `$user->id`.
  - Add `@property` docblocks matching `User.php`.
- [X] T006 [P] Create the model `app/Models/CoupleRequest.php` with `HasFactory`:
  - Add `#[Fillable(['sender_id', 'receiver_id', 'status', 'responded_at'])]`.
  - Add `casts()`, which returns `['status' => CoupleRequestStatus::class, 'responded_at' => 'datetime']`.
  - Add `sender(): BelongsTo` and `receiver(): BelongsTo`, both to `User`.
  - Add `scopePending(Builder $q)`, which filters on `status = CoupleRequestStatus::Pending`.
  - Add `@property` docblocks.
- [X] T007 Update `app/Models/User.php`:
  - Add `@property int|null $couple_id` and `@property-read Couple|null $couple`. Do NOT add `couple_id` to `#[Fillable]`, because only the couple actions set it.
  - Add `couple(): BelongsTo`, which returns `$this->belongsTo(Couple::class)`.
  - Add `isInCouple(): bool`, which returns `$this->couple_id !== null`.
  - Add `partner(): ?User`, which returns `null` if the user is single and otherwise `User::where('couple_id', $this->couple_id)->whereKeyNot($this->id)->first()`.
  - Add `sentCoupleRequests(): HasMany`, which uses `sender_id`, and `receivedCoupleRequests(): HasMany`, which uses `receiver_id`.

  This depends on T005 and T006
- [X] T008 [P] Create `database/factories/CoupleFactory.php`, where `definition()` returns `[]`. Add a static-style helper state or a test helper method `withMembers(User $a, User $b): static`. Its `afterCreating` hook sets `couple_id` on both users through `forceFill(['couple_id' => $couple->id])->save()`. Also create `database/factories/CoupleRequestFactory.php`:
  - `definition()` returns `sender_id => User::factory()`, `receiver_id => User::factory()` and `status => CoupleRequestStatus::Pending`.
  - Add the states `declined()`, `cancelled()`, `accepted()` and `closed()`, each of which also sets `responded_at => now()`.
- [X] T009 [P] Create `app/Policies/CoupleRequestPolicy.php`:
  - `accept(User $user, CoupleRequest $r): bool` and `decline(...)` return `$user->id === $r->receiver_id`.
  - `cancel(...)` returns `$user->id === $r->sender_id`.

  Laravel auto-discovers the policy by name. Research R5, FR-010
- [X] T010 [P] Create `app/Policies/CouplePolicy.php` with `end(User $user, Couple $couple): bool`, which returns `$user->couple_id === $couple->id` (research R5, FR-019)
- [X] T011 Run `dc php artisan migrate:fresh` and `dc php artisan test`. Every existing test, including `tests/Feature/PartnerSearchTest.php`, MUST still pass

**Checkpoint**: The schema and models exist, and the policies are registered. The stories can now proceed.

---

## Phase 3: User Story 1 - Send a couple request to a found user (Priority: P1) 🎯 MVP

**Goal**: A single user can find another single user by username and send them a request, see
that it is pending, and cancel it. Users in a couple are kept out of the search page.

**Independent Test**: With `kyle` and `harry` both single, Kyle searches `harry` and sends a
request. The request is shown as pending. Kyle cancels it, and the request is gone.

### Tests for User Story 1

> Write these tests FIRST and make sure they FAIL before you implement anything.

- [X] T012 [P] [US1] Create `tests/Feature/Couples/SendCoupleRequestTest.php`, testing the `App\Actions\Couples\SendCoupleRequest` action directly. Clear `RateLimiter::clear('couple-request:'.$id)` in `setUp`. Cover these cases:
  - **Success**: a single user sends to a single user. One `couple_requests` row is created with `status = pending`.
  - **Each refusal**: it throws `CoupleActionException` with the exact message from data-model.md, and no row is created:
    - Sending to yourself: "You can't send a request to yourself."
    - The sender is in a couple: "You're already in a couple."
    - The receiver is in a couple: ":name is not available."
    - The sender already has a pending request to anyone: "You already have a pending request. Cancel it first."
    - The receiver already has a pending request to the sender: ":name has already sent you a request. Accept it below." The row count is unchanged.
  - **Rate limit**: the 11th successful send within an hour throws "You've sent too many requests. Try again in :minutes minutes." Use cancel between sends so FR-005 doesn't block the test.
  - **After a decline**: if the sender's previous request was `declined`, they can send again (US2-4).
- [X] T013 [P] [US1] Create `tests/Feature/Couples/CancelCoupleRequestTest.php`. Cover these cases:
  - The sender cancels a pending request. The status becomes `cancelled` and `responded_at` is set.
  - Cancelling a request that is not pending throws "This request is no longer available."
  - Through Livewire, a non-sender calling `cancelRequest($id)` on `pages::partner.search` gets `assertForbidden()`.
- [X] T014 [P] [US1] Add the US1 page tests in `tests/Feature/Couples/CouplePagesTest.php`:
  - **Coupled user**: a user in a couple who opens `GET /partner/search` is redirected to `route('couple.show')`. Register a placeholder `couple.show` route in T021 if US3 isn't built yet.
  - **Result relations**, via `Livewire::test('pages::partner.search')->set('query', 'harry')->call('search')`:
    - A single target shows `send-request-button`.
    - A target in a couple shows `partner-unavailable` with "Not available". The page must NOT contain the target partner's name or username.
    - When the searcher already has a pending request to the target, the card shows "Request pending".
  - **Sending**: `call('sendRequest')` creates the request, and the page shows `outgoing-request` with "waiting for Harry". When a refusal happens, its message is shown.
  - **No emails**: the page never contains any user's email.

### Implementation for User Story 1

- [X] T015 [US1] Create `app/Actions/Couples/SendCoupleRequest.php`, an invokable action `__invoke(User $sender, User $receiver): CoupleRequest`. It runs inside `DB::transaction`:
  1. Lock both users with `User::whereKey([$a, $b])->orderBy('id')->lockForUpdate()->get()`, then refresh both.
  2. Check, in this order, throwing `CoupleActionException` with these messages (data-model.md):
     - The sender is the receiver: "You can't send a request to yourself."
     - `$sender->isInCouple()`: "You're already in a couple."
     - `$receiver->isInCouple()`: ":name is not available." with the receiver's name.
     - There is a pending request from the receiver to the sender: ":name has already sent you a request. Accept it below."
     - The sender has any pending outgoing request: "You already have a pending request. Cancel it first."
     - `RateLimiter::tooManyAttempts('couple-request:'.$sender->id, 10)`: "You've sent too many requests. Try again in :minutes minutes.", where `:minutes` is `(int) ceil(RateLimiter::availableIn(...) / 60)`.
  3. Create the pending request.
  4. Call `RateLimiter::hit('couple-request:'.$sender->id, 3600)`, so only successful sends count.

  Research R3 and R6, FR-004 to FR-007, FR-018
- [X] T016 [P] [US1] Create `app/Actions/Couples/CancelCoupleRequest.php`, `__invoke(CoupleRequest $request): void`. Inside `DB::transaction`:
  1. Re-read the request with `lockForUpdate()`.
  2. If it isn't `Pending`, throw "This request is no longer available."
  3. Otherwise set `status = Cancelled` and `responded_at = now()`.

  FR-008
- [X] T017 [US1] Update `resources/views/pages/partner/⚡search.blade.php` (PHP part):
  - **`mount()`**: if `Auth::user()->isInCouple()`, call `$this->redirectRoute('couple.show', navigate: true)` (FR-001, research R9).
  - **`relation` on the result**: extend `$result` to `{username, name, relation}`, where `relation` is one of `available|pending_outgoing|pending_incoming|unavailable` (contracts/ui-routes.md "Search result `relation`"). Compute it in `search()` after the existing lookup, which must stay unchanged. For `unavailable`, never load or store the partner.
  - **`outgoing` computed property**: returns the sender's latest `CoupleRequest`, but only if its status is in `[Pending, Declined, Closed]` and it hasn't been dismissed. Map it to `{id, name, username, status}`. Store dismissed ids in a public `array $dismissed` property, or simply hide them for the session with `session()->push('couple.dismissed', $id)`. Pick the session approach.
  - **`sendRequest()`**: re-resolve the receiver from `$this->result['username']` on the server. Call `SendCoupleRequest`. Catch `CoupleActionException` and add the error on `query` with its message. On success, re-run `search()` so the relation becomes `pending_outgoing`.
  - **`cancelRequest(int $id)`**: load the request with `CoupleRequest::findOrFail($id)`, call `$this->authorize('cancel', $request)`, call `CancelCoupleRequest`, and catch the exception and show its message.
  - **`dismissOutgoing()`**: hides the current declined or closed notice.
- [X] T018 [US1] Update `resources/views/pages/partner/⚡search.blade.php` (Blade part):
  - **Outgoing panel** (`data-test="outgoing-request"`), shown above the search form:
    - `pending`: "Request sent, waiting for :name" and a **Cancel** button (`data-test="cancel-request-button"`, `wire:click="cancelRequest(id)"`).
    - `declined`: ":name declined your request" and a **Dismiss** button.
    - `closed`: ":name is no longer available" and a **Dismiss** button.
  - **Result card** content, based on `relation`:
    - `available`: a **Send request** button (`data-test="send-request-button"`, `wire:click="sendRequest"`). It is disabled, with the hint "Cancel your current request to send a new one", when there is a pending outgoing request to someone else.
    - `pending_outgoing`: "Request pending".
    - `unavailable`: "Not available" (`data-test="partner-unavailable"`).
    - `pending_incoming`: leave a placeholder that is filled in US2.

  Output only the name and username, never the email
- [X] T019 [US1] Run `dc php artisan test --filter='SendCoupleRequest|CancelCoupleRequest|CouplePages|PartnerSearch'`. All of them MUST pass

**Checkpoint**: Single users can send, view and cancel requests. Users in a couple are kept off the search page.

---

## Phase 4: User Story 2 - Accept or reject a couple request (Priority: P1)

**Goal**: The receiver sees incoming requests and can accept or reject them. Accepting creates a
couple of exactly those two users and closes every other pending request either of them has.

**Independent Test**: Kyle has a pending request to Harry. Harry accepts it, and both users now
have the same `couple_id`. Repeat with a new request and have Harry reject it. No couple is
created, and Kyle sees "declined" and can send again.

### Tests for User Story 2

- [X] T020 [P] [US2] Create `tests/Feature/Couples/RespondToCoupleRequestTest.php`. Cover these cases:
  - **Accept**: one `couples` row exists, and Kyle and Harry both have its `couple_id`. The request is `accepted` with `responded_at` set.
  - **Other requests are closed on accept** (US2-6): Sam's pending request to Harry, and a pending request Kyle had sent to Sam, both become `closed`. `Couple::first()->members()->count() === 2`.
  - **Decline**: the request is `declined`, there are 0 couples, and the sender can send a new request.
  - **Accept or decline a request that isn't pending** (cancelled, declined or closed): throws "This request is no longer available."
  - **Accept when the sender is already in another couple**: create one with `Couple::factory()->withMembers($kyle, $sam)`. The accept throws "Kyle is already in a couple.", no new couple is created, Harry's `couple_id` stays null, and the request becomes `closed` (research R2).
  - **Livewire authorization**: a third user calling `acceptRequest($id)` or `declineRequest($id)` on `pages::partner.search` gets `assertForbidden()`.
  - **Livewire page**: Harry's page lists `incoming-request` showing "Kyle" and "@kyle" but not Kyle's email. Calling `acceptRequest` redirects to `route('couple.show')`.
  - **Search result**: when the person found has a pending request to the searcher, the result card shows accept and decline buttons.

### Implementation for User Story 2

- [X] T021 [US2] Create `app/Actions/Couples/AcceptCoupleRequest.php`, `__invoke(CoupleRequest $request): Couple`, following research R2. The first part runs inside `DB::transaction`:
  1. Re-read the request with `lockForUpdate()`. If it isn't `Pending`, throw "This request is no longer available."
  2. Create the `Couple`.
  3. Run `$updated = User::whereKey([$request->sender_id, $request->receiver_id])->whereNull('couple_id')->update(['couple_id' => $couple->id]);`
  4. If `$updated !== 2`, throw a private sentinel so the transaction rolls back.

  After the rollback, in a separate small transaction:
  1. Mark the request `Closed` with `responded_at = now()`.
  2. Throw `CoupleActionException` with ":name is already in a couple.", naming whichever user has a non-null `couple_id`, or the sender if both do.

  On success, inside the first transaction:
  1. Set the request to `Accepted` with `responded_at = now()`.
  2. Update every other `Pending` request where `sender_id` or `receiver_id` is in `[sender, receiver]` to `Closed` with `responded_at = now()` (FR-012).

  Return the couple. FR-011 to FR-013
- [X] T022 [P] [US2] Create `app/Actions/Couples/DeclineCoupleRequest.php`, `__invoke(CoupleRequest $request): void`. Inside `DB::transaction`:
  1. Re-read the request with `lockForUpdate()`.
  2. If it isn't `Pending`, throw "This request is no longer available."
  3. Otherwise set it to `Declined` with `responded_at = now()`.

  FR-014
- [X] T023 [US2] Update `resources/views/pages/partner/⚡search.blade.php` (PHP part):
  - **`incoming` computed property**: returns `Auth::user()->receivedCoupleRequests()->pending()->with('sender:id,name,username')->latest()->get()`, mapped to `{id, name, username}`.
  - **`acceptRequest(int $id)`**: `findOrFail`, then `$this->authorize('accept', $request)`, then call `AcceptCoupleRequest`. On success, call `$this->redirectRoute('couple.show', navigate: true)`. On `CoupleActionException`, add the error with its message, using the `Flux::toast(variant: 'danger', text: ...)` pattern.
  - **`declineRequest(int $id)`**: `findOrFail`, then `$this->authorize('decline', $request)`, then call `DeclineCoupleRequest`, catching `CoupleActionException` in the same way.
- [X] T024 [US2] Update `resources/views/pages/partner/⚡search.blade.php` (Blade part):
  - **Incoming requests**: when `incoming` isn't empty, show a "Requests for you" list above the search form. Each row (`data-test="incoming-request"`) shows an avatar, the name and `@username`, plus **Accept** (`data-test="accept-request-button"`) and **Reject** (`data-test="decline-request-button"`, `variant="danger"` or ghost).
  - **Result card**: replace the `pending_incoming` placeholder with ":name has sent you a request" and the same **Accept** and **Reject** buttons, wired to the matching request id.
- [X] T025 [US2] Add a minimal `/couple` route and page if T031 hasn't been done yet, so that the accept redirect works:
  - In `routes/web.php`, inside the existing `auth, verified, has-username` group, add `Route::livewire('couple', 'pages::couple.show')->name('couple.show');`.
  - Create `resources/views/pages/couple/⚡show.blade.php`, which shows the partner's name and `@username` (`data-test="couple-partner"`). US3 adds the end button.
- [X] T026 [US2] Run `dc php artisan test --filter='RespondToCoupleRequest|CouplePages|PartnerSearch'`. All of them MUST pass

**Checkpoint**: Two users can go from single to a couple with consent from both. Rejecting leaves both single.

---

## Phase 5: User Story 3 - End a couple (Priority: P2)

**Goal**: Either partner can end the couple after a confirmation modal that warns that the
couple's reviews will be permanently deleted. Both become single, and the couple's data is
deleted.

**Independent Test**: Kyle and Harry are a couple. Kyle opens `/couple`, clicks **End couple**,
sees the warning, and confirms. There are 0 couples, both `couple_id`s are null, and both can
open `/partner/search`.

### Tests for User Story 3

- [X] T027 [P] [US3] Create `tests/Feature/Couples/EndCoupleTest.php`. Cover these cases:
  - **Either member can end it**: calling `EndCouple` as either member deletes the `couples` row, and both users' `couple_id` becomes null (FR-016, SC-007).
  - **Livewire**: `pages::couple.show` with `call('endCouple')` redirects to `route('partner.search')`.
  - **Non-member**: a user who isn't a member, attempting the policy check `Gate::forUser($sam)->allows('end', $couple)`, is refused.
  - **Account deletion**: deleting Kyle's account through `$kyle->delete()` deletes the couple and sets Harry's `couple_id` to null (research R8). Kyle's pending requests are removed by the cascade.
  - **Becoming single again**: after the couple ends, both members can send new requests.
- [X] T028 [P] [US3] Add the US3 page tests in `tests/Feature/Couples/CouplePagesTest.php`:
  - A single user who opens `GET /couple` is redirected to `route('partner.search')`.
  - A user in a couple who opens `/couple` sees `couple-partner` with the partner's name and `@username`, plus `end-couple-button`.
  - The page contains the warning text "This will permanently delete all of your couple's reviews, for both of you. This cannot be undone." and `confirm-end-couple-button`.
  - No email address appears on the page.
  - **Nav label**: `GET /partner/search` for a single user shows "Find your partner". `GET /couple` for a user in a couple shows "Your partner" and not "Find your partner".

### Implementation for User Story 3

- [X] T029 [US3] Create `app/Actions/Couples/EndCouple.php`, `__invoke(Couple $couple): void`. Inside `DB::transaction`, call `$couple->delete()`. The `nullOnDelete` FK clears both users' `couple_id`. Future couple-owned tables cascade from `couples` (FR-016 and FR-017, data-model.md "Future couple-owned tables … MUST reference `couples.id` with `cascadeOnDelete`"). Add a docblock comment that states that rule
- [X] T030 [US3] Update `app/Models/User.php`. Add `protected static function booted(): void`, which registers `static::deleting(function (User $user) { if ($user->couple_id !== null && $user->couple) { app(EndCouple::class)($user->couple); } });` (research R8)
- [X] T031 [US3] Create or complete `resources/views/pages/couple/⚡show.blade.php`, with the title "Your partner":
  - **`mount()`**: if `! Auth::user()->isInCouple()`, call `$this->redirectRoute('partner.search', navigate: true)`.
  - **`partner` computed property**: returns `{name, username}` from `Auth::user()->partner()`.
  - **`endCouple()`**:
    1. `$couple = Auth::user()->couple`, then `$this->authorize('end', $couple)`.
    2. Call `app(EndCouple::class)($couple)`.
    3. Show `Flux::toast(text: __('Your couple has ended.'))`.
    4. Call `$this->redirectRoute('partner.search', navigate: true)`.
  - **View**:
    - A partner card (`data-test="couple-partner"`) with an avatar, the name and `@username`.
    - A **End couple** `flux:modal.trigger` button with `variant="danger"` (`data-test="end-couple-button"`). It opens a `flux:modal name="confirm-end-couple"` with:
      - Heading: "End your couple with :name?"
      - Body: "This will permanently delete all of your couple's reviews, for both of you. This cannot be undone."
      - A **Cancel** `flux:modal.close` button.
      - A danger **End couple** button (`data-test="confirm-end-couple-button"`, `wire:click="endCouple"`).

  Contracts/ui-routes.md
- [X] T032 [US3] Ensure `routes/web.php` has `Route::livewire('couple', 'pages::couple.show')->name('couple.show');` inside the `auth, verified, has-username` group. Skip this if T025 already added it
- [X] T033 [US3] Update `resources/views/layouts/app/header.blade.php`, in both the desktop `flux:navbar` and the mobile `flux:sidebar.nav`:
  - If `auth()->user()->isInCouple()`, show the item `icon="heart"`, label **Your partner**, `:href="route('couple.show')"`, `:current="request()->routeIs('couple.show')"`.
  - Otherwise keep the existing **Find your partner** item.

  Research R9
- [X] T034 [US3] Run `dc php artisan test --filter='EndCouple|CouplePages'`. All of them MUST pass

**Checkpoint**: All three stories work. The full lifecycle is: request, then accept or reject, then end.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T035 [P] Update `tests/Feature/NavigationTest.php` if any of its assertions about the "Find your partner" nav item now depend on the user's couple state. A single user must still see it
- [X] T036 [P] Add a short "Couples" section to `README.md`. It should explain that partner requests and couples are managed from **Find your partner** and **Your partner**, and that ending a couple deletes its reviews
- [X] T037 Run `dc composer ci:check` (Pint, PHPStan and the full suite on SQLite) and fix every Pint, PHPStan and test failure. Run `dc vendor/bin/pint` if formatting fails
- [X] T038 Run `dc php artisan migrate:fresh`, then `dc php artisan migrate:rollback --step=2`, then `dc php artisan migrate`. This proves both migrations run cleanly up and down on MySQL
- [ ] T039 Follow the manual walkthrough in `specs/005-couple-management/quickstart.md` with the `kyle`, `harry` and `sam` accounts, and confirm each expected outcome

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: has no dependencies. T001 and T002 can run in parallel.
- **Foundational (Phase 2)**: depends on Setup. It BLOCKS all of the stories.
  - T003 must come before T004.
  - T005 and T006 can run in parallel, and T007 depends on both.
  - T008 to T010 can run in parallel after T005, T006 and T007.
  - T011 comes last.
- **US1 (Phase 3)**: depends on Phase 2.
- **US2 (Phase 4)**: depends on Phase 2. It shares `⚡search.blade.php` with US1, so do it after US1 to avoid conflicting edits to the same file. Its actions and tests are independent of US1.
- **US3 (Phase 5)**: depends on Phase 2. Its tests can create couples with `CoupleFactory::withMembers`, so US3 doesn't need US1 or US2 to be built. T033 (nav) touches only the layout.
- **Polish (Phase 6)**: depends on all the stories.

### Within Each User Story

- Write the tests first and make sure they fail.
- Build the actions before the Livewire page logic, and the page logic before the Blade markup.
- Run the story's filtered tests at its checkpoint.

### Parallel Opportunities

- Phase 1: T001 and T002.
- Phase 2: T005 and T006. Then T008, T009 and T010.
- US1: T012, T013 and T014, which are separate test files (T014 adds to the new `CouplePagesTest.php`). T016 can run in parallel with T015.
- US2: T020 in parallel with T022.
- US3: T027 and T028 in parallel. T029 to T031 can be built while US1 or US2 are in progress, because they're separate files, apart from T030 (`User.php`, coordinate with T007).

---

## Parallel Example: User Story 1

```bash
# Tests first, in parallel:
Task: "SendCoupleRequestTest in tests/Feature/Couples/SendCoupleRequestTest.php"
Task: "CancelCoupleRequestTest in tests/Feature/Couples/CancelCoupleRequestTest.php"
Task: "US1 page tests in tests/Feature/Couples/CouplePagesTest.php"

# Then the actions, in parallel:
Task: "SendCoupleRequest action in app/Actions/Couples/SendCoupleRequest.php"
Task: "CancelCoupleRequest action in app/Actions/Couples/CancelCoupleRequest.php"
```

## Parallel Example: User Story 3

```bash
Task: "EndCoupleTest in tests/Feature/Couples/EndCoupleTest.php"
Task: "EndCouple action in app/Actions/Couples/EndCouple.php"
Task: "Couple page in resources/views/pages/couple/⚡show.blade.php"
```

---

## Implementation Strategy

### MVP First

1. Phase 1 and Phase 2: the foundation.
2. Phase 3 (US1): sending and cancelling requests. Validate on its own.
3. Phase 4 (US2): accepting and rejecting. This is the first point where couples actually form,
   so **US1 and US2 together are the usable MVP**.
4. **STOP and VALIDATE**: two users can become a couple with consent from both.

### Incremental Delivery

1. Foundation, then US1, then US2. Couples can now be created (MVP).
2. Add US3. Couples can be ended, with the deletion warning.
3. Polish: `composer ci:check`, migration round-trip and the manual walkthrough. Then open a PR
   into `dev`.

---

## Notes

- `couple_id` is set ONLY by `AcceptCoupleRequest`, and cleared ONLY by deleting the couple.
  Never mass-assign it.
- Every Livewire action that takes a request id must call `findOrFail` and then `authorize`
  before it calls an action. Never trust ids sent from the client.
- Never render or keep in component state: emails, or the partner of anyone in another couple.
- Commit after each phase checkpoint.
