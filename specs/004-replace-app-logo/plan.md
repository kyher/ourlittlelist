# Implementation Plan: Replace the Laravel Logo

**Branch**: `app-logo` | **Date**: 2026-11-07 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/004-replace-app-logo/spec.md`

## Summary

Swap the Laravel artwork for a placeholder logo of two linked hearts (issue #13). The in-page logo is a single-colour inline SVG in the existing `app-logo-icon` component, so every caller (top bar, phone slide-out, home page, auth pages) updates at once and keeps following light/dark colours. The three public icon files are regenerated from the same artwork on a dark rounded square. No new dependencies; PNG/ICO are produced once with the Playwright Chromium already on the machine. See [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.3+, Laravel 13; SVG

**Primary Dependencies**: Blade components, Flux UI (unchanged). One-off export uses existing Playwright Chromium + Node (not committed)

**Storage**: N/A

**Testing**: PHPUnit class-based feature test (`tests/Feature/AppLogoTest.php`), run in Docker

**Target Platform**: Web browsers (desktop and mobile), iOS/Android home-screen shortcuts

**Project Type**: Server-rendered Laravel + Livewire web application

**Performance Goals**: N/A (new SVG is smaller than the old one)

**Constraints**: Single colour via `currentColor`; legible at 16px; unique mask ids when rendered twice on one page; no new packages

**Scale/Scope**: 1 Blade component rewritten, 3 public files replaced, 1 test file added

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Laravel & Livewire starter kit conventions | ✅ Pass | Keeps the kit's `app-logo` / `app-logo-icon` components and call sites; only artwork changes |
| II. Docker-first | ✅ Pass | Tests and CI run in Docker. The one-off image export runs on the host because it needs a browser engine; its outputs are committed static files, so nobody needs it to run or test the app |
| III. Couple-scoped privacy | ✅ N/A | No data access |
| IV. Tested behaviour | ✅ Pass | `AppLogoTest` covers rendered pages and the public icon files |
| V. Extensible review categories | ✅ N/A | |
| VI. Simplicity | ✅ Pass | No new packages, containers or build steps |

**Post-design re-check**: ✅ Still passes.

## Project Structure

### Documentation (this feature)

```text
specs/004-replace-app-logo/
├── plan.md              # This file
├── research.md          # R1–R5 decisions
├── data-model.md        # The four artwork files
├── quickstart.md        # Validation guide
├── contracts/
│   └── logo.md          # Component + public file contract
└── tasks.md             # Created by /speckit-tasks
```

### Source Code (repository root)

```text
resources/views/components/
└── app-logo-icon.blade.php      # REWRITE: two-hearts SVG, currentColor, data-logo marker, unique mask id

public/
├── favicon.svg                  # REPLACE: hearts on #262626 rounded square
├── favicon.ico                  # REPLACE: 32×32 PNG-in-ICO
└── apple-touch-icon.png         # REPLACE: 180×180 PNG

tests/Feature/
└── AppLogoTest.php              # NEW
```

**Structure Decision**: Existing single Laravel app. No changes to `app-logo.blade.php`, `welcome.blade.php`, `layouts/auth/simple.blade.php` or `partials/head.blade.php`.

## Complexity Tracking

No constitution violations to justify.
