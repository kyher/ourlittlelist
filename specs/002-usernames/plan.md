# Implementation Plan: Usernames & Partner Search

**Branch**: `002-usernames` | **Date**: 2026-11-06 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/002-usernames/spec.md`

## Summary

Give every user a unique, lowercase username (3–30 characters: letters, numbers, `_` and `.`) that
they choose at registration and can change in profile settings. Existing users without one are
sent to a choose-username page by a `has-username` route middleware. Add a signed-in
**Find your partner** page that does an exact, case-insensitive username lookup. It excludes the
searcher, shows only the username and display name, and is limited to 30 searches per minute per
user. The feature stops at "found". The connect/invite flow is a later feature.

## Technical Context

**Language/Version**: PHP 8.4 (Sail runtime). The framework requires ^8.3

**Primary Dependencies**: Laravel 13, Livewire 4, Flux UI 2, Laravel Fortify. No new packages

**Storage**: MySQL 8.4 (Sail) for development. SQLite in tests and CI. One migration adds
`users.username` (nullable, unique)

**Testing**: Pest via `sail artisan test`. Pint. `sail composer ci:check` before the PR

**Target Platform**: Linux containers (Docker Compose / Sail)

**Project Type**: Server-rendered web application (single Laravel project)

**Performance Goals**: A search returns instantly from a single indexed equality lookup.
Partner found in under 30 s (SC-001)

**Constraints**: No email address or couple/review data in search output or Livewire state.
Behaves identically on MySQL and SQLite (lowercase storage, R1)

**Scale/Scope**: Tiny user base (pairs of users). 1 migration, 1 middleware, 2 new Livewire pages,
3 changed views/actions

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Evidence |
|-----------|--------|----------|
| I. Laravel & Livewire starter kit | ✅ | Extends the kit's Fortify `CreateNewUser`, the `ProfileValidationRules` trait and the profile Livewire page. New pages are Livewire page components. Middleware, migration and factory are idiomatic Laravel |
| II. Docker-first | ✅ | All commands go through Sail (quickstart) |
| III. Couple-scoped privacy | ✅ | No couple data is touched or exposed. Search shows only the username and name, never the email (FR-009). Exact match only, plus rate limiting, prevents user enumeration. Finding a user creates no connection, so consent stays with the future invite feature (FR-013) |
| IV. Tested behaviour | ✅ | Feature and Livewire tests for registration, choosing a username, profile updates and search, covering happy paths, validation errors, the guest redirect, own-account exclusion, no email leak and the rate limit (quickstart) |
| V. Extensible categories | ✅ n/a | No review data |
| VI. Simplicity | ✅ | No search engine or packages. One indexed equality query. First-party `RateLimiter`. Reserved names in a config array |
| Stack constraints | ✅ | Schema change via migration. Server-side validation. Blade escaping for output |

**Post-design re-check**: ✅ All gates still pass after Phase 1. No violations, so Complexity
Tracking is empty.

## Project Structure

### Documentation (this feature)

```text
specs/002-usernames/
├── plan.md              # This file
├── research.md          # Phase 0: decisions R1–R8
├── data-model.md        # Phase 1: users.username, states, config
├── quickstart.md        # Phase 1: validation guide
├── contracts/
│   └── ui-routes.md     # Phase 1: routes, middleware, input and search contracts
└── tasks.md             # Phase 2 (/speckit-tasks)
```

### Source Code (repository root)

```text
app/
├── Concerns/ProfileValidationRules.php          # + usernameRules(), username in profileRules()
├── Actions/Fortify/CreateNewUser.php            # + normalise/validate/persist username
├── Http/Middleware/EnsureUserHasUsername.php    # new (alias has-username)
└── Models/User.php                              # + fillable, docblock, lowercase mutator
bootstrap/app.php                                 # + middleware alias
config/usernames.php                              # new: reserved list
database/
├── migrations/xxxx_add_username_to_users_table.php   # new
└── factories/UserFactory.php                     # + username, withoutUsername()
resources/views/
├── pages/auth/register.blade.php                 # + username field
├── pages/auth/⚡choose-username.blade.php         # new Livewire page
├── pages/partner/⚡search.blade.php               # new Livewire page
├── pages/settings/⚡profile.blade.php             # + username field
└── layouts/app/sidebar.blade.php (+ header.blade.php)  # + "Find your partner" nav item
routes/
├── web.php                                       # + /username, /partner/search, has-username on dashboard
└── settings.php                                  # + has-username on settings groups
tests/Feature/
├── Auth/RegistrationTest.php                     # extended
├── Auth/ChooseUsernameTest.php                   # new
├── Settings/ProfileUpdateTest.php                # extended
└── PartnerSearchTest.php                         # new
```

**Structure Decision**: Single Laravel project following the starter kit layout. New UI uses
Livewire 4 single-file page components under `resources/views/pages/`, the same as the existing
settings pages.

## Complexity Tracking

No violations.
