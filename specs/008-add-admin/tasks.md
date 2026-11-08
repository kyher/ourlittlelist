---

description: "Task list for Admin Users and System Totals"
---

# Tasks: Admin Users and System Totals

**Input**: Design documents from `/specs/008-add-admin/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/admin-command.md,
contracts/admin-routes.md, quickstart.md

**Tests**: Included. Constitution Principle IV requires feature tests, and Principle III requires
tests proving that non-admins cannot reach the totals. Tests are class-based PHPUnit
(`class XTest extends Tests\TestCase`, `use RefreshDatabase`, method names `test_...`). Pest is not
used. Write each story's tests first and confirm they fail before implementing.

**Commands**: Run everything inside Docker with `docker compose exec app …`, written `dc` below.
For example: `dc php artisan test --filter=Admin` and `dc composer ci:check`. CI runs on SQLite, so
everything MUST work on both SQLite and MySQL.

**Test pitfalls** (research R9):
- `actingAs($admin, 'admin')` makes `admin` the default guard for the rest of the test, so the
  regular `auth` middleware then treats the admin as signed in. Any test about crossing between the
  two logins MUST sign in with `$this->post(route('admin.login.store'), [...])` instead.
- One test shares a single app instance and session store across requests, so it cannot show two
  sessions surviving each other's logout. Assert the store's cookie name per path instead.
- Guards remember their user between requests in one test. After deleting a signed-in admin, call
  `$this->app['auth']->forgetGuards()` before the next request.
- `password()` falls back to a plain question under unit tests. Use
  `->expectsQuestion('Password', $value)` and `->expectsQuestion('Confirm password', $value)`, with
  the labels exactly as written here.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: User story from spec.md (US1–US3)

---

## Phase 1: Setup

**Purpose**: Confirm a clean starting point. There is nothing to install.

- [X] T001 Confirm the Docker stack is running and the existing suite passes with `dc composer ci:check`, so later failures can be attributed to this feature (config in `composer.json`)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: The admin account and guard that every story depends on

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T002 Create the migration in `database/migrations/2026_11_08_000000_create_admins_table.php` for table `admins` with: `id()`; `string('email')->unique()` (constraint: "string(255), unique", "stored trimmed and lowercase, unique among admins"); `string('password')`; `timestamps()`. No other columns, and no foreign keys to or from any table
- [X] T003 [P] Create `app/Models/Admin.php`, extending `Illuminate\Foundation\Auth\User as Authenticatable`, following the style of `app/Models/User.php`: `@property` docblock for Larastan, `#[Fillable(['email', 'password'])]`, `#[Hidden(['password'])]`, `/** @use HasFactory<AdminFactory> */`, and `casts()` returning `['password' => 'hashed']`. No `Notifiable`, two-factor or passkey traits, and no relationships
- [X] T004 [P] Create `database/factories/AdminFactory.php`, modelled on `database/factories/UserFactory.php`, with a lowercase `fake()->unique()->safeEmail()` and the same reused `Hash::make('password')` static
- [X] T005 Add to `config/auth.php` a guard `'admin' => ['driver' => 'session', 'provider' => 'admins']` and a provider `'admins' => ['driver' => 'eloquent', 'model' => App\Models\Admin::class]`. Leave the defaults, the `web` guard and the `users` provider unchanged
- [X] T006 Apply the migration with `dc php artisan migrate` (not `migrate:fresh`, which deletes development data) to create the table defined in `database/migrations/2026_11_08_000000_create_admins_table.php`

**Checkpoint**: `Admin::factory()->create()` works, and the `admin` guard resolves

---

## Phase 3: User Story 1 - Create an admin from the command line (Priority: P1) 🎯 MVP

**Goal**: `admin:create {email}` creates an admin after hidden, confirmed password entry. It is the only way to create one.

**Independent Test**: Run the command with a new email and a valid password, then confirm that exactly one admin exists for that email and no regular user was created.

### Tests for User Story 1

- [X] T007 [US1] Write `tests/Feature/Admin/CreateAdminCommandTest.php` using `$this->artisan('admin:create', ['email' => ...])`, covering:
  - a valid email and password succeeds, and exactly one `Admin` exists whose password passes `Hash::check`, with `User::count()` still 0
  - `Admin@Example.COM` is stored as `admin@example.com`, and surrounding whitespace is trimmed
  - a mismatched confirmation re-prompts (four `expectsQuestion` calls in order), then succeeds using the second password
  - a password shorter than 8 characters re-prompts, then succeeds. The test environment uses the non-production default rules
  - an empty password re-prompts
  - an email already used by an admin, in different letter case, returns failure with no prompts, and the existing admin's password hash is unchanged
  - a malformed email returns failure with no prompts, and creates nothing
  - an email of more than 255 characters returns failure and creates nothing
  - `'--no-interaction' => true` with a new, valid email returns failure and creates nothing
  - an email already used by a regular user succeeds, and that user's row is unchanged
  - the command definition from `Artisan::all()['admin:create']->getDefinition()` has an `email` argument and no `password` argument or option (FR-002)

### Implementation for User Story 1

- [X] T008 [US1] Create `app/Console/Commands/CreateAdmin.php` with signature `admin:create {email}`, using `App\Concerns\PasswordValidationRules` and `use function Laravel\Prompts\password;`, per `contracts/admin-command.md` and research R5. In order:
  1. Set `$email = Str::lower(trim($this->argument('email')))`.
  2. Validate `['email' => $email]` against `['required', 'string', 'email', 'max:255', Rule::unique(Admin::class)]` (constraint: "Valid email", "at most 255 characters", "unique in `admins`"). Do not check `users`. On failure, show the messages and return `self::FAILURE` before any prompt.
  3. If `! $this->input->isInteractive()`, or `! $this->laravel->runningUnitTests() && ! stream_isatty(STDIN)`, show "This command must be run interactively so a password can be entered." and return `self::FAILURE`.
  4. Loop: `$password = password('Password')`, `$confirmation = password('Confirm password')`, then validate `['password' => $password, 'password_confirmation' => $confirmation]` against `['password' => $this->passwordRules()]`. Show any errors and repeat until valid. Do not pass the prompts a `required` or `validate` argument, because `passwordRules()` covers both.
  5. `Admin::create(['email' => $email, 'password' => $password])`. Catch `Illuminate\Database\UniqueConstraintViolationException`, show the same duplicate message as step 2, and return `self::FAILURE`.
  6. Show a success message naming the email and return `self::SUCCESS`. Never output the password.
- [X] T009 [US1] Run `dc php artisan test --filter=CreateAdminCommandTest` and make `tests/Feature/Admin/CreateAdminCommandTest.php` pass

**Checkpoint**: Admins can be created from the command line

---

## Phase 4: User Story 2 - Log in to a separate admin area (Priority: P2)

**Goal**: Admins sign in and out at `/admin`, fully separate from regular login.

**Independent Test**: Log in through `/admin/login` and reach the admin area. Confirm that regular credentials fail there, and admin credentials fail at the regular login.

### Tests for User Story 2

- [X] T010 [US2] Write `tests/Feature/Admin/AdminAuthenticationTest.php`, signing in through `POST admin.login.store` unless noted, covering:
  - `GET admin.login` renders
  - correct credentials redirect to `admin.dashboard`, and `assertAuthenticated('admin')`
  - the submitted email matches in any letter case
  - a wrong password and an unknown email each return `assertSessionHasErrors(['email' => __('auth.failed')])` and `assertGuest('admin')`
  - a regular user's credentials at the admin login get that same error
  - an admin's credentials at `route('login.store')` get `assertSessionHasErrors('email')` and `assertGuest('web')`
  - the same email and password on both a `User` and an `Admin`: signing in at the admin login authenticates only `admin`, with `assertGuest('web')`
  - a guest at `admin.dashboard` redirects to `admin.login`
  - a regular user (`actingAs($user)`) at `admin.dashboard` redirects to `admin.login`
  - a signed-in admin at `route('reviews.index')` and `route('couple.show')` redirects to `route('login')`
  - a signed-in admin at `GET admin.login` redirects to `admin.dashboard`
  - logout via `POST admin.logout` gives `assertGuest('admin')`, redirects to `admin.login`, and `admin.dashboard` then redirects to `admin.login`
  - a sixth failed attempt within a minute for the same email returns 429
  - after `Admin::query()->delete()` and `forgetGuards()`, `admin.dashboard` redirects to `admin.login`
  - `app('session.store')->getName()` is `config('session.cookie').'-admin'` after `GET admin.login`, and `config('session.cookie')` after a following `GET route('login')`

### Implementation for User Story 2

- [X] T011 [P] [US2] Add to `configureRateLimiting()` in `app/Providers/FortifyServiceProvider.php` a limiter `admin-login`, returning `Limit::perMinute(5)->by(Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip()))` (research R3)
- [X] T012 [P] [US2] Create `app/Http/Middleware/UseAdminSessionCookie.php`. On every request, call `app('session.store')->setName(...)` with `config('session.cookie').'-admin'` when `$request->is('admin', 'admin/*')` and `config('session.cookie')` otherwise, then continue. The docblock MUST explain why: Fortify's logout invalidates the whole session (research R2)
- [X] T013 [US2] In `bootstrap/app.php`, inside `withMiddleware`, add `$middleware->web(prepend: [UseAdminSessionCookie::class]);` so it runs before `StartSession`, and `$middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin', 'admin/*') ? route('admin.login') : route('login'));` (research R6). Keep the existing aliases
- [X] T014 [P] [US2] Create `app/Http/Controllers/Admin/AuthenticatedSessionController.php` with:
  - `create()`: redirect to `admin.dashboard` if `Auth::guard('admin')->check()`, otherwise return `view('pages::admin.login')`
  - `store(Request $request)`: validate `email` and `password` as `['required', 'string']` only, with no `email` format rule so every failure looks the same. Call `Auth::guard('admin')->attempt(['email' => Str::lower($email), 'password' => $password])` with no remember option. On failure, throw `ValidationException::withMessages(['email' => __('auth.failed')])`. On success, `$request->session()->regenerate()` and `return redirect()->intended(route('admin.dashboard'))`
  - `destroy(Request $request)`: `Auth::guard('admin')->logout()`, `$request->session()->invalidate()`, `$request->session()->regenerateToken()`, then redirect to `admin.login`
- [X] T015 [P] [US2] Create invokable `app/Http/Controllers/Admin/DashboardController.php` returning `view('pages::admin.dashboard')`. US3 adds the totals
- [X] T016 [P] [US2] Create `resources/views/pages/admin/login.blade.php`, a plain Blade form based on `resources/views/pages/auth/login.blade.php`: `<x-layouts::auth :title="__('Admin log in')">`, `<x-auth-header>`, a `POST` to `route('admin.login.store')` with `@csrf`, the same email and password `flux:input` fields, and a submit button. Leave out `<x-passkey-verify />`, the forgot-password link, remember-me and sign-up link (research R4)
- [X] T017 [P] [US2] Create `resources/views/pages/admin/dashboard.blade.php` using `<x-layouts::auth :title="__('Admin')">`, a heading, and a `POST` form to `route('admin.logout')` with `@csrf` and a log-out `flux:button`. Do not use the app header layout or `<x-user-menu>`, which expect a `User`. Do not add Livewire components
- [X] T018 [US2] Create `routes/admin.php` per `contracts/admin-routes.md`, inside `Route::prefix('admin')->name('admin.')`: `GET login` (`login`), `POST login` (`login.store`, `middleware('throttle:admin-login')`), `POST logout` (`logout`, `middleware('auth:admin')`), `GET /` (`dashboard`, `middleware('auth:admin')`). Add `require __DIR__.'/admin.php';` after the settings require in `routes/web.php`
- [X] T019 [US2] Run `dc php artisan test --filter=AdminAuthenticationTest` and make `tests/Feature/Admin/AdminAuthenticationTest.php` pass

**Checkpoint**: Admin login works and is separate from regular login

---

## Phase 5: User Story 3 - View system totals (Priority: P3)

**Goal**: Signed-in admins see correct totals of users, couples and reviews, and nothing else.

**Independent Test**: With known numbers of users, couples and reviews, sign in as an admin and confirm that the three totals match.

### Tests for User Story 3

- [X] T020 [US3] Write `tests/Feature/Admin/AdminDashboardTest.php`, using `actingAs(Admin::factory()->create(), 'admin')` where crossing between logins isn't being tested. Set up data as `tests/Feature/Reviews/BrowseReviewsTest.php` does: `Couple::factory()->withMembers($a, $b)->create()`, `refresh()` both, and `app(CreateReview::class)($user, null, ReviewCategory::Film, $title, '4', $body)[0]`. Cover:
  - `assertViewHas('totals', ['users' => …, 'couples' => …, 'reviews' => …])` is exact with known data, and `assertSeeInOrder` shows the three labels
  - both partners reviewing the same title counts as 2 reviews (FR-018)
  - admin accounts are not included in the users total (FR-017)
  - an empty system shows 0 for all three
  - after a couple is ended with `App\Actions\Couples\EndCouple`, the couples total drops, and a later request shows current totals
  - no user name, username or email, and no review title or body, appears in the response (FR-019)
  - a guest and a regular user each get redirected to `admin.login` and do not see the totals (Principle III)

### Implementation for User Story 3

- [X] T021 [US3] Update `app/Http/Controllers/Admin/DashboardController.php` to pass `totals` as `['users' => User::count(), 'couples' => Couple::count(), 'reviews' => Review::count()]`, with no caching (research R7). Select no other data
- [X] T022 [US3] Update `resources/views/pages/admin/dashboard.blade.php` to show labelled Users, Couples and Reviews totals from `$totals`, formatted with `number_format()`, above the log-out form. Use translated labels and Flux text components, and do not show the admin's own email
- [X] T023 [US3] Run `dc php artisan test --filter=AdminDashboardTest` and make `tests/Feature/Admin/AdminDashboardTest.php` pass

**Checkpoint**: All three stories work

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Documentation and final validation

- [X] T024 [P] Add a row to the commands table in `README.md`: "Create an admin account" with `docker compose exec app php artisan admin:create <email>`
- [X] T025 Run `dc composer lint` and then `dc composer ci:check` (Pint, Larastan, tests), and fix anything found in the files listed in `specs/008-add-admin/plan.md`
- [X] T026 Walk through `specs/008-add-admin/quickstart.md` against the running app. Scenario 5, independent sessions, MUST be run in a real browser because automated tests cannot show it (research R9)
- [X] T027 Confirm with the user before verifying migrations from scratch on MySQL (Constitution quality gate). `migrate:fresh` deletes development data, so it MUST use a throwaway database or have explicit approval. The migration under test is `database/migrations/2026_11_08_000000_create_admins_table.php`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies
- **Foundational (Phase 2)**: Depends on Setup, and blocks all stories
- **US1 (Phase 3)**: Depends on Foundational only
- **US2 (Phase 4)**: Depends on Foundational only. Tests create admins through the factory, so US1 isn't needed
- **US3 (Phase 5)**: Depends on US2 for the login, routes and dashboard page
- **Polish (Phase 6)**: Depends on all stories

### Within Each Story

- Write tests first and confirm they fail
- US2: T013 needs T012's class. T018 needs T014 and T015. T019 needs all of US2
- US3: T021 before T022, because the view reads `$totals`

### Parallel Opportunities

- T003 and T004 (model and factory)
- US1 and US2 can run in parallel once Foundational is complete
- T011, T012, T014, T015, T016 and T017 (separate files in US2)
- T024 alongside the other Polish work

## Parallel Example: User Story 2

```text
T011 Add admin-login limiter in app/Providers/FortifyServiceProvider.php
T012 Create app/Http/Middleware/UseAdminSessionCookie.php
T014 Create app/Http/Controllers/Admin/AuthenticatedSessionController.php
T015 Create app/Http/Controllers/Admin/DashboardController.php
T016 Create resources/views/pages/admin/login.blade.php
T017 Create resources/views/pages/admin/dashboard.blade.php
```

## Implementation Strategy

### MVP First (User Story 1)

Complete Phases 1 to 3. Admins can be created, but they have nothing to log in to yet, so this is a
safe stopping point rather than a useful release.

### Incremental Delivery

1. Phases 1 and 2: the admin account exists
2. US1: admins can be created
3. US2: admins can log in and out, and the login is fully separated
4. US3: the totals appear, which is the first release with visible value
5. Polish, then open the PR into `dev`

---

## Phase 7: Convergence

- [X] T028 Make the `admin-login` limiter in `app/Providers/FortifyServiceProvider.php` treat a non-string `email` as an empty string when building its throttle key, so `email[]=x` reaches the controller's `string` validation and returns a validation error instead of a 500, and add a regression test asserting `assertSessionHasErrors('email')` for an array email in `tests/Feature/Admin/AdminAuthenticationTest.php` per FR-012 (partial)
- [X] T029 Add tests to `tests/Feature/Admin/AdminAuthenticationTest.php` asserting that a login with an empty or missing email, and with an empty or missing password, returns `assertSessionHasErrors` on that field and `assertGuest('admin')` per Constitution IV (partial)
