# Implementation Plan: Application Foundation & Containerised Environment

**Branch**: `001-laravel-docker-setup` | **Date**: 2026-11-06 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/001-laravel-docker-setup/spec.md`

## Summary

Scaffold "Our Little List" from the official Laravel 13 Livewire starter kit (Livewire 4, Flux,
Fortify), using the Laravel installer inside a throwaway Composer container so the host needs
only Docker. Add Laravel Sail with MySQL 8.4 and Mailpit, convert tests to Pest, set the app
name and environment defaults, and write a README covering every developer command. No custom
domain code: the dashboard stays a placeholder.

## Technical Context

**Language/Version**: PHP 8.4 (Sail runtime). The framework requires ^8.3.

**Primary Dependencies**: Laravel 13, Livewire 4, Flux UI 2, Laravel Fortify, Tailwind CSS,
Vite, Laravel Sail

**Storage**: MySQL 8.4 (Sail container, named volume) for development. In-memory SQLite for tests

**Testing**: Pest (converted from the kit's PHPUnit suite) via `sail artisan test`. Pint for
style

**Target Platform**: Linux containers (Docker Compose v2) on Linux, macOS or Windows with WSL2
hosts

**Project Type**: Server-rendered web application (single Laravel project)

**Performance Goals**: Test suite under 2 minutes (SC-004). Register to dashboard under 1 minute
of user time (SC-002)

**Constraints**: Nothing on the host except Docker. Ports configurable through `.env`. No real
email in development

**Scale/Scope**: Local development only. Expected production scale is tiny (pairs of users).
About 10 kit pages

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Evidence |
|-----------|--------|----------|
| I. Laravel & Livewire starter kit | ✅ | Scaffolded from the official kit (`main`, Laravel 13). Auth via the kit's Fortify flows. No SPA |
| II. Docker-first | ✅ | Sail compose. Fresh-clone install via a container. All commands run through `sail` (R2, R3) |
| III. Couple-scoped privacy | ✅ n/a | No couple data in this feature. The User model is the base for it |
| IV. Tested behaviour | ✅ | Kit's auth and settings test suite (Pest) runs in containers. Gate: tests pass |
| V. Extensible categories | ✅ n/a | No review data in this feature |
| VI. Simplicity | ✅ | First-party tools only (Sail, Fortify, Pint). No Redis or extra services (R3) |
| Stack constraints | ✅ | Containerised DB, migrations only, `.env` ignored, `.env.example` maintained |

**Post-design re-check**: ✅ All gates still pass after Phase 1. No violations, so Complexity
Tracking is empty.

## Project Structure

### Documentation (this feature)

```text
specs/001-laravel-docker-setup/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── ui-routes.md
└── tasks.md             # created by /speckit-tasks
```

### Source Code (repository root)

```text
app/
├── Actions/Fortify/          # CreateNewUser, ResetUserPassword (kit)
├── Concerns/                 # validation rule traits (kit)
├── Livewire/Actions/         # Logout (kit)
├── Models/User.php
└── Providers/                # AppServiceProvider, FortifyServiceProvider
resources/views/
├── pages/auth/               # login, register, reset, verify, 2FA (kit)
├── pages/settings/           # profile, security, appearance (kit)
└── dashboard.blade.php       # placeholder, greets user by name
routes/{web,settings,console}.php
database/{migrations,factories,seeders}/
tests/
├── Feature/Auth/             # Pest: auth flows
├── Feature/Settings/         # Pest: profile/security
└── Pest.php
compose.yaml                  # Sail: laravel.test, mysql, mailpit
.env.example                  # APP_NAME, DB/MAIL defaults, port vars
README.md                     # developer command contract
```

**Structure Decision**: A single Laravel project at the repository root, using the starter
kit's layout unchanged. Spec Kit folders (`.specify/`, `.claude/`, `specs/`) sit alongside it.

## Complexity Tracking

No constitution violations. Nothing to justify.
