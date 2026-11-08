# Implementation Plan: Admin Users and System Totals

**Branch**: `add-admin` | **Date**: 2026-11-08 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/008-add-admin/spec.md`

## Summary

Add a separate kind of account, the admin, which is created only from the command line and can
see three totals.

- **Creation**: `php artisan admin:create {email}` prompts twice for a hidden password and applies
  the regular password rules (R5).
- **Separation**: an `admins` table, an `admin` session guard, and a dedicated session cookie for
  `/admin` paths. The cookie is what keeps the two logins independent, because Fortify's logout
  invalidates the whole session (R1, R2).
- **Login**: a small controller and a plain Blade form under `/admin`, mirroring the starter kit's
  login page and rate limit. Fortify supports only one guard (R3, R4).
- **Totals**: one page showing `COUNT` results for users, couples and reviews, and nothing else (R7).

One new table. No new packages.

## Technical Context

**Language/Version**: PHP 8.4 (Docker app image)

**Primary Dependencies**: Laravel 13, Livewire 4, Flux UI 2 (free), Fortify, Laravel Prompts (ships
with the framework). No new packages

**Storage**: MySQL 8.4 in Docker for development; SQLite in tests and CI. One new `admins` table

**Testing**: Class-based PHPUnit (`Tests\TestCase`), run with
`docker compose exec app php artisan test`; `composer ci:check` before a PR

**Target Platform**: Linux containers (Docker Compose)

**Project Type**: Server-rendered web application (single Laravel project) with one console command

**Performance Goals**: Three `COUNT` queries per page load; totals visible within 5 seconds of
signing in (SC-005)

**Constraints**:
- Admin and regular authentication share no accounts, guards or session cookies (FR-009 to FR-011).
- The password is never accepted as a command argument and never printed (FR-002).
- The admin area exposes aggregate totals only (FR-019).
- Admin login is limited to 5 attempts per minute per email and IP, as regular login is (FR-013).

**Scale/Scope**: A handful of admins. One command, one model and table, one middleware, two
controllers, two views, and their tests

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Laravel & Livewire conventions | ⚠️ Justified | Admin login is a small custom controller rather than a Fortify flow, because Fortify is bound to one guard and the spec requires complete separation. See Complexity Tracking. Everything else is idiomatic: Eloquent model, migration, factory, session guard, and a Blade form with Flux inputs like the existing login page. No interactive UI is added, so no Livewire components are needed (R4) |
| II. Docker-first | ✅ | No new services. The command, tests and checks run in the `app` container |
| III. Couple-scoped privacy | ✅ | Admins see three numbers from `COUNT` queries. No review, item or couple row is read out or shown, and nothing is broken down per couple (FR-019). Admins have no route to couple pages (FR-011). Tests prove both. These counts are the only cross-couple queries, and they are limited to the `auth:admin` dashboard |
| IV. Tested behaviour | ✅ | Feature tests cover the command's paths, both login directions, redirects, throttling, logout, correct totals and the absence of personal data |
| V. Extensible review categories | ✅ | Not affected. The review total counts every category |
| VI. Simplicity | ✅ | No packages. Totals are computed on request, with no stats table or cache. The login page reuses the existing auth layout, and no admin management UI is built |

**Post-design re-check**: ✅ All gates still pass after Phase 1. The one deviation from Principle I
is justified below.

## Project Structure

### Documentation (this feature)

```text
specs/008-add-admin/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   ├── admin-command.md
│   └── admin-routes.md
└── tasks.md             # Created by /speckit-tasks
```

### Source Code (repository root)

```text
app/
├── Console/Commands/CreateAdmin.php                     # new: admin:create
├── Http/
│   ├── Controllers/Admin/
│   │   ├── AuthenticatedSessionController.php           # new: login form, login, logout
│   │   └── DashboardController.php                      # new: the three totals
│   └── Middleware/UseAdminSessionCookie.php             # new: selects the session cookie (R2)
├── Models/Admin.php                                     # new
└── Providers/FortifyServiceProvider.php                 # add the admin-login rate limiter

bootstrap/app.php                                        # register middleware; guest redirect (R6)
config/auth.php                                          # admin guard and provider
database/
├── factories/AdminFactory.php                           # new
└── migrations/2026_11_08_000000_create_admins_table.php # new
resources/views/pages/admin/
├── login.blade.php                                      # new
└── dashboard.blade.php                                  # new
routes/
├── admin.php                                            # new
└── web.php                                              # require admin.php
README.md                                                # document admin:create via Docker
tests/Feature/Admin/
├── CreateAdminCommandTest.php                           # new
├── AdminAuthenticationTest.php                          # new
└── AdminDashboardTest.php                               # new
```

**Structure Decision**: Single Laravel project, following the existing layout. Admin routes get
their own file, as settings routes do, and admin views sit in the `pages::` namespace beside the
existing plain Blade auth pages.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Principle I: admin login does not build on the starter kit's Fortify flow | The spec requires admin login to be completely separate from regular login, and `fortify.guard` is a single global value | A second Fortify registration is not supported. An `is_admin` flag on `users` with the Fortify login would share accounts and login, which the spec rules out (R1, R3) |
