---

description: "Task list for the site colour scheme and welcoming home page"
---

# Tasks: Site Colour Scheme & Welcoming Home Page

**Input**: Design documents from `/specs/009-site-design-refresh/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/design-tokens.md, contracts/home-page.md, quickstart.md

**Tests**: Included. FR-023 and constitution Principle IV require automated tests. Use class-based PHPUnit tests extending `Tests\TestCase`, not Pest, and run them in Docker.

**Organization**: Tasks are grouped by user story so each story can be implemented and tested on its own.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: The user story this task belongs to (US1, US2, US3)
- Paths are relative to the repository root. Some file names contain a literal `⚡`, so quote them in shell commands

---

## Phase 1: Setup

**Purpose**: Establish a known-good baseline. No new packages or scaffolding are needed.

- [X] T001 Run `docker compose exec app composer ci:check` from the repository root and record any failures that already exist, so they are not mistaken for regressions from this feature

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Define the colour scheme in one place. The home page and the site-wide fixes both rely on these values.

**⚠️ CRITICAL**: All three tasks edit the same file, so do them in order and before any user story work.

- [X] T002 In the `@theme` block of `resources/css/app.css`, set `--color-zinc-50` to `--color-zinc-950` to these values: 50 `#fdf8f3`, 100 `#f8efe8`, 200 `#ecdfd6`, 300 `#dbcbc1`, 400 `#9c8b82`, 500 `#796860`, 600 `#5b4c46`, 700 `#463833`, 800 `#2f2522`, 900 `#1f1816`, 950 `#140f0d`. In the `@layer base` border rule of the same file, change `var(--color-gray-200, currentColor)` to `var(--color-zinc-200, currentColor)`
- [X] T003 In `resources/css/app.css`, set the `@theme` values `--color-accent: #8a2252`, `--color-accent-content: #8a2252`, `--color-accent-foreground: #ffffff`, and add `--color-red-500: #dc2626` and `--color-red-600: #b91c1c`. In the `.dark` rule inside `@layer theme`, set `--color-accent: #f2a7c3`, `--color-accent-content: #f2a7c3`, `--color-accent-foreground: #3a0f24`
- [X] T004 In `resources/css/app.css`, add unlayered rules next to the existing `[data-flux-control]` rules, per the "Form control overrides" table in `specs/009-site-design-refresh/contracts/design-tokens.md`: placeholder colour on `input`, `textarea` and `select` with `[data-flux-control]` is `var(--color-zinc-500)`, and `var(--color-zinc-300)` under `.dark`; border colour on those controls and on `[data-flux-checkbox-indicator]` and `[data-flux-radio-indicator]` is `var(--color-zinc-400)` in both appearances. Exclude disabled controls and checked indicators. Read `vendor/livewire/flux/stubs/resources/views/flux/checkbox/indicator.blade.php` and `radio/indicator.blade.php` first to confirm the attribute Flux uses for the checked state, so the accent-coloured checked state is not overridden

**Checkpoint**: The scheme exists. Flux components and `zinc` utilities are already re-coloured site-wide.

---

## Phase 3: User Story 1 - A signed-out visitor is encouraged to sign up (Priority: P1) 🎯 MVP

**Goal**: Signed-out visitors see a welcoming page that explains the app and offers sign-up at the top and the end.

**Independent Test**: Open `/` signed out at 320×568, 390×844 and 1440×900. Purpose and sign-up are visible without scrolling, the supporting sections are present, and both sign-up actions reach `/register`.

### Tests for User Story 1

> Write these first and confirm they fail before implementing.

- [X] T005 [US1] In `tests/Feature/HomePageTest.php`, replace `test_guests_see_log_in_and_register_links` so a guest sees `route('login')`, "Log in", "Create your account", and `route('register')` at least twice (use `substr_count` on the response content). Add a test that a guest sees the logo marker `data-logo="our-little-list"`, the headline, the "Example" label and example title, all three how-it-works steps, every `App\Enums\ReviewCategory::cases()` `label()`, and the privacy statement, all using the copy in `specs/009-site-design-refresh/contracts/home-page.md`
- [X] T006 [US1] In `tests/Feature/HomePageTest.php`, add a test that sets `config(['fortify.features' => array_values(array_diff(config('fortify.features'), [Laravel\Fortify\Features::registration()]))])`, then asserts a guest sees `route('login')` and does not see `route('register')` (the how-it-works step legitimately contains the words "Create your account.", so assert on the link)
- [X] T007 [US1] In `tests/Feature/HomePageTest.php`, add a test that creates a user with a distinctive name and username and `App\Models\Review::factory()->by($user)` with a distinctive `body`, then asserts the guest home page contains none of those values

### Implementation for User Story 1

- [X] T008 [P] [US1] Add a public `icon(): string` method to `app/Enums/ReviewCategory.php` using an exhaustive `match` with no default arm: `Film` → `film`, `TvShow` → `tv`, `Book` → `book-open`, `Food` → `cake`, `Restaurant` → `building-storefront`
- [X] T009 [P] [US1] Create the anonymous component `resources/views/components/home-cta.blade.php` rendering one primary `flux:button` per the "Actions by visitor state" table in `specs/009-site-design-refresh/contracts/home-page.md`: `@auth` shows "Open :app" to `route('partner.search')`; for guests, `Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::registration())` shows "Create your account" to `route('register')`, otherwise "Log in" to `route('login')`. Wrap labels in `__()`
- [X] T010 [US1] In `resources/views/welcome.blade.php`, replace the `<body>` and its contents with a `bg-zinc-50 dark:bg-zinc-900` body, keeping `@include('partials.head')` and `@fluxScripts`. Add a `<header>` top bar containing the logo (`x-app-logo-icon` with `text-accent-content`), the app name, and a secondary "Log in" `flux:button` to `route('login')` shown only with `@guest`, followed by a `<main>` element. Remove the old `Route::has('register')` check
- [X] T011 [US1] In `<main>` of `resources/views/welcome.blade.php`, add the hero (one `<h1>` with the headline, the explanation, `<x-home-cta />`) and the example section: a block labelled "Example" with the title "Moonlight Over Marrow Lane" and the "You" (`<x-star-rating :stars="4.5" />`) and "Your partner" (`<x-star-rating :stars="3" />`) reviews, using the copy in `contracts/home-page.md`. Keep source order the same as visual order on phones, and make sure the headline, explanation and action fit above the fold at 320×568 by keeping the heading compact on small screens. From the `lg` breakpoint the example may sit beside the hero text
- [X] T012 [US1] In `resources/views/welcome.blade.php`, after the example, add sections with an `<h2>` each: "How it works" (ordered list of the three steps), "For everything you do together" (loop over `App\Enums\ReviewCategory::cases()` using `<flux:icon :name="$category->icon()" aria-hidden="true" />` and `label()`), "Just for the two of you" (privacy statement), and the closing section "Start your little list today" with `<x-home-cta />`. Use only `zinc` and accent utilities, add no `transition` or `animate` classes, no images, and no hard-coded colours. Wrap copy in `__()`
- [X] T013 [US1] Run `docker compose exec app php artisan test --filter=HomePageTest` and fix `resources/views/welcome.blade.php` or `resources/views/components/home-cta.blade.php` until it passes

**Checkpoint**: User Story 1 works on its own and the existing signed-in home page test still passes.

---

## Phase 4: User Story 2 - The whole site shares one recognisable colour scheme (Priority: P1)

**Goal**: Every page uses the scheme in light and dark appearance, with all text and controls meeting the required contrast.

**Independent Test**: Visit every page in the quickstart's colour table in both appearances. The scheme is applied everywhere and a contrast checker reports no FR-005 failures.

### Tests for User Story 2

- [X] T014 [US2] In `tests/Feature/AppLogoTest.php`, change `test_the_svg_favicon_is_the_new_logo` to assert `favicon.svg` contains `#8a2252` in place of `#262626`, and confirm it fails

### Implementation for User Story 2

- [X] T015 [US2] In `public/favicon.svg`, change the rounded square's fill from `#262626` to `#8a2252`, leaving the white hearts unchanged
- [X] T016 [US2] Regenerate `public/apple-touch-icon.png` (180×180, full-bleed square, no rounded corners) and `public/favicon.ico` (32×32 PNG inside an ICO container: 6-byte ICONDIR, 16-byte ICONDIRENTRY, then the raw PNG) from `public/favicon.svg`, following spec 004 research R4 in `specs/004-replace-app-logo/research.md`. Use headless Chromium from the host's Playwright install with a throwaway script in the session scratchpad, and do not commit the script
- [X] T017 [P] [US2] In `resources/views/layouts/app/header.blade.php`, change the `<body>` classes from `bg-white dark:bg-zinc-800` to `bg-zinc-50 dark:bg-zinc-800`
- [X] T018 [P] [US2] In `resources/views/layouts/auth/simple.blade.php`, change the `<body>` classes to `min-h-screen bg-zinc-50 antialiased dark:bg-zinc-900`, remove the no-op `bg-background` class from the inner `<div>`, and change the logo classes from `fill-current text-black dark:text-white` to `fill-current text-accent-content`
- [X] T019 [P] [US2] In `resources/views/pages/settings/⚡two-factor-setup-modal.blade.php`, replace all 12 lines of `stone-*` utilities with the same step of `zinc-*`, and change the `text-green-500` check icon on line 307 to `text-green-700 dark:text-green-400`
- [X] T020 [P] [US2] In `resources/views/components/star-rating.blade.php`, change the outline star to `text-zinc-400` (removing `text-zinc-300 dark:text-zinc-600`) and the filled star to `text-amber-600 dark:text-amber-400`
- [X] T021 [P] [US2] In `resources/views/components/star-rating-input.blade.php`, change the outline star to `text-zinc-400` (removing `text-zinc-300 dark:text-zinc-600`), the filled star to `text-amber-600 dark:text-amber-400`, and the focus ring from `peer-focus-visible:ring-amber-500` to `peer-focus-visible:ring-accent`
- [X] T022 [P] [US2] In `resources/views/pages/reviews/⚡index.blade.php`, change the prompt icon on line 213 from `text-amber-500` to `text-amber-600 dark:text-amber-500`
- [X] T023 [P] [US2] In `resources/views/pages/auth/forgot-password.blade.php`, change line 26 from `text-zinc-400` to `text-zinc-500 dark:text-zinc-400`
- [X] T024 [P] [US2] In `resources/views/flux/navlist/group.blade.php`, change line 37 from `text-zinc-400` to `text-zinc-500 dark:text-zinc-400`
- [X] T025 [P] [US2] In `resources/views/components/auth-session-status.blade.php`, change `text-green-600` to `text-green-700 dark:text-green-400`
- [X] T026 [P] [US2] In `resources/views/pages/auth/verify-email.blade.php`, replace the malformed `!dark:text-green-400 !text-green-600` with `text-green-700! dark:text-green-400!`
- [X] T027 [P] [US2] In `resources/views/pages/settings/⚡profile.blade.php`, replace the malformed `!dark:text-green-400 !text-green-600` on line 126 with `text-green-700! dark:text-green-400!`
- [X] T028 [US2] Run `docker compose exec app php artisan test --filter=AppLogoTest` and fix `public/favicon.svg`, `public/favicon.ico` or `public/apple-touch-icon.png` until it passes

**Checkpoint**: User Stories 1 and 2 both work on their own.

---

## Phase 5: User Story 3 - A signed-in visitor can get back into the app from the home page (Priority: P2)

**Goal**: Signed-in visitors see the same page, with every sign-up and log in action replaced by a way into the app.

**Independent Test**: Sign in, open `/`, and confirm no sign-up or log in actions appear and the app link reaches the usual signed-in page.

### Tests for User Story 3

- [X] T029 [US3] In `tests/Feature/HomePageTest.php`, extend `test_signed_in_users_see_a_link_into_the_app` to assert `route('partner.search')` appears exactly three times as a link (top bar, hero, end of page) and that `route('register')`, `route('login')` and "Log in" are absent, then confirm it fails

### Implementation for User Story 3

- [X] T030 [US3] In the top bar of `resources/views/welcome.blade.php`, add an `@auth` branch beside the `@guest` one with a secondary `flux:button` labelled `__('Open :app', ['app' => config('app.name', 'Our Little List')])` linking to `route('partner.search')`
- [X] T031 [US3] Run `docker compose exec app php artisan test --filter=HomePageTest` and fix `resources/views/welcome.blade.php` until it passes

**Checkpoint**: All user stories work on their own.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Whole-feature verification against the quickstart.

- [X] T032 Run `docker compose exec app npm run build`, then `docker compose exec app composer ci:check`, and fix anything this feature caused in the files listed in `specs/009-site-design-refresh/plan.md`, comparing against the T001 baseline
- [X] T033 Carry out home page checks 1–7 in `specs/009-site-design-refresh/quickstart.md` in a real browser at the listed sizes, and fix any problem in `resources/views/welcome.blade.php`
- [X] T034 Carry out colour checks 8–11 in `specs/009-site-design-refresh/quickstart.md` on every listed page in light and dark appearance, including the admin pages and the two-factor modal, and fix any failure through `resources/css/app.css` or the affected view
- [X] T035 Carry out checks 12–13 in `specs/009-site-design-refresh/quickstart.md`: confirm no other neutral ramps remain in `resources/views`, and complete register, log in, partner connection, and creating and editing a review to confirm behaviour is unchanged

- [X] T036 Fix contrast failures found by the browser audit: muted text on translucent dark cards in `resources/views/pages/reviews/⚡show.blade.php` and `resources/views/pages/reviews/⚡index.blade.php` (`dark:text-zinc-300`), Flux `variant="subtle"` text in `resources/views/pages/settings/⚡security.blade.php` and `resources/views/pages/settings/two-factor/⚡recovery-codes.blade.php` (remove the variant), `opacity-50` text in `resources/views/pages/auth/two-factor-challenge.blade.php`, and the select placeholder in `resources/css/app.css`
- [X] T037 Add an accent `:focus-visible` outline to `@layer base` in `resources/css/app.css`, because buttons and links were showing the browser's default outline (FR-004)
---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies
- **Foundational (Phase 2)**: Depends on Setup and blocks all user stories
- **User Stories (Phases 3–5)**: Depend on Foundational
- **Polish (Phase 6)**: Depends on all user stories

### User Story Dependencies

- **US1 (P1)**: Can start after Foundational. No dependency on other stories
- **US2 (P1)**: Can start after Foundational. No dependency on other stories
- **US3 (P2)**: Depends on US1, because it extends the top bar and tests the page US1 builds

### Within Each User Story

- Tests are written and seen to fail before implementation
- US1: T008 and T009 before T010–T012, which run in order because they share `welcome.blade.php`
- US2: T015 before T016. T028 last

### Parallel Opportunities

- T008 and T009 can run together
- T017–T027 touch separate files and can all run together
- US1 and US2 can proceed at the same time after Foundational, since they share no files

---

## Parallel Example: User Story 2

```bash
Task: "Change the body background in resources/views/layouts/app/header.blade.php"
Task: "Change the body and logo colours in resources/views/layouts/auth/simple.blade.php"
Task: "Replace stone with zinc in resources/views/pages/settings/⚡two-factor-setup-modal.blade.php"
Task: "Update star colours in resources/views/components/star-rating.blade.php"
Task: "Update star colours in resources/views/components/star-rating-input.blade.php"
```

---

## Implementation Strategy

### MVP First

1. Complete Phases 1 and 2. The scheme is live on Flux components and `zinc` utilities
2. Complete Phase 3 (US1)
3. **Stop and validate**: run the US1 independent test

### Incremental Delivery

1. Setup + Foundational: scheme defined
2. US1: new home page for signed-out visitors
3. US2: every page finished and accessible
4. US3: signed-in home page actions
5. Polish: full quickstart pass

---

## Notes

- Follow `specs/009-site-design-refresh/contracts/` for exact values and copy
- Do not publish Flux vendor views, hard-code hex colours in views, or add packages
- Verify tests fail before implementing
- Branch from and open the PR against `dev`

---

## Phase 7: Convergence

- [X] T038 In `resources/css/app.css`, add `:not([data-invalid])` to the `input`, `textarea` and `select` selectors in the `--color-zinc-400` border rule so Flux's red error border shows again, then confirm a failed log in at `/login` gives the email input a red border in light and dark appearance, per FR-006 (contradicts)
- [X] T039 In `resources/views/pages/settings/⚡security.blade.php` line 291, add `dark:text-red-400 dark:hover:text-red-300` to the passkey remove button so it stays at or above 3:1 on dark surfaces after the red shift, per FR-005 (partial)
- [X] T040 In `resources/css/app.css`, add unlayered rules giving the checked option of Flux's segmented radio a `--color-accent` background with `--color-accent-foreground` text and icon, after reading `vendor/livewire/flux/stubs/resources/views/flux/radio/variants/segmented.blade.php` to confirm its marker attribute and checked state, then confirm the selected option at `/settings/appearance` in light and dark appearance, per FR-004 (partial)
- [X] T041 In `resources/views/components/passkey-verify.blade.php` line 69, change the divider label background from `bg-white dark:bg-zinc-900` to `bg-zinc-50 dark:bg-zinc-900` so no white box shows on the log in and confirm-password pages, per FR-003 (partial)

---

## Phase 8: Convergence

- [X] T042 In `resources/views/pages/auth/two-factor-challenge.blade.php` line 93, add `text-accent-content` to the recovery code toggle's `div` classes so this link-style control uses the accent colour, per FR-004 (partial)
