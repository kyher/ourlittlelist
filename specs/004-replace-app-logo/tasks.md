---

description: "Task list for replacing the Laravel logo with a two-hearts placeholder (issue #13)"
---

# Tasks: Replace the Laravel Logo

**Input**: Design documents from `/specs/004-replace-app-logo/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/logo.md, quickstart.md

**Tests**: Required by FR-008 and Constitution Principle IV. Class-based PHPUnit (`extends Tests\TestCase`, `use RefreshDatabase;`, `test_*` methods, no Pest), run with `docker compose exec app php artisan test`.

**Organization**: Grouped by user story. US1 (in-page logo, P1) and US2 (browser-tab/home-screen icons, P2).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: US1 or US2

## Path Conventions

Single Laravel app at repo root. Scratch scripts go in the session scratchpad directory, never in the repo.

---

## Phase 1: Setup

- [X] T001 Start the stack with `docker compose up -d app` and run `docker compose exec app php artisan test` to confirm a green baseline (85 tests at the time of writing)

---

## Phase 2: Foundational

No foundational tasks. Both stories use the same artwork, but US2 exports it from US1's SVG, so the artwork is created in US1.

---

## Phase 3: User Story 1 - The app shows its own couple-themed logo (Priority: P1) 🎯 MVP

**Goal**: Every in-page logo (top bar, phone slide-out, home page, auth pages) shows two linked hearts in `currentColor`.

**Independent Test**: `/`, `/login` and `/partner/search` (signed in) show the hearts in light and dark appearance; no Laravel artwork in the HTML.

### Tests for User Story 1

- [X] T002 [US1] Create `tests/Feature/AppLogoTest.php` (namespace `Tests\Feature`, class `AppLogoTest extends TestCase`, `use RefreshDatabase`). Add a private const `LARAVEL_LOGO_PATH = 'M17.2 5.633'`. Tests: `test_the_home_page_shows_the_new_logo` (guest GET `route('home')` → OK, `assertSee('data-logo="our-little-list"', false)`, `assertDontSee(self::LARAVEL_LOGO_PATH, false)`); `test_the_login_page_shows_the_new_logo` (same against `route('login')`); `test_signed_in_pages_show_the_new_logo` (acting as `User::factory()->create()`, GET `route('partner.search')` → same assertions); `test_each_logo_on_a_page_uses_a_unique_mask_id` (signed-in GET `route('partner.search')`, use `preg_match_all('/<mask id="([^"]+)"/', $html, $m)`, assert `count($m[1]) >= 2` (top bar + slide-out) and `count($m[1]) === count(array_unique($m[1]))`). Run `docker compose exec app php artisan test --filter=AppLogoTest` and confirm they fail.

### Implementation for User Story 1

- [X] T003 [US1] Rewrite `resources/views/components/app-logo-icon.blade.php` to match `contracts/logo.md`. Starting point (tune positions by screenshot in T004):
  ```blade
  @php($maskId = 'logo-mask-'.\Illuminate\Support\Str::random(8))
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" data-logo="our-little-list" {{ $attributes }}>
      <defs>
          <mask id="{{ $maskId }}">
              <rect width="24" height="24" fill="white" />
              {{-- front heart, enlarged ~1.5 units in every direction, cut out of the back heart to leave a gap --}}
              <path transform="translate(7.6 6.6) scale(0.95)" d="M8 15C8 15 0 10 0 4.5 0 2 2 0 4.5 0 6 0 7.3.8 8 2 8.7.8 10 0 11.5 0 14 0 16 2 16 4.5 16 10 8 15 8 15Z" fill="black" stroke="black" stroke-width="3" />
          </mask>
      </defs>
      {{-- back heart (upper left) --}}
      <path transform="translate(1 2) scale(0.85)" mask="url(#{{ $maskId }})" fill="currentColor" d="M8 15C8 15 0 10 0 4.5 0 2 2 0 4.5 0 6 0 7.3.8 8 2 8.7.8 10 0 11.5 0 14 0 16 2 16 4.5 16 10 8 15 8 15Z" />
      {{-- front heart (lower right) --}}
      <path transform="translate(7.6 6.6) scale(0.95)" fill="currentColor" d="M8 15C8 15 0 10 0 4.5 0 2 2 0 4.5 0 6 0 7.3.8 8 2 8.7.8 10 0 11.5 0 14 0 16 2 16 4.5 16 10 8 15 8 15Z" />
  </svg>
  ```
  Requirements: only `currentColor`/mask colours (no other hard-coded colours), keep `{{ $attributes }}` on `<svg>`, no `<title>` (decorative; the brand link already carries the app name), both hearts fully inside the 0–24 viewBox. Do not edit callers (`components/app-logo.blade.php`, `welcome.blade.php`, `layouts/auth/simple.blade.php`).
- [X] T004 [US1] Run `docker compose exec app php artisan view:clear` and `--filter=AppLogoTest` until green. Then screenshot `/`, `/login`, `/partner/search` (desktop 1280px and phone 375px with slide-out open) in light and dark colour scheme using the Playwright Chromium at `~/.cache/ms-playwright/chromium-*/chrome-linux64/chrome` (script in the scratchpad; move `public/hot` aside while capturing if the vite service is not running, then restore it). Check: hearts clearly read as two overlapping hearts with a visible gap at `size-5` (20px) in the top bar, balanced inside the square, visible in both themes. Adjust the `transform` values in T003 if not.

**Checkpoint**: In-page logo replaced everywhere.

---

## Phase 4: User Story 2 - Browser tabs and home-screen shortcuts use the new icon (Priority: P2)

**Goal**: `public/favicon.svg`, `public/favicon.ico` and `public/apple-touch-icon.png` show the hearts in white on `#262626`.

**Independent Test**: Browser tab shows the hearts; `/favicon.ico` and `/apple-touch-icon.png` open as the hearts artwork.

**Depends on**: T003/T004 (final artwork geometry).

### Tests for User Story 2

- [X] T005 [US2] Add to `tests/Feature/AppLogoTest.php`: `test_the_svg_favicon_is_the_new_logo` (`$svg = file_get_contents(public_path('favicon.svg'))`; `assertStringNotContainsString('M162.041 38.7592', $svg)`; `assertStringContainsString('<mask', $svg)`; `assertStringContainsString('#262626', $svg)`); `test_the_ico_favicon_is_a_32px_png_icon` (`$ico = file_get_contents(public_path('favicon.ico'))`; `assertSame("\x00\x00\x01\x00", substr($ico, 0, 4))`; `assertSame(32, ord($ico[6]))` and `assertSame(32, ord($ico[7]))` (width/height bytes of the first ICONDIRENTRY); `$offset = unpack('V', substr($ico, 18, 4))[1]`; `assertSame("\x89PNG", substr($ico, $offset, 4))`); `test_the_apple_touch_icon_is_a_180px_png` (`$info = getimagesize(public_path('apple-touch-icon.png'))`; `assertSame([180, 180, IMAGETYPE_PNG], [$info[0], $info[1], $info[2]])`). Run and confirm they fail.

### Implementation for User Story 2

- [X] T006 [US2] Replace `public/favicon.svg` with a standalone SVG: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">`, a `<rect width="32" height="32" rx="7" fill="#262626"/>`, then the hearts from T003 inside `<g transform="translate(4 4)" fill="#ffffff">` (24-unit artwork centred in 32 with a 4-unit margin). Use a fixed mask id (`id="m"`) since this file is standalone. No Blade syntax, no Laravel artwork.
- [X] T007 [US2] In the scratchpad, write a Node script that loads `playwright-core` (e.g. from `~/.npm/_npx/*/node_modules/playwright-core`), launches the cached Chromium, and for each of (180, full-bleed: same SVG but `rx="0"`) and (32, as-is) does `page.setContent('<html><body style="margin:0;background:transparent">' + svgSizedTo(N) + '</body></html>')` then `page.locator('svg').screenshot({ omitBackground: true })`. Write the 180px PNG to `public/apple-touch-icon.png`. Write `public/favicon.ico` as: ICONDIR `Buffer.from([0,0,1,0,1,0])` + ICONDIRENTRY (16 bytes: width 32, height 32, colours 0, reserved 0, planes `UInt16LE 1`, bit count `UInt16LE 32`, size `UInt32LE png.length`, offset `UInt32LE 22`) + the 32px PNG bytes. Do not commit the script.
- [X] T008 [US2] Run `docker compose exec app php artisan test --filter=AppLogoTest` until green; open `/favicon.svg`, `/favicon.ico` and `/apple-touch-icon.png` in the Playwright browser and screenshot them (also render the SVG favicon at 16px) to confirm the hearts are recognisable at 16px and the background is the dark square.

**Checkpoint**: All Laravel artwork gone.

---

## Phase 5: Polish & Cross-Cutting Concerns

- [X] T009 Run `grep -rn "M17.2 5.633\|M162.041" resources public` and confirm no output (SC-001)
- [X] T010 Run `docker compose exec app composer ci:check` (Pint, Larastan, full test suite) and fix any failures
- [X] T011 Walk through `specs/004-replace-app-logo/quickstart.md` rows 1–7 using the screenshots from T004/T008 and tick each off

---

## Dependencies & Execution Order

- **T001** first.
- **US1**: T002 → T003 → T004.
- **US2**: depends on T004 (final heart geometry). T005 → T006 → T007 → T008. T005 can be written any time after T002 (same file, so not in parallel with T002).
- **Polish**: after both stories.

### Parallel Opportunities

Little parallelism: one component, one test file, and three exports that all derive from the final artwork. T005 (test) and T006 (favicon.svg) touch different files and can run together once T004 is done.

## Parallel Example: User Story 2

```bash
Task: "T005 Add favicon/ico/touch-icon tests in tests/Feature/AppLogoTest.php"
Task: "T006 Write public/favicon.svg"
```

## Implementation Strategy

### MVP First (User Story 1)

T001–T004: the visible logo is replaced on every page. Shippable alone; tabs would still show the Laravel icon.

### Incremental Delivery

1. US1 → in-page logo.
2. US2 → favicons and home-screen icon.
3. Polish → `composer ci:check` green, then PR into `dev` with `Closes #13`.
