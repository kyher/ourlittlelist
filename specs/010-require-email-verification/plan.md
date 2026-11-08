# Implementation Plan: Require Email Verification

**Branch**: `implement-mustverifyemail` | **Date**: 2026-11-08 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/010-require-email-verification/spec.md`

## Summary

Turn on the email verification that the app's routes already ask for. `User` gains the `MustVerifyEmail` contract, which activates the existing `verified` middleware, Fortify's verification routes and the sending of a link on registration. The rest of the work closes the gaps enforcement would otherwise leave: re-checking on Livewire actions from open pages, sending a link when the address changes, limiting link sends from the profile page, not failing registration when mail is down, and giving people on the "verify your email" page a way to correct their address. No schema change, package or container is needed.

## Technical Context

**Language/Version**: PHP 8.3+

**Primary Dependencies**: Laravel 13, Fortify 1.37, Livewire 4, Flux UI

**Storage**: Existing `users.email_verified_at` column; no migration

**Testing**: PHPUnit class-based feature tests in Docker (`docker compose exec app composer ci:check`); CI uses SQLite and the `array` mailer

**Target Platform**: Dockerised Laravel web app

**Project Type**: Web application (single Laravel project)

**Performance Goals**: None specific; one extra check per request on an already-loaded user

**Constraints**: Mail is sent synchronously because no queue worker runs in `compose.yaml`; links expire after 60 minutes (framework default)

**Scale/Scope**: One model, one middleware registration, one exception renderer, two Blade pages, tests

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Starter kit conventions | Pass | Uses Fortify's verification routes and the kit's `verify-email` and profile pages; nothing is re-implemented |
| II. Docker-first | Pass | No environment change; mail is captured by the existing Mailpit container |
| III. Couple-scoped privacy | Pass | No new data access. Verification only narrows who can reach couple data, and couples and reviews are not modified (FR-014) |
| IV. Tested behaviour | Pass | Feature tests for each requirement; the two tests that protect the old optional behaviour are replaced |
| V. Extensible categories | N/A | Reviews are untouched |
| VI. Simplicity | Pass | First-party features only. No queue worker, migration or new tables |

**Post-design re-check**: Pass. The design adds no packages, tables or services, and Complexity Tracking stays empty.

## Project Structure

### Documentation (this feature)

```text
specs/010-require-email-verification/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── ui-routes.md
└── tasks.md             # Created by /speckit-tasks
```

### Source Code (repository root)

```text
app/
├── Models/User.php                              # Implement MustVerifyEmail; tolerate mail transport failure
└── Providers/FortifyServiceProvider.php         # Add EnsureEmailIsVerified to Livewire persistent middleware
bootstrap/app.php                                # Send expired or tampered links to the verify page
resources/views/pages/
├── auth/verify-email.blade.php                  # Show address, link to profile, invalid-link message
└── settings/⚡profile.blade.php                  # Send link on email change; rate-limit sends
README.md                                        # Note verification mail and the live mail requirement
tests/Feature/
├── Auth/EmailVerificationTest.php               # Enforcement, links, resend, failure handling, open pages
├── Auth/RegistrationTest.php                    # Replace the "without verifying" test; link is sent
└── Settings/ProfileUpdateTest.php               # Email change sends a link; limits; unchanged email sends nothing
```

**Structure Decision**: Single Laravel project, changing existing files only.

## Complexity Tracking

No violations to justify.
