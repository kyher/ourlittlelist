# Implementation Plan: Initial UI Changes (Remove Template Boilerplate & Horizontal Navigation)

**Branch**: `initial-ui-changes` | **Date**: 2026-11-06 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/003-initial-ui-changes/spec.md`

## Summary

Switch the authenticated layout from the starter kit's sidebar to its header (top-bar) layout, trimmed to logo, "Find your partner" and a user menu. Remove the placeholder dashboard and point every default redirect at the partner search page. Replace the Laravel welcome page with a minimal Our Little List landing page and delete starter-kit leftovers (repo/docs links, placeholder graphics, unused layouts and icons). Purely a view/routing change: no data model work. See [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.3+, Laravel 13

**Primary Dependencies**: Livewire 4, Flux UI 2 (`flux:header`, `flux:navbar`, `flux:profile`, mobile `flux:sidebar`), Laravel Fortify, Tailwind CSS via Vite

**Storage**: N/A (no schema changes)

**Testing**: PHPUnit class-based feature tests, run in Docker (`docker compose exec app php artisan test`; full gate `composer ci:check`)

**Target Platform**: Web, desktop and phone-width browsers

**Project Type**: Server-rendered Laravel + Livewire web application

**Performance Goals**: No change; the new landing page drops ~190 lines of inlined CSS/SVG

**Constraints**: Must reuse starter-kit/Flux components; existing auth, passkey, two-factor and settings flows must keep working

**Scale/Scope**: ~6 views edited, ~9 files deleted, 1 config value, ~6 test files touched

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Laravel & Livewire starter kit conventions | ✅ Pass | Uses the kit's own header layout and Flux components; auth flows untouched apart from redirect target |
| II. Docker-first | ✅ Pass | No environment changes; validation commands run via `docker compose exec` |
| III. Couple-scoped privacy | ✅ Pass | No couple data touched; partner search keeps its existing middleware (`auth`, `verified`, `has-username`) |
| IV. Tested behaviour | ✅ Pass | New `NavigationTest`, extended `HomePageTest`, redirect assertions updated; `DashboardTest` removed with the feature it tested |
| V. Extensible review categories | ✅ N/A | No review data |
| VI. Simplicity | ✅ Pass | Removes code; no new packages, routes or services |

**Post-design re-check**: ✅ Still passes. The design only deletes or trims starter-kit views and adds tests.

## Project Structure

### Documentation (this feature)

```text
specs/003-initial-ui-changes/
├── plan.md              # This file
├── research.md          # Phase 0 decisions (R1–R8)
├── data-model.md        # No data changes
├── quickstart.md        # Validation guide
├── contracts/
│   └── ui-routes.md     # Route, redirect and layout contract
└── tasks.md             # Created by /speckit-tasks
```

### Source Code (repository root)

```text
config/
└── fortify.php                                   # EDIT: 'home' => '/partner/search'

routes/
└── web.php                                       # EDIT: remove dashboard route

resources/views/
├── welcome.blade.php                             # REWRITE: minimal Our Little List landing page
├── dashboard.blade.php                           # DELETE
├── layouts/
│   ├── app.blade.php                             # EDIT: use app.header + flux:main container
│   ├── app/header.blade.php                      # EDIT: strip Dashboard/Search/Repo/Docs; logo → partner.search
│   ├── app/sidebar.blade.php                     # DELETE
│   ├── auth/card.blade.php                       # DELETE (unused)
│   └── auth/split.blade.php                      # DELETE (unused, random-quote panel)
├── components/
│   ├── desktop-user-menu.blade.php               # REPLACE with user-menu.blade.php (flux:profile)
│   ├── placeholder-pattern.blade.php             # DELETE
│   └── passkey-verify.blade.php                  # EDIT: '/dashboard' fallback → '/partner/search'
├── flux/icon/
│   ├── layout-grid.blade.php                     # DELETE
│   ├── folder-git-2.blade.php                    # DELETE
│   ├── book-open-text.blade.php                  # DELETE
│   └── chevrons-up-down.blade.php                # DELETE
└── pages/
    ├── auth/⚡choose-username.blade.php           # EDIT: dashboard → partner.search (2 places)
    └── settings/⚡profile.blade.php               # EDIT: redirectIntended default → partner.search

tests/Feature/
├── DashboardTest.php                             # DELETE
├── NavigationTest.php                            # NEW
├── HomePageTest.php                              # EXTEND
└── Auth/{Authentication,Registration,EmailVerification,ChooseUsername}Test.php  # EDIT redirect assertions
```

**Structure Decision**: Existing single Laravel application layout; changes confined to `resources/views`, `routes/web.php`, `config/fortify.php` and `tests/Feature`.

## Complexity Tracking

No constitution violations to justify.
