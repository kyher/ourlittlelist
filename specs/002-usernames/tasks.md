---

description: "Task list for Usernames & Partner Search"
---

# Tasks: Usernames & Partner Search

**Input**: Design documents from `/specs/002-usernames/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/ui-routes.md, quickstart.md

**Tests**: Included. Constitution Principle IV requires Pest feature and Livewire tests for every
user-facing feature, covering the happy path, validation errors and authorization.

**Organization**: Tasks are grouped by user story so each story can be built and tested on its own.

**Commands**: Run everything through Sail (`./vendor/bin/sail …`, written `sail` below).
Livewire 4 single-file pages live at `resources/views/pages/<area>/⚡<name>.blade.php` and are
referenced as `pages::<area>.<name>`. Use `sail artisan make:livewire` or copy the structure of
`resources/views/pages/settings/⚡profile.blade.php`.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: User story from spec.md (US1, US2, US3)

---

## Phase 1: Setup

**Purpose**: Configuration shared by every story

- [X] T001 Create `config/usernames.php` returning `['reserved' => ['admin', 'administrator', 'support', 'help', 'settings', 'dashboard', 'login', 'logout', 'register', 'api', 'root', 'system', 'username', 'partner', 'ourlittlelist']]` (research R2)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: The username column, model, validation rules and factory that every story depends on

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T002 Create migration `database/migrations/2026_11_06_000000_add_username_to_users_table.php`. In `up()`, add `$table->string('username', 30)->nullable()->unique()->after('name');`. In `down()`, drop the unique index and then the column. No data backfill (data-model.md)
- [X] T003 Update `app/Models/User.php`. Add `'username'` to the `#[Fillable([...])]` attribute. Add `@property string|null $username` to the docblock. Add an Eloquent `Attribute` mutator `username()` whose `set` returns `null` for null input and otherwise `Str::lower(trim($value))` (research R1)
- [X] T004 [P] Add `usernameRules(?int $userId = null): array` to `app/Concerns/ProfileValidationRules.php`. It returns `['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9][a-z0-9_.]*$/', Rule::notIn(config('usernames.reserved')), $userId === null ? Rule::unique(User::class) : Rule::unique(User::class)->ignore($userId)]`. Do NOT add it to `profileRules()` yet, because the profile page does that in US3. Also add `protected function normaliseUsername(?string $value): string` returning `Str::lower(trim((string) $value))`, which callers apply before validating
- [X] T005 [P] Update `database/factories/UserFactory.php`. In `definition()`, add `'username' => Str::limit(preg_replace('/[^a-z0-9_.]/', '_', Str::lower(fake()->unique()->userName())), 30, '')`, making sure the first character is `[a-z0-9]` (prefix `u` if not). Add a `withoutUsername(): static` state that sets `'username' => null`
- [X] T006 Run `sail artisan migrate:fresh` and `sail artisan test`. Every existing test MUST still pass

**Checkpoint**: Users can hold a lowercase, unique username. The stories can now proceed.

---

## Phase 3: User Story 1 - Choose a username at registration (Priority: P1) 🎯 MVP

**Goal**: New users must choose a valid, unique (case-insensitive) username when they register.

**Independent Test**: Register with username `Harry`. The account is created with `username = 'harry'`. Registering again with `harry` is rejected with an error on `username`.

### Tests for User Story 1

- [X] T007 [US1] Update `tests/Feature/Auth/RegistrationTest.php`. Add `'username' => …` to every existing `post(route('register.store'), …)` payload (use unique values). Add tests for:
  1. Registering with `'  Harry '` stores `username` as `harry`.
  2. Registering with `HARRY` when a factory user has `harry` gives `assertSessionHasErrors('username')` and the user stays a guest.
  3. Usernames `ab`, a 31-character name, `_harry`, `har ry` and `har-ry` each fail on `username`.
  4. Reserved `admin` fails on `username`.
  5. A missing username fails on `username`.
  6. The registration screen contains the `name="username"` input.

### Implementation for User Story 1

- [X] T008 [US1] Update `app/Actions/Fortify/CreateNewUser.php`. Before validating, set `$input['username'] = $this->normaliseUsername($input['username'] ?? null)`. Add `'username' => $this->usernameRules()` to the `Validator::make` rules. Pass `'username' => $input['username']` to `User::create`. Wrap the create in `try { … } catch (UniqueConstraintViolationException) { throw ValidationException::withMessages(['username' => __('That username is taken.')]); }` (research R3)
- [X] T009 [US1] Update `resources/views/pages/auth/register.blade.php`. After the name `flux:input`, add a `flux:input` with `name="username"`, `:label="__('Username')"`, `:value="old('username')"`, `type="text"`, `required`, `autocomplete="username"`, `:placeholder="__('e.g. harry')"`, and a description/hint: "3–30 characters: letters, numbers, _ and . Your partner uses this to find you."
- [X] T010 [US1] Add custom validation messages: regex → "Usernames may only contain letters, numbers, underscores and full stops, and must start with a letter or number."; `not_in` → "That username isn't available."; `unique` → "That username is taken.". Pass them as the third argument of `Validator::make` in `app/Actions/Fortify/CreateNewUser.php`, sourced from a new `usernameMessages(): array` method in `app/Concerns/ProfileValidationRules.php` so US3 can reuse them
- [X] T011 [US1] Run `sail artisan test --filter=Registration`. All tests pass

**Checkpoint**: New accounts always have a username. This is a shippable MVP.

---

## Phase 4: User Story 2 - Search for a partner by username (Priority: P1)

**Goal**: A signed-in user can find another user by exact username (case-insensitive) and sees only their username and name.

**Independent Test**: With factory users `kyle` and `harry`, act as Kyle, search `HARRY`, and see `@harry` with Harry's name and no email address.

### Tests for User Story 2

- [X] T012 [P] [US2] Create `tests/Feature/PartnerSearchTest.php` (Pest, using `Livewire::test('pages::partner.search')`). Add tests for:
  1. A guest visiting `route('partner.search')` is redirected to `route('login')`.
  2. An authenticated user sees the page (`assertOk`).
  3. Setting `query` to `HARRY` and calling `search` shows `@harry` and Harry's name.
  4. `assertDontSee` Harry's email, both in the HTML and in the `result` component state.
  5. The term `'  harry  '` matches.
  6. The partial term `har` shows "No user found with that username.".
  7. Searching your own username shows "No user found with that username.".
  8. An empty or whitespace-only query gives `assertHasErrors('query')`.
  9. After 30 `search` calls, the 31st gives `assertHasErrors('query')` with the message containing "Too many searches". Call `RateLimiter::clear('partner-search:'.$user->id)` in `beforeEach`.

### Implementation for User Story 2

- [X] T013 [US2] Create the Livewire page `resources/views/pages/partner/⚡search.blade.php` with `#[Title('Find your partner')]`. It has a public `string $query = ''`, a public `?array $result = null` (shape `['username' => string, 'name' => string]`) and a public `bool $searched = false`. Add `search()` which:
  1. Sets `$term = Str::lower(trim($this->query))`. If it is `''`, call `$this->addError('query', __('Enter a username to search for.'))` and return.
  2. Checks the limiter: `if (! RateLimiter::attempt('partner-search:'.Auth::id(), 30, fn () => true, 60)) { $this->addError('query', __('Too many searches. Try again in :seconds seconds.', ['seconds' => RateLimiter::availableIn('partner-search:'.Auth::id())])); return; }`.
  3. Looks up `$user = User::query()->where('username', $term)->whereKeyNot(Auth::id())->first(['id', 'name', 'username'])`.
  4. Sets `$this->result = $user ? ['username' => $user->username, 'name' => $user->name] : null` and `$this->searched = true`.

  The view is a `flux:heading` "Find your partner", a short explanation ("Ask your partner for their username, then search for it here."), and a `<form wire:submit="search">` containing `flux:input wire:model="query"` (label "Username", `data-test="partner-search-input"`) and a `flux:button type="submit" variant="primary"` "Search". Below the form, show a result `flux:card` with `@{{ $result['username'] }}` and `{{ $result['name'] }}` when `$result` is set. Show "No user found with that username." when `$searched && ! $result`. There is NO connect/invite button (FR-013)
- [X] T014 [US2] Update `routes/web.php`. Inside the existing `Route::middleware(['auth', 'verified'])` group, add `Route::livewire('partner/search', 'pages::partner.search')->name('partner.search');`
- [X] T015 [P] [US2] Add a nav item `<flux:sidebar.item icon="magnifying-glass" :href="route('partner.search')" :current="request()->routeIs('partner.search')" wire:navigate>{{ __('Find your partner') }}</flux:sidebar.item>` after the dashboard item in `resources/views/layouts/app/sidebar.blade.php`. Add the same after the dashboard item in `resources/views/layouts/app/header.blade.php` (in both its desktop navbar and mobile sidebar if both list the dashboard)
- [X] T016 [US2] Run `sail artisan test --filter=PartnerSearch`. All tests pass

**Checkpoint**: Kyle can find Harry. US1 and US2 together deliver the feature's core value.

---

## Phase 5: User Story 3 - Existing users set and change their username (Priority: P2)

**Goal**: Users without a username must choose one before using the app. Any user can change their username in profile settings.

**Independent Test**: A `withoutUsername()` user visiting `/dashboard` is redirected to `/username`. After choosing `kyle` they reach the dashboard. In settings they change it to `kyle_h`, and a taken name is rejected.

### Tests for User Story 3

- [X] T017 [P] [US3] Create `tests/Feature/Auth/ChooseUsernameTest.php`. Add tests for:
  1. A `withoutUsername()` user GETting `route('dashboard')`, `route('profile.edit')` and `route('partner.search')` is redirected to `route('username.create')`.
  2. That user can GET `route('username.create')` (`assertOk`).
  3. `Livewire::test('pages::auth.choose-username')->set('username', 'Kyle')->call('save')` stores `kyle` and redirects to `route('dashboard')`.
  4. Validation errors on `username` for invalid, reserved and taken (case-insensitive) values.
  5. A user who already has a username GETting `route('username.create')` is redirected to `route('dashboard')`.
  6. A `withoutUsername()` user can still `post(route('logout'))`.
  7. A guest GETting `route('username.create')` is redirected to login.
- [X] T018 [P] [US3] Extend `tests/Feature/Settings/ProfileUpdateTest.php`. Add `->set('username', …)` to the existing update test. Add tests for:
  1. Changing the username to `Kyle_H` stores `kyle_h`.
  2. Changing to another user's username (in a different case) gives `assertHasErrors('username')` and the stored username is unchanged.
  3. Saving with your own unchanged username succeeds.
  4. A reserved name gives `assertHasErrors('username')`.

### Implementation for User Story 3

- [X] T019 [US3] Create `app/Http/Middleware/EnsureUserHasUsername.php`. `handle()`: `if ($request->user() && $request->user()->username === null) { return redirect()->guest(route('username.create')); }` (this stores the intended URL), then `return $next($request);`
- [X] T020 [US3] Register the alias in `bootstrap/app.php` inside `withMiddleware`: `$middleware->alias(['has-username' => \App\Http\Middleware\EnsureUserHasUsername::class]);`
- [X] T021 [US3] Create the Livewire page `resources/views/pages/auth/⚡choose-username.blade.php`. It uses `ProfileValidationRules` with `#[Title('Choose a username')]` and the app layout. `mount()` redirects to `route('dashboard')` if `Auth::user()->username !== null`. It has a public `string $username = ''`. `save()`:
  1. Normalises with `$this->username = $this->normaliseUsername($this->username)`.
  2. Validates with `$this->validate(['username' => $this->usernameRules(Auth::id())], $this->usernameMessages())`.
  3. Saves with `Auth::user()->forceFill(['username' => $this->username])->save()` inside a try/catch of `UniqueConstraintViolationException` that does `addError('username', __('That username is taken.'))`.
  4. Then calls `$this->redirectIntended(default: route('dashboard', absolute: false), navigate: true)`.

  The view has a heading "Choose a username", the text "Your partner will use this to find you.", a `flux:input wire:model="username"` and a Save button
- [X] T022 [US3] Update `routes/web.php`. Add `Route::livewire('username', 'pages::auth.choose-username')->middleware('auth')->name('username.create');` OUTSIDE the gated groups. Add `'has-username'` to the `['auth', 'verified']` group's middleware array, which covers `dashboard` and `partner.search`
- [X] T023 [US3] Update `routes/settings.php`. Add `'has-username'` to both middleware groups (`['auth']` → `['auth', 'has-username']` and `['auth', 'verified']` → `['auth', 'verified', 'has-username']`). Leave `well-known.passkeys` ungated
- [X] T024 [US3] Update `app/Concerns/ProfileValidationRules.php` so that `profileRules(?int $userId = null)` also returns `'username' => $this->usernameRules($userId)`. Then update `app/Actions/Fortify/CreateNewUser.php` to drop its now-duplicate explicit `username` rule, so it relies on `profileRules()`
- [X] T025 [US3] Update `resources/views/pages/settings/⚡profile.blade.php`:
  - Add `public string $username = '';`, set from `Auth::user()->username ?? ''` in `mount()`.
  - In `updateProfileInformation()`, set `$this->username = $this->normaliseUsername($this->username)` before `$this->validate($this->profileRules($user->id), $this->usernameMessages())`.
  - Wrap `$user->save()` in a try/catch of `UniqueConstraintViolationException` that does `addError('username', __('That username is taken.'))` and returns.
  - In the view, add a `flux:input wire:model="username" :label="__('Username')"` between Name and Email.
  - Change the subheading to "Update your name, username and email address".
- [X] T026 [US3] Run `sail artisan test --filter="ChooseUsername|ProfileUpdate"`. All tests pass

**Checkpoint**: Every active user has a username, and anyone can change theirs.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T027 [P] Check that `tests/Feature/DashboardTest.php` and other kit tests still pass with the `has-username` gate. Factory users have usernames, so no change should be needed. Fix any test that builds users without the factory
- [X] T028 Run `sail bin pint` and fix any style issues
- [X] T029 Run `sail composer ci:check` (full suite and Pint, using SQLite like CI). It MUST pass
- [X] T030 Work through the manual steps in `specs/002-usernames/quickstart.md` (manual walkthrough and legacy user check) in the browser via Sail

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: none
- **Foundational (Phase 2)**: depends on T001, because `usernameRules()` reads the config. It blocks all stories
- **US1 (Phase 3)**: depends on Phase 2
- **US2 (Phase 4)**: depends on Phase 2 only. Factory users have usernames, so it does not need US1
- **US3 (Phase 5)**: depends on Phase 2. T024 touches `CreateNewUser.php`, so do US1 first to avoid conflicts. T022 changes the route group that T014 created, so do US2 first
- **Polish (Phase 6)**: after all stories

### Within Each Story

Write the tests first and confirm they fail. Then implement, and run the story's filter until it passes.

### Parallel Opportunities

- T004 and T005 (different files) once T002 and T003 are done
- T012 and T015 can run alongside T013
- T017 and T018 can run alongside each other and alongside T019–T021
- US1 and US2 are independent after Phase 2. Two people could work on them in parallel, but T010 and T024 both touch the shared trait

## Parallel Example: User Story 3

```text
Task: "T017 Create tests/Feature/Auth/ChooseUsernameTest.php"
Task: "T018 Extend tests/Feature/Settings/ProfileUpdateTest.php"
Task: "T019 Create app/Http/Middleware/EnsureUserHasUsername.php"
```

## Implementation Strategy

### MVP First

1. Phases 1–2, then US1. Every new account gets a username (shippable).
2. Then US2. Kyle can find Harry, which is the core value of the feature.
3. Then US3. Covers legacy accounts and lets users change their username.
4. Then Polish, then the PR into `dev` (`gh pr create --base dev`).

## Notes

- Never select or expose `email` in the partner search (FR-009). The tests assert this.
- Store usernames in lowercase (mutator in T003). Still normalise before validating, so that the `unique` rule compares lowercase values.
- Do not add any connect, invite or couple behaviour (FR-013).
