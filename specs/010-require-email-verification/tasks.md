---

description: "Task list for requiring email verification"
---

# Tasks: Require Email Verification

**Input**: Design documents from `/specs/010-require-email-verification/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/ui-routes.md, quickstart.md

**Tests**: Included. Constitution Principle IV requires automated tests for every user-facing feature. Tests are class-based PHPUnit and run inside Docker.

**Organization**: Tasks are grouped by user story so each can be implemented and tested as its own increment.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1 to US4)

## Conventions for every task

- Run PHP and test commands inside Docker, e.g. `docker compose exec app php artisan test --filter=EmailVerificationTest`.
- `User::factory()` is verified by default and has a username. Use `User::factory()->unverified()` for unverified users, and `['username' => null]` for users with no username.
- Build verification links with `URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1($user->email)])`, as the existing tests do.
- Use `Illuminate\Support\Facades\Notification` and `Illuminate\Auth\Notifications\VerifyEmail` to assert on sent links.
- Do not add a migration. The data model is unchanged: `email_verified_at` is "timestamp, nullable" and "`null` means unverified".

---

## Phase 1: Setup

**Purpose**: Confirm a clean starting point

- [X] T001 Run `docker compose exec app composer ci:check` from the repository root and confirm it passes before any change, so later failures can be attributed to this feature

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Turn enforcement on. Every user story depends on this.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T002 In `tests/Feature/Auth/EmailVerificationTest.php`, delete `test_unverified_users_can_use_the_app_without_verifying` and its comment. Add a test backed by a `#[DataProvider]` asserting that an unverified user requesting each of `partner.search`, `couple.show`, `reviews.index`, `reviews.create`, `appearance.edit` and `security.edit` is redirected to `route('verification.notice')`. Add a separate test for `reviews.show` using an id from `Item::factory()->create()`. Add tests that an unverified user gets `assertOk()` on `verification.notice` and `profile.edit`, that an unverified user with `['username' => null]` gets `assertOk()` on `username.create`, that an unverified user can POST to `route('logout')` and `assertGuest()`, and that a verified user gets `assertOk()` on `partner.search` and `appearance.edit` (FR-002, FR-004, FR-013)
- [X] T003 In `tests/Feature/Auth/RegistrationTest.php`, replace `test_newly_registered_users_reach_partner_search_without_verifying_their_email` with `test_newly_registered_users_are_asked_to_verify_their_email`: post the same payload to `route('register.store')`, then assert that `$this->get(route('partner.search'))` redirects to `route('verification.notice')`. Run the tests from T002 and T003 and confirm the redirect tests fail
- [X] T004 In `app/Models/User.php`, import `Illuminate\Contracts\Auth\MustVerifyEmail` and change the class declaration to `class User extends Authenticatable implements MustVerifyEmail, PasskeyUser`. Run `docker compose exec app php artisan test --filter='EmailVerificationTest|RegistrationTest'` and confirm T002 and T003 now pass (FR-001, FR-002)

**Checkpoint**: Enforcement is active; unverified users cannot reach member pages

---

## Phase 3: User Story 1 - New members verify their email before using the app (Priority: P1) 🎯 MVP

**Goal**: Registration sends a link, following it opens the app, and neither open pages nor mail failures break the rule.

**Independent Test**: Register a new account, confirm every member page leads to the verify page, follow the emailed link, and confirm the member pages open.

### Tests for User Story 1

> Some of these pass as soon as Phase 2 is done, because they protect behaviour Fortify already provides. T008 and T009 MUST fail before T010 and T011.

- [X] T005 [P] [US1] In `tests/Feature/Auth/RegistrationTest.php`, add `test_registering_sends_a_verification_link`: call `Notification::fake()`, post `registrationPayload()` to `route('register.store')`, and assert `Notification::assertSentTo(User::where('email', 'harry@example.com')->first(), VerifyEmail::class)` (FR-005)
- [X] T006 [US1] In `tests/Feature/Auth/EmailVerificationTest.php`, add `test_a_signed_out_person_verifies_after_logging_in`: as a guest, `get` a valid link for an unverified user and assert a redirect to `route('login')`; post `email` and `password` (`'password'`) to `route('login.store')` and assert a redirect to the link; `get` the link and assert `hasVerifiedEmail()` on `$user->fresh()`
- [X] T007 [US1] In `tests/Feature/Auth/EmailVerificationTest.php`, add `test_verifying_without_a_username_continues_to_choosing_one`: as an unverified user with `['username' => null]`, `get` a valid link and assert a redirect to `route('partner.search', absolute: false).'?verified=1'`, then assert `get(route('partner.search'))` redirects to `route('username.create')` (FR-012)
- [X] T008 [US1] In `tests/Feature/Auth/EmailVerificationTest.php`, add a private helper and two tests for open pages. The helper takes a rendered response from `get(route('appearance.edit'))`, finds the `wire:snapshot` attribute whose decoded JSON has `memo.name` equal to `pages::settings.appearance` (`html_entity_decode` the attribute), and POSTs to `app('livewire')->getUpdateUri()` with header `X-Livewire: 1` and body `['_token' => csrf_token(), 'components' => [['snapshot' => $snapshotJson, 'updates' => [], 'calls' => []]]]`. Do not use `Livewire::test()`, because it skips persistent middleware. `test_an_open_page_keeps_working_while_verified` renders as a verified user and asserts the POST is `assertOk()`. `test_an_open_page_stops_working_once_unverified` renders as a verified user, calls `$user->markEmailAsUnverified()`, and asserts the POST redirects to `route('verification.notice')` (FR-003)
- [X] T009 [US1] In `tests/Feature/Auth/RegistrationTest.php`, add `test_registration_survives_a_mail_failure`: call `Exceptions::fake()` and `Notification::shouldReceive('send')->andThrow(new TransportException('down'))` (`Symfony\Component\Mailer\Exception\TransportException`), post `registrationPayload()` to `route('register.store')`, then assert `assertAuthenticated()`, that `get(route('verification.notice'))` is OK, and `Exceptions::assertReported(TransportException::class)` (spec edge case: a verification email fails to send)

### Implementation for User Story 1

- [X] T010 [US1] In `app/Providers/FortifyServiceProvider.php`, import `Illuminate\Auth\Middleware\EnsureEmailIsVerified` and add it to the `Livewire::addPersistentMiddleware([...])` array alongside `RequirePassword::class`. Rename `configurePasswordConfirmation()` to `configureLivewirePersistentMiddleware()`, update its call in `boot()`, and change the docblock to say that Livewire requests repeat the password confirmation and email verification checks of their page (FR-003, research R2)
- [X] T011 [US1] In `app/Models/User.php`, import `Symfony\Component\Mailer\Exception\TransportExceptionInterface` and add a `public function sendEmailVerificationNotification(): void` that calls `parent::sendEmailVerificationNotification()` inside `try` and `report($e)`s a `TransportExceptionInterface`. Add a docblock explaining that a mail outage must not fail registration or an email change, and that the person can request another link (research R5)

**Checkpoint**: User Story 1 works on its own and is the MVP

---

## Phase 4: User Story 2 - Getting unstuck while waiting to verify (Priority: P2)

**Goal**: People on the verify page can get a new link, correct their address, or sign out, and a bad link leads somewhere useful.

**Independent Test**: Register, request a new link from the verify page and confirm it arrives, then correct the address and confirm a link arrives there and verifies the account.

### Tests for User Story 2

- [X] T012 [US2] In `tests/Feature/Auth/EmailVerificationTest.php`, add tests: `get(route('verification.notice'))` as an unverified user shows the user's email and `route('profile.edit')`, and does not show `just emailed` (FR-008); posting to `route('verification.send')` with `Notification::fake()` sends `VerifyEmail` and sets session `status` to `verification-link-sent` (FR-007); a seventh POST within a minute is `assertStatus(429)` (FR-009)
- [X] T013 [US2] In `tests/Feature/Auth/EmailVerificationTest.php`, add tests: a link signed with `now()->subMinute()` redirects to `route('verification.notice')` with session `status` of `verification-link-invalid`, leaves the user unverified, and the page then shows `That link has expired or is invalid` (FR-010, FR-011); a link for another user is `assertForbidden()` and neither user is verified (edge case: signed in as a different account)
- [X] T014 [P] [US2] In `tests/Feature/Settings/ProfileUpdateTest.php`, add tests: as an unverified user with `Notification::fake()`, `Livewire::test('pages::settings.profile')->call('resendVerificationNotification')` sends `VerifyEmail` (FR-007); after six calls to `RateLimiter::hit('verification-link:'.$user->id, 60)`, the same call sends nothing and the rendered component shows `Too many verification emails` (FR-009)

### Implementation for User Story 2

- [X] T015 [US2] In `resources/views/pages/auth/verify-email.blade.php`, replace the opening `flux:text` copy with `{{ __('Please verify :email using the link in your verification email. If you can\'t find it, you can send a new one below.', ['email' => auth()->user()->email]) }}`. Below the existing `verification-link-sent` block, add an equivalent `@if (session('status') == 'verification-link-invalid')` block reading `{{ __('That link has expired or is invalid. Please request a new one.') }}`. Between the resend form and the log out form, add `<flux:link :href="route('profile.edit')" wire:navigate class="text-sm">{{ __('Change your email address') }}</flux:link>` (FR-004, FR-008, research R7)
- [X] T016 [US2] In `bootstrap/app.php`, inside `withExceptions`, add `$exceptions->render(function (InvalidSignatureException $e, Request $request) { ... })` that returns `redirect()->route('verification.notice')->with('status', 'verification-link-invalid')` when `$request->routeIs('verification.verify')` and otherwise returns `null`. Import `Illuminate\Routing\Exceptions\InvalidSignatureException` (FR-011, research R6)
- [X] T017 [US2] In `resources/views/pages/settings/⚡profile.blade.php`, import `Illuminate\Support\Facades\RateLimiter` and add two private methods to the component: `verificationLinkLimitReached(): bool`, returning `RateLimiter::tooManyAttempts('verification-link:'.Auth::id(), 6)`, and `recordVerificationLink(): void`, calling `RateLimiter::hit('verification-link:'.Auth::id(), 60)`. In `resendVerificationNotification()`, after the `hasVerifiedEmail()` check, if the limit is reached call `Session::flash('status', 'verification-link-throttled')` and return; otherwise call `recordVerificationLink()` before sending. In the template, next to the `verification-link-sent` block, show `{{ __('Too many verification emails. Please try again shortly.') }}` for that status (FR-007, FR-009, research R4)

**Checkpoint**: User Stories 1 and 2 both work

---

## Phase 5: User Story 3 - Changing an email address requires verifying the new one (Priority: P2)

**Goal**: A changed address is unverified and gets a link automatically.

**Independent Test**: As a verified member, change the address in profile settings, confirm a link arrives without asking, confirm member pages lead to the verify page, then follow the link and confirm access returns.

**Depends on**: T017 for the rate limit helpers.

### Tests for User Story 3

- [X] T018 [US3] In `tests/Feature/Settings/ProfileUpdateTest.php`, add tests using `Notification::fake()` and `Livewire::test('pages::settings.profile')`: changing `email` sends `VerifyEmail` to the user, whose `refresh()`ed `email` is the new address and whose `email_verified_at` is null (FR-006); saving with the same email leaves `email_verified_at` set and `Notification::assertNothingSent()` (FR-013); after six `RateLimiter::hit('verification-link:'.$user->id, 60)` calls, changing `email` gives `assertHasErrors('email')`, leaves the stored `email` and `email_verified_at` unchanged, and sends nothing (FR-009, contract)
- [X] T019 [P] [US3] In `tests/Feature/Auth/EmailVerificationTest.php`, add `test_a_link_for_a_previous_address_does_not_verify_the_new_one`: build a link with `sha1($oldEmail)` for an unverified user whose `email` has since changed, assert `assertForbidden()` and that the user is still unverified (FR-010)
- [X] T020 [P] [US3] In `tests/Feature/Couples/CouplePagesTest.php`, add a test: with `Couple::factory()->withMembers($this->kyle, $this->harry)->create()`, call `$this->kyle->markEmailAsUnverified()`, then assert `kyle` is redirected from `route('couple.show')` to `route('verification.notice')`, that `kyle->fresh()->couple_id` is unchanged, and that `harry` still gets `assertOk()` on `route('couple.show')` (FR-014, US3 scenario 5)

### Implementation for User Story 3

- [X] T021 [US3] In `updateProfileInformation()` in `resources/views/pages/settings/⚡profile.blade.php`, replace the `isDirty('email')` block with: `$emailChanged = $user->isDirty('email');`, then when `$emailChanged` and `verificationLinkLimitReached()`, `$this->addError('email', __('Too many verification emails. Please try again shortly.'))` and `return` before anything is saved; otherwise when `$emailChanged`, set `$user->email_verified_at = null`. After the `try`/`catch` around `save()`, when `$emailChanged`, call `recordVerificationLink()` then `$user->sendEmailVerificationNotification()`. Before the final toast, use the text `__('Profile updated. Check your new email address for a verification link.')` when `$emailChanged`, and keep `__('Profile updated.')` otherwise (FR-006, FR-009, FR-013, research R3, R4)

**Checkpoint**: User Stories 1, 2 and 3 all work

---

## Phase 6: User Story 4 - Existing accounts are brought in line (Priority: P3)

**Goal**: Accounts that never verified are asked to at their next visit and lose nothing. No production code is needed; Phase 2 delivers this and these tests protect it.

**Independent Test**: Sign in as an existing account that has never verified, request a link, follow it, and confirm the account's data is intact.

### Tests for User Story 4

- [X] T022 [P] [US4] In `tests/Feature/Auth/EmailVerificationTest.php`, add `test_existing_unverified_accounts_verify_and_keep_their_data`: create unverified and verified users joined with `Couple::factory()->withMembers(...)`, a review from `Review::factory()->by($unverified)`, then as the unverified user assert `get(route('reviews.index'))` redirects to `route('verification.notice')`, `get` a valid link, then assert `get(route('reviews.index'))` is OK, `couple_id` is unchanged and the review still exists (FR-014, US4). Check `database/factories/ReviewFactory.php` and an existing test in `tests/Feature/Reviews/` for any required attributes

**Checkpoint**: All user stories work

---

## Phase 7: Polish & Cross-Cutting Concerns

- [X] T023 [P] In `tests/Feature/Admin/AdminDashboardTest.php`, add a test that `$this->actingAs(Admin::factory()->create(), 'admin')->get(route('admin.dashboard'))` is `assertOk()` while an unverified `User` exists (FR-015)
- [X] T024 [P] In `README.md`, under "Viewing emails", change the example to "e.g. verification and password reset links", and add a sentence that any live environment needs working mail settings because people must verify their email before using the app
- [X] T025 Run `docker compose exec app composer ci:check` and fix any failures, including Pint and PHPStan issues, without weakening a test
- [X] T026 Walk through the manual scenarios in `specs/010-require-email-verification/quickstart.md`, and note any scenario that could not be run

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies
- **Foundational (Phase 2)**: Depends on Setup and blocks all user stories
- **User Stories (Phases 3 to 6)**: Depend on Foundational
- **Polish (Phase 7)**: Depends on the user stories being complete

### User Story Dependencies

- **US1 (P1)**: Depends only on Foundational
- **US2 (P2)**: Depends only on Foundational
- **US3 (P2)**: Depends on US2's T017 for the rate limit helpers
- **US4 (P3)**: Depends only on Foundational

### Within Each User Story

- Tests come before implementation
- T002 and T003 before T004
- T008 before T010, T009 before T011
- T017 before T021

### Parallel Opportunities

- Tasks touching `tests/Feature/Auth/EmailVerificationTest.php` or the profile page must run one at a time, so they are only marked [P] where the other tasks running alongside touch different files
- US1 and US2 can run side by side after Phase 2, except where both edit `tests/Feature/Auth/EmailVerificationTest.php`
- T023 and T024 can run together

---

## Parallel Example: User Story 3

```bash
Task: "Add email-change tests in tests/Feature/Settings/ProfileUpdateTest.php" # T018
Task: "Add couple unaffected test in tests/Feature/Couples/CouplePagesTest.php" # T020
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phases 1 and 2
2. Complete Phase 3
3. **STOP and VALIDATE** with quickstart scenarios 1 to 3 and 8

Do not ship the MVP alone: until US2 and US3 are done, a mistyped address strands someone, and changing an email sends no link.

### Incremental Delivery

1. Phases 1 and 2: enforcement on
2. US1: complete registration path
3. US2: ways out of the verify page
4. US3: email changes
5. US4 and Polish: protection for existing accounts, then full validation
