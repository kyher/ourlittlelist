# Our Little List Constitution

## Core Principles

### I. Laravel & Livewire Starter Kit Conventions

The application MUST be a Laravel application bootstrapped from the official Laravel
Livewire starter kit. All interactive UI MUST be built with Livewire components (and the
starter kit's Blade/Flux UI conventions); a separate SPA framework (React, Vue, Inertia)
MUST NOT be introduced. Authentication, registration, and profile management MUST build on
the starter kit's provided flows rather than being re-implemented. Code MUST follow
idiomatic Laravel structure (Eloquent models, policies, form requests/validation,
migrations, factories) unless a deviation is justified in the plan.

Rationale: one well-trodden stack keeps the codebase small, familiar, and easy to upgrade.

### II. Docker-First Environment

Local development, testing, and CI MUST run inside Docker containers defined in the
repository (Docker Compose, e.g. Laravel Sail or an equivalent compose setup). A new
contributor MUST be able to start the full stack (app, database, any queue/cache services)
with documented `docker compose` commands and no host-installed PHP, Composer, or Node.
Artisan, Composer, npm, and test commands MUST be documented as run through the containers.

Rationale: identical environments for both developers and deployment remove
"works on my machine" drift.

### III. Couple-Scoped Privacy (NON-NEGOTIABLE)

The core domain unit is a couple: exactly two users who have connected with each other.
- A user MUST belong to at most one active couple at a time, and a couple MUST contain no
  more than two users.
- Connecting MUST require consent from both users (e.g. an invite that the partner accepts);
  a user MUST NOT be added to a couple unilaterally.
- Reviews and reviewed items MUST be visible only to members of the owning couple. Every
  query, Livewire action, and route that reads or mutates couple data MUST be authorized via
  Laravel policies/gates and scoped to the current user's couple.
- A user MAY edit or delete only their own reviews; their partner's reviews are read-only
  to them.
- Disconnecting from a couple MUST have a defined, specified outcome for existing data
  before it is implemented.

Rationale: this is a private space for two people; any cross-couple data leak is a
critical defect.

### IV. Tested Behaviour

Every user-facing feature MUST ship with automated tests (Pest or PHPUnit) run inside
Docker. At minimum:
- Feature/Livewire component tests covering the primary happy path and validation errors.
- Authorization tests proving that a user outside the couple cannot view or modify the
  couple's data, and that a partner cannot edit the other partner's reviews.
The test suite MUST pass before a change is merged.

Rationale: authorization rules are easy to break silently; tests are the guardrail.

### V. Extensible Review Categories

Users review different kinds of things: initially films, series, books, food, and
restaurants. The data model MUST treat the category as data or a well-defined type
(e.g. an enum or category table with shared review fields), so that adding a new category
does not require duplicating models, tables, or Livewire components. Category-specific
attributes (e.g. author for books, location for restaurants) MAY exist but MUST be
isolated so that shared behaviour (rating, written review, date, reviewer) stays common.
Both partners MUST be able to review the same item independently, and the UI MUST make
it easy to compare both partners' reviews of that item.

Rationale: the value of the app is the shared, comparable list across many kinds of things.

### VI. Simplicity

Build the smallest thing that satisfies the current spec (YAGNI). New packages, services,
or infrastructure (search engines, websockets, external APIs, extra containers) MUST be
justified in the feature plan by a concrete requirement. Prefer Laravel first-party
features over third-party packages.

Rationale: a two-person app does not need enterprise complexity.

## Technology Stack & Constraints

- Backend: Laravel (current stable major), PHP version as required by that release.
- Frontend: Livewire starter kit (Livewire, Blade, Flux UI, Tailwind CSS, Vite).
- Runtime: Docker / Docker Compose for all environments.
- Database: a relational database run as a container (MySQL, MariaDB, or PostgreSQL),
  with all schema changes made through Laravel migrations; SQLite is acceptable for tests.
- Testing: Pest (preferred) or PHPUnit, executed in containers.
- Code style: Laravel Pint MUST pass.
- Secrets MUST live in `.env` files that are not committed; `.env.example` MUST be kept
  current.
- User-entered content MUST be validated server-side and escaped on output (Blade defaults).

## Development Workflow & Quality Gates

- Features follow the Spec Kit flow: specify → (clarify) → plan → tasks → implement.
- Each plan MUST include a Constitution Check confirming compliance with Principles I–VI,
  and MUST justify any deviation in its complexity tracking.
- Before merge: tests pass, Pint passes, migrations run cleanly from scratch in Docker,
  and couple-scoping authorization is covered for any new data access.
- Work happens on feature branches; `main` stays deployable.

## Governance

This constitution supersedes other project practices. Amendments are made via
`/speckit-constitution`, documented in the Sync Impact Report, and committed with a
message describing the change. Versioning follows semantic versioning: MAJOR for removing
or redefining a principle, MINOR for adding a principle or materially expanding guidance,
PATCH for clarifications and wording. All specs, plans, and reviews MUST verify compliance;
unjustified complexity or any breach of Principle III blocks a change.

**Version**: 1.0.0 | **Ratified**: 2026-11-06 | **Last Amended**: 2026-11-06
