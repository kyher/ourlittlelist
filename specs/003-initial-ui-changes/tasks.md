---

description: "Task list for Initial UI Changes (remove template boilerplate & horizontal navigation)"
---

# Tasks: Initial UI Changes (Remove Template Boilerplate & Horizontal Navigation)

**Input**: Design documents from `/specs/003-initial-ui-changes/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/ui-routes.md, quickstart.md

**Tests**: Required. FR-011 and Constitution Principle IV require class-based PHPUnit feature tests (extending `Tests\TestCase`, `use RefreshDatabase;`, `test_*` method names, no Pest). All commands run in Docker: `docker compose exec app php artisan test`.

**Organization**: Tasks are grouped by user story. US1 and US2 are both P1; US1 goes first because the header layout must stop referencing `route('dashboard')` before US2 deletes that route.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)

## Path Conventions

Single Laravel app at repo root: views in `resources/views/`, routes in `routes/`, config in `config/`, tests in `tests/Feature/`.

---

## Phase 1: Setup

**Purpose**: Confirm a green baseline before changing anything.

- [X] T001 Run `docker compose exec app php artisan test` and confirm the suite passes on the current branch before any edits (record any pre-existing failures so they are not attributed to this feature)

---

## Phase 2: Foundational

No foundational tasks: there are no schema, middleware or shared-service changes. The header layout already exists in `resources/views/layouts/app/header.blade.php`.

---

## Phase 3: User Story 1 - Signed-in users navigate via a horizontal top bar (Priority: P1) 🎯 MVP

**Goal**: Every page using `layouts::app` (partner search, all settings pages) shows a horizontal top bar with logo, "Find your partner" and a user menu; no sidebar on desktop; slide-out menu on narrow screens.

**Independent Test**: Sign in, open `/partner/search` and `/settings/profile` at desktop and phone widths; the top bar is shown, "Find your partner" is marked current on its page, the user menu has name, email, Settings and Log out, and there are no Dashboard/Repository/Documentation/Search items.

### Tests for User Story 1

- [X] T002 [US1] Create `tests/Feature/NavigationTest.php` (class `NavigationTest extends TestCase`, `use RefreshDatabase`) with tests that act as `User::factory()->create(['name' => 'Sam Example'])` and GET `route('partner.search')`: (a) response is OK and contains "Find your partner" and a link to `route('partner.search')`; (b) contains the user's name, email, "Settings", a link to `route('profile.edit')`, "Log out" and `data-test="logout-button"`; (c) `assertDontSee` for "Repository", "Documentation", "github.com/laravel/livewire-starter-kit", "laravel.com/docs/starter-kits"; (d) `assertDontSee('data-test="sidebar-menu-button"', false)` proving the old sidebar profile button is gone; (e) GET `route('profile.edit')` also renders "Find your partner" and "Log out" (settings pages use the new layout). Run it and confirm (c)/(d) fail before implementation.

### Implementation for User Story 1

- [X] T003 [P] [US1] Create `resources/views/components/user-menu.blade.php`: a `flux:dropdown position="bottom" align="end"` wrapping `<flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />` and a `flux:menu` containing: a header block with `flux:avatar` (name + initials) and `flux:heading class="truncate"` (name) / `flux:text class="truncate"` (email); `flux:menu.separator`; `flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate` "Settings"; a `POST route('logout')` form with `@csrf` and `flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button"` "Log out". Wrap labels in `__()` as the existing views do.
- [X] T004 [US1] Edit `resources/views/layouts/app/header.blade.php`: (1) change both `x-app-logo` `href`s from `route('dashboard')` to `route('partner.search')`; (2) delete the desktop `flux:navbar.item` "Dashboard" (`icon="layout-grid"`), keep the "Find your partner" item; (3) delete the entire right-hand `<flux:navbar class="me-1.5 ...">` block (placeholder Search, Repository, Documentation tooltips); (4) replace `<x-desktop-user-menu />` with `<x-user-menu />`; (5) in the mobile `flux:sidebar`, delete the "Dashboard" `flux:sidebar.item` and the second `flux:sidebar.nav` (Repository/Documentation) plus the `flux:spacer` before it, keeping the "Find your partner" item (rename the group heading from "Platform" to nothing or remove the `:heading` attribute). Keep `@persist('toast')` and `@fluxScripts`.
- [X] T005 [US1] Edit `resources/views/layouts/app.blade.php` to `<x-layouts::app.header :title="$title ?? null"><flux:main container>{{ $slot }}</flux:main></x-layouts::app.header>` (depends on T004)
- [X] T006 [P] [US1] Delete `resources/views/layouts/app/sidebar.blade.php` and `resources/views/components/desktop-user-menu.blade.php` after confirming with `grep -rn "app.sidebar\|desktop-user-menu" resources app routes tests` that nothing else references them (depends on T003–T005)
- [X] T007 [US1] Run `docker compose exec app php artisan test --filter='NavigationTest|Settings'` and fix until green; open `/partner/search` and `/settings/profile` at ~375px and desktop width to confirm the slide-out toggle works, settings sub-navigation still renders, and there is no horizontal scroll

**Checkpoint**: Top-bar navigation works on all authenticated pages. The dashboard still exists (reachable by URL) but is no longer linked.

---

## Phase 4: User Story 2 - Dashboard is removed; users land somewhere useful (Priority: P1)

**Goal**: `/dashboard` is gone (404) and every default redirect goes to `/partner/search`, still honouring intended URLs.

**Independent Test**: Log in, register, choose a username and verify an email; each ends on `/partner/search`. `/dashboard` returns 404.

**Depends on**: US1 (T004) — the header layout must no longer call `route('dashboard')`.

### Tests for User Story 2

- [X] T008 [P] [US2] Delete `tests/Feature/DashboardTest.php`
- [X] T009 [P] [US2] In `tests/Feature/Auth/AuthenticationTest.php` line ~32, change `route('dashboard', absolute: false)` to `route('partner.search', absolute: false)`
- [X] T010 [P] [US2] In `tests/Feature/Auth/RegistrationTest.php`: change the redirect assertion at line ~55 to `route('partner.search', absolute: false)`; rename `test_newly_registered_users_land_on_the_dashboard_without_verifying_their_email` to `test_newly_registered_users_reach_partner_search_without_verifying_their_email` and change its `route('dashboard')` (line ~70) to `route('partner.search')`
- [X] T011 [P] [US2] In `tests/Feature/Auth/EmailVerificationTest.php` lines ~61 and ~94, change `route('dashboard', absolute: false).'?verified=1'` to `route('partner.search', absolute: false).'?verified=1'`
- [X] T012 [P] [US2] In `tests/Feature/Auth/ChooseUsernameTest.php`: remove the `'dashboard' => ['dashboard']` entry from the `gatedRoutes()` data provider (it lists pages that require a username, and the dashboard no longer exists); rename `test_users_can_choose_a_username_and_continue_to_the_dashboard` → `..._continue_to_partner_search` and `test_users_who_already_have_a_username_are_sent_to_the_dashboard` → `..._are_sent_to_partner_search`; change `route('dashboard')` at lines ~58, ~62 and ~113 to `route('partner.search')` (keeping `absolute: false` where present)
- [X] T013 [US2] Add to `tests/Feature/NavigationTest.php`: `test_the_old_dashboard_address_returns_not_found` (guest and authenticated `GET '/dashboard'` → `assertNotFound()`); `test_the_navigation_has_no_dashboard_item` (authenticated GET `route('partner.search')` → `assertDontSee('Dashboard')`); `test_login_still_honours_the_intended_url` (guest GET `route('profile.edit')` → redirected to login; then POST login with valid credentials for a user with a username → `assertRedirect(route('profile.edit', absolute: false))`)

### Implementation for User Story 2

- [X] T014 [P] [US2] In `config/fortify.php` set `'home' => '/partner/search'`
- [X] T015 [P] [US2] In `resources/views/pages/auth/⚡choose-username.blade.php` replace `$this->redirectRoute('dashboard', navigate: true)` (line ~21) with `$this->redirectRoute('partner.search', navigate: true)` and `route('dashboard', absolute: false)` (line ~45) with `route('partner.search', absolute: false)`
- [X] T016 [P] [US2] In `resources/views/pages/settings/⚡profile.blade.php` line ~70 change `route('dashboard', absolute: false)` to `route('partner.search', absolute: false)`
- [X] T017 [P] [US2] In `resources/views/components/passkey-verify.blade.php` line ~36 change the fallback `'/dashboard'` to `'/partner/search'`
- [X] T018 [P] [US2] In `resources/views/welcome.blade.php` change the `@auth` link `href="{{ route('dashboard') }}"` to `route('partner.search')` (temporary until US3 rewrites the page; keeps the home page from erroring)
- [X] T019 [US2] In `routes/web.php` delete `Route::view('dashboard', 'dashboard')->name('dashboard');` and delete `resources/views/dashboard.blade.php` and `resources/views/components/placeholder-pattern.blade.php` (depends on T004, T014–T018). Do NOT remove `'dashboard'` from `config/usernames.php`.
- [X] T020 [US2] Run `grep -rn "dashboard" app config routes resources tests` — the only remaining hits must be `config/usernames.php` and the reserved-username case in `tests/Feature/Auth/ChooseUsernameTest.php` (plus the NavigationTest 404 test). Then run `docker compose exec app php artisan test` and fix until green

**Checkpoint**: US1 + US2 together deliver the full navigation change with no dead routes.

---

## Phase 5: User Story 3 - Starter-template boilerplate is gone (Priority: P2)

**Goal**: The public home page is an Our Little List landing page, and unused starter-kit files are deleted.

**Independent Test**: Open `/` signed out (name, description, Log in, Register; no Laravel marketing) and signed in (link into the app). Authentication pages still work.

### Tests for User Story 3

- [X] T021 [US3] Extend `tests/Feature/HomePageTest.php`: keep the existing test; add `test_guests_see_log_in_and_register_links` (GET `route('home')` → `assertSee(route('login'))`, `assertSee(route('register'))`, `assertSee('Log in')`, `assertSee('Register')`); add `test_signed_in_users_see_a_link_into_the_app` (acting as a user with a username → `assertSee(route('partner.search'))`, `assertDontSee('Log in')`); add `test_home_page_has_no_laravel_starter_content` (`assertDontSee("Let's get started")`, `assertDontSee('Laracasts')`, `assertDontSee('Deploy now')`)

### Implementation for User Story 3

- [X] T022 [US3] Rewrite `resources/views/welcome.blade.php` as a small page (target < 50 lines, no inline `<style>` or Laravel SVG): `<!DOCTYPE html>` with `class="dark"` on `<html>` to match the app layouts, `<head>@include('partials.head')</head>`, body `min-h-screen bg-white dark:bg-zinc-800` with a centred column containing `<x-app-logo-icon class="size-12 fill-current text-black dark:text-white" />`, `<flux:heading size="xl" level="1">{{ config('app.name') }}</flux:heading>` (must render "Our Little List"), a `<flux:text>` one-liner such as "A shared list for the two of you to review films, series, books, food and restaurants together.", and buttons: `@auth` → `<flux:button variant="primary" :href="route('partner.search')">{{ __('Open Our Little List') }}</flux:button>` `@else` → `<flux:button variant="primary" :href="route('login')">{{ __('Log in') }}</flux:button>` and (if `Route::has('register')`) `<flux:button :href="route('register')">{{ __('Register') }}</flux:button>` `@endauth`; end with `@fluxScripts`. Check `resources/views/partials/head.blade.php` sets `<title>`; if it uses `$title`, pass nothing and let it fall back to the app name.
- [X] T023 [P] [US3] Delete unused auth layout variants `resources/views/layouts/auth/card.blade.php` and `resources/views/layouts/auth/split.blade.php` after `grep -rn "auth.card\|auth.split\|auth/card\|auth/split" resources app routes tests` returns nothing
- [X] T024 [P] [US3] Delete starter-kit-only icons `resources/views/flux/icon/layout-grid.blade.php`, `resources/views/flux/icon/folder-git-2.blade.php`, `resources/views/flux/icon/book-open-text.blade.php` and `resources/views/flux/icon/chevrons-up-down.blade.php` after `grep -rn "layout-grid\|folder-git-2\|book-open-text\|chevrons-up-down" resources app` returns only those files (depends on T004, T006). Keep `resources/views/flux/navlist/group.blade.php` (used by settings sub-navigation).
- [X] T025 [US3] Run `docker compose exec app php artisan test --filter='HomePageTest|Auth'` and fix until green

**Checkpoint**: All three stories complete.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T026 Run `grep -rni "laravel/livewire-starter-kit\|starter-kits#livewire\|Inspiring::\|placeholder-pattern" resources app routes` and confirm zero hits (SC-002)
- [X] T027 Run `docker compose exec app npm run build` to confirm Vite/Tailwind still compiles with the deleted views
- [X] T028 Run `docker compose exec app composer ci:check` (Pint, Larastan, tests — same as CI) and fix any failures; run `docker compose exec app ./vendor/bin/pint` if formatting fails
- [X] T029 Walk through every row of `specs/003-initial-ui-changes/quickstart.md` in a browser (desktop and ~375px width) and tick each off

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (T001)**: first.
- **US1 (T002–T007)**: after Setup.
- **US2 (T008–T020)**: after US1's T004 (header layout no longer calls `route('dashboard')`). Test edits T008–T012 and config/view edits T014–T018 can start as soon as T004 is done; T019 (route deletion) must come after all of them.
- **US3 (T021–T025)**: T022 after T018 (both edit `welcome.blade.php`); T024 after T004/T006. Otherwise independent of US2.
- **Polish (T026–T029)**: after all stories.

### User Story Dependencies

- **US1 (P1)**: independent; MVP.
- **US2 (P1)**: depends on US1 T004 only.
- **US3 (P2)**: T022 depends on US2 T018 (same file); T024 depends on US1 T004/T006.

### Within Each User Story

- Write/adjust tests first and confirm the new assertions fail, then implement, then run the story's test filter.

### Parallel Opportunities

- US1: T003 can be written alongside T002.
- US2: T008–T012 (five different test files) and T014–T018 (five different source files) are all parallel.
- US3: T023 and T024 are parallel with each other and with T021/T022.

---

## Parallel Example: User Story 2

```bash
# Test updates (different files):
Task: "T009 Update AuthenticationTest redirect assertion"
Task: "T010 Update RegistrationTest redirect + rename test"
Task: "T011 Update EmailVerificationTest redirects"
Task: "T012 Update ChooseUsernameTest redirects + renames"

# Source edits (different files):
Task: "T014 fortify home → /partner/search"
Task: "T015 choose-username redirects"
Task: "T016 profile redirectIntended default"
Task: "T017 passkey-verify fallback"
Task: "T018 welcome @auth link"
```

---

## Implementation Strategy

### MVP First (User Story 1)

1. T001 baseline.
2. T002–T007: top bar in place, dashboard unlinked but still reachable.
3. Stop and validate: NavigationTest green, check at desktop and phone widths.

### Incremental Delivery

1. US1 → horizontal nav (shippable on its own).
2. US2 → dashboard gone, redirects updated (ship US1+US2 together if preferred, since both are P1).
3. US3 → landing page and file cleanup.
4. Polish → `composer ci:check` green before opening the PR into `dev`.
