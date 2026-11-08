# Implementation Plan: Couple Management

**Branch**: `couple-management` | **Date**: 2026-11-07 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/005-couple-management/spec.md`

## Summary

Turn the existing exact-username partner search into a consent-based connect flow:

- A single user can send one couple request at a time to another single user.
- The receiver can accept or reject it.
- Accepting creates a couple of exactly those two users and closes every other pending request
  either of them has sent or received.
- Either partner can end the couple after a confirmation modal. The modal warns that the couple's
  reviews will be permanently deleted.

Membership is stored as a nullable `users.couple_id` that references a new `couples` table.
Because of this, "one couple per user" is guaranteed by the schema itself. Ending a couple is a
single row delete, and cascading foreign keys clear out its data. Business rules live in action
classes that run in transactions. Policies authorise every action. Two Livewire pages handle the
UI: `/partner/search` for single users and `/couple` for users in a couple.

## Technical Context

**Language/Version**: PHP 8.4 (Docker app image). The framework requires ^8.3

**Primary Dependencies**: Laravel 13, Livewire 4, Flux UI 2, Laravel Fortify. No new packages

**Storage**: MySQL in Docker for development. SQLite in tests and CI. The migrations:
- create `couples`
- create `couple_requests`
- add `users.couple_id`

**Testing**: Class-based PHPUnit (`Tests\TestCase`) with Livewire test helpers.
`docker compose exec app composer ci:check` (Pint, PHPStan and the full suite) runs before the PR

**Target Platform**: Linux containers (Docker Compose)

**Project Type**: Server-rendered web application (single Laravel project)

**Performance Goals**: Each action is a handful of indexed queries in one transaction. Two users
can go from single to a couple in under 2 minutes (SC-001)

**Constraints**:
- Never more than one couple per user or more than two members per couple, even when actions
  happen at the same time. This must hold on both MySQL and SQLite (R2).
- No emails and no partner identities in output for anyone outside the couple.
- Ending a couple must delete all of its data (FR-017).

**Scale/Scope**: Tiny user base. The work is:
- 2 migrations
- 2 new models, 1 enum, 5 actions, 1 exception, 2 policies
- 1 new Livewire page and 1 changed page, plus header nav changes

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Evidence |
|-----------|--------|----------|
| I. Laravel & Livewire starter kit | ✅ | Eloquent models, a backed enum, migrations, factories, policies, `app/Actions` classes (the kit's convention) and Livewire single-file pages using Flux (`flux:modal` for the confirmation). No SPA framework |
| II. Docker-first | ✅ | Every command in the quickstart runs through `docker compose exec app` |
| III. Couple-scoped privacy | ✅ | **At most one couple per user** is enforced by the single `couple_id` column. **Two members per couple** is enforced because members are only added through the atomic conditional update in `AcceptCoupleRequest` (R2). **Consent from both** comes from the receiver having to accept, which the receiver-only policy enforces. **Every mutation is authorised** through `CoupleRequestPolicy` and `CouplePolicy` (R5). **Partner identity is never revealed in search** (FR-003). **The disconnect outcome is defined**: all couple data is permanently deleted, the user is warned in the modal, and the cascading FK rule is written down in data-model.md for future review tables (FR-017) |
| IV. Tested behaviour | ✅ | The feature tests in the quickstart cover each story's happy path, every validation refusal, the authorization refusals (non-receiver, non-sender, non-member), concurrency guards (accepting when a user is already in a couple), account deletion, and that no email or partner identity leaks |
| V. Extensible categories | ✅ n/a | No review data yet. Data-model.md requires future review tables to cascade from `couples` |
| VI. Simplicity | ✅ | No packages, queues, websockets or notifications. One FK column, not a pivot table. Requests appear on the user's next page load, with no real-time updates. The first-party `RateLimiter` is used. Redirects happen in `mount()`, not through new middleware |
| Stack constraints | ✅ | Schema changes go through migrations. Validation happens server-side in actions. Output is escaped by Blade |

**Post-design re-check**: ✅ All gates still pass after Phase 1. There are no violations, so
Complexity Tracking is empty.

## Project Structure

### Documentation (this feature)

```text
specs/005-couple-management/
├── plan.md              # This file
├── research.md          # Phase 0: decisions R1–R10
├── data-model.md        # Phase 1: tables, enum, transitions, validation messages
├── quickstart.md        # Phase 1: validation guide
├── contracts/
│   └── ui-routes.md     # Phase 1: routes, component actions, modal, data-test hooks
└── tasks.md             # Phase 2 (/speckit-tasks)
```

### Source Code (repository root)

```text
app/
├── Actions/Couples/
│   ├── SendCoupleRequest.php        # new (R3, R6)
│   ├── AcceptCoupleRequest.php      # new (R2, closes other requests)
│   ├── DeclineCoupleRequest.php     # new
│   ├── CancelCoupleRequest.php      # new
│   └── EndCouple.php                # new (deletes the couple)
├── Enums/CoupleRequestStatus.php    # new
├── Exceptions/CoupleActionException.php  # new: carries the user-facing message (FR-020)
├── Models/
│   ├── Couple.php                   # new
│   ├── CoupleRequest.php            # new
│   └── User.php                     # + couple relations, partner(), isInCouple(), deleting hook
└── Policies/
    ├── CouplePolicy.php             # new: end
    └── CoupleRequestPolicy.php      # new: accept, decline, cancel
database/
├── factories/CoupleFactory.php, CoupleRequestFactory.php   # new
└── migrations/
    ├── 2026_11_07_000000_create_couples_table.php          # couples + users.couple_id
    └── 2026_11_07_000001_create_couple_requests_table.php
resources/views/
├── pages/partner/⚡search.blade.php   # + incoming, outgoing, send/accept/decline/cancel, redirect if coupled
├── pages/couple/⚡show.blade.php      # new: partner card + end-couple modal
└── layouts/app/header.blade.php       # nav item depends on the user's couple state
routes/web.php                         # + /couple (couple.show)
tests/Feature/Couples/
├── SendCoupleRequestTest.php
├── RespondToCoupleRequestTest.php
├── EndCoupleTest.php
└── CouplePagesTest.php
```

**Structure Decision**: This is a single Laravel project that follows the existing layout. The
rules go in `app/Actions/Couples` (alongside `app/Actions/Fortify`). The UI uses Livewire
single-file pages under `resources/views/pages/`, and the tests are class-based PHPUnit tests in
`tests/Feature/Couples/`.

## Complexity Tracking

No constitution violations.
