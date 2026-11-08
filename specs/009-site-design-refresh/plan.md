# Implementation Plan: Site Colour Scheme & Welcoming Home Page

**Branch**: `ui-changes` | **Date**: 2026-11-08 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/009-site-design-refresh/spec.md`

## Summary

Give the site a warm berry-and-cream identity and replace the minimal home page with one that encourages sign-ups. Flux and nearly all views already draw their neutrals from a single `zinc` ramp that `resources/css/app.css` overrides, so re-tinting that ramp and changing the accent re-colours almost the whole site from one file. A small number of targeted fixes follow: views that bypass the ramp, stock colours that fail WCAG AA, and page backgrounds. The home page stays a static Blade view on the existing route, with no new dependencies, data or routes. See [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.3+, Laravel 13; Blade; CSS (Tailwind CSS 4); SVG

**Primary Dependencies**: Flux UI 2 and Livewire 4 (unchanged). No new packages. One-off icon export reuses the Playwright Chromium already on the developer machine (not committed)

**Storage**: N/A

**Testing**: PHPUnit class-based feature tests (`tests/Feature/HomePageTest.php`, `tests/Feature/AppLogoTest.php`), run in Docker

**Target Platform**: Web browsers, desktop and mobile, from 320px wide

**Project Type**: Server-rendered Laravel + Livewire web application

**Performance Goals**: N/A. The home page adds no images, queries or scripts

**Constraints**: WCAG 2.1 AA contrast in light and dark (FR-005); scheme defined in one place (FR-002); no animation on the home page (FR-021); purpose and sign-up visible without scrolling at 320×568 (SC-003); Flux vendor views stay unpublished

**Scale/Scope**: 1 stylesheet, 3 layouts/views restructured or re-backgrounded, about 8 views with small colour fixes, 1 new anonymous component, 1 enum method, 3 icon files, 2 test files

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Laravel & Livewire starter kit conventions | ✅ Pass | Uses Flux components and the kit's theme variables. Home page is a non-interactive Blade view on the existing route, so no Livewire component is needed and no SPA is introduced |
| II. Docker-first | ✅ Pass | Build, tests and CI run in Docker. The one-off icon export runs on the host, as in spec 004; its outputs are committed static files, so nobody needs it to run or test the app |
| III. Couple-scoped privacy | ✅ Pass | Home page runs no queries over couple data and shows only hard-coded fictional sample content. A test proves real user data does not appear (FR-016) |
| IV. Tested behaviour | ✅ Pass | `HomePageTest` is extended for every FR-023 case, and `AppLogoTest` follows the new icon colour. No new authorization surface |
| V. Extensible review categories | ✅ Pass | Home page lists categories from `ReviewCategory::cases()`, so a new category appears without a view change |
| VI. Simplicity | ✅ Pass | No packages, containers, migrations or new routes. Existing ramp is re-tinted instead of adding a semantic token layer |

**Post-design re-check**: ✅ Still passes.

## Project Structure

### Documentation (this feature)

```text
specs/009-site-design-refresh/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   ├── design-tokens.md
│   └── home-page.md
└── tasks.md             # Created by /speckit-tasks
```

### Source Code (repository root)

```text
resources/css/app.css                                  # Ramp, accent, red shift, control overrides
resources/views/
├── welcome.blade.php                                  # Rebuilt home page
├── components/
│   ├── home-cta.blade.php                             # New: hero and end-of-page action
│   ├── star-rating.blade.php                          # Accessible star colours
│   ├── star-rating-input.blade.php                    # Accessible star colours, accent focus ring
│   └── auth-session-status.blade.php                  # Accessible success text
├── layouts/app/header.blade.php                       # Page background
├── layouts/auth/simple.blade.php                      # Page background, logo colour
├── flux/navlist/group.blade.php                       # Accessible muted text
└── pages/
    ├── auth/{forgot-password,verify-email}.blade.php  # Accessible text
    ├── reviews/⚡index.blade.php                      # Accessible prompt icon
    └── settings/{⚡profile,⚡two-factor-setup-modal}.blade.php   # Accessible success text, stone → zinc
app/Enums/ReviewCategory.php                           # New icon() method
public/{favicon.svg,favicon.ico,apple-touch-icon.png}  # Berry background
tests/Feature/{HomePageTest,AppLogoTest}.php
```

**Structure Decision**: Existing Laravel layout. The only new file outside `specs/` is one anonymous Blade component, because the hero and the end of the page need the same guest, signed-in and registration-off branching.

## Complexity Tracking

No violations.
