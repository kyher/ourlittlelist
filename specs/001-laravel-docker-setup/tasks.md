---

description: "Task list for Application Foundation & Containerised Environment"
---

# Tasks: Application Foundation & Containerised Environment

**Input**: Design documents from `/specs/001-laravel-docker-setup/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/ui-routes.md, quickstart.md

**Tests**: Required. The spec (US3, SC-003) and constitution Principle IV call for automated tests
of every account flow. The starter kit ships most of them. The tasks below convert them to Pest
and add tests for the project-specific behaviour.

**Organization**: Tasks are grouped by user story so each story can be implemented and tested on
its own.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: The user story the task belongs to (US1, US2, US3)
- Paths are relative to the repository root. This is a single Laravel project at the root (see
  plan.md).
- `sail` means `./vendor/bin/sail`. Never use host `php`, `composer` or `npm` (constitution
  Principle II).

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Scaffold the starter kit app into the repo using only Docker (research R1, R2)

- [X] T001 Confirm the repo root contains only `.git/`, `.claude/`, `.specify/`, `specs/` (plus
  dotfiles), so moving the scaffold in will not overwrite project files. Create a scratch
  directory outside the repo (e.g. `$TMPDIR/oll-scaffold`) to hold the generated app
- [X] T002 Scaffold the app in a throwaway container into the scratch directory:
  `docker run --rm -u "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer -v "<scratch>:/app" -w /app laravelsail/php84-composer:latest bash -c "composer global require laravel/installer && /tmp/composer/vendor/bin/laravel new ourlittlelist --livewire --pest --database=mysql --no-interaction"`.
  If the installer prompts for anything not covered by flags (e.g. WorkOS, npm install, git init),
  pick: Laravel's built-in auth (not WorkOS), Pest, no git init. If `--database=mysql` triggers a
  migration attempt that fails because no DB exists, that is expected; migrations run in T009.
  Verify `<scratch>/ourlittlelist/composer.json` requires `laravel/framework` ^13 and
  `livewire/livewire` ^4, and that `tests/Pest.php` exists
- [X] T003 Move all scaffolded files, including dotfiles (`.env.example`, `.gitignore`,
  `.editorconfig`, `.gitattributes`, etc.), from `<scratch>/ourlittlelist/` into the repo root
  without touching `.git/`, `.claude/`, `.specify/` or `specs/`. Delete the scaffold's own `.git/`
  if the installer created one. Delete the scratch directory afterwards
- [X] T004 Install Sail with MySQL and Mailpit (research R3) through a container:
  `docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest php artisan sail:install --with=mysql,mailpit`.
  Confirm `compose.yaml` exists at the repo root with services `laravel.test`, `mysql` and
  `mailpit`, and that `laravel.test` builds the PHP 8.4 runtime
  (`vendor/laravel/sail/runtimes/8.4`)
- [X] T005 Check `.gitignore` at the repo root: it MUST ignore `/vendor`, `/node_modules`, `.env`,
  `/public/build` and `/public/hot`, and MUST NOT ignore `.specify/`, `.claude/` or `specs/`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Environment config and a running stack. Every story depends on this.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T006 Edit `.env.example` (research R6, FR-008, FR-009, FR-011). Set
  `APP_NAME="Our Little List"`, `APP_URL=http://localhost`, `DB_CONNECTION=mysql`,
  `DB_HOST=mysql`, `DB_PORT=3306`, `DB_DATABASE=ourlittlelist`, `DB_USERNAME=sail`,
  `DB_PASSWORD=password`, `MAIL_MAILER=smtp`, `MAIL_HOST=mailpit`, `MAIL_PORT=1025`,
  `MAIL_FROM_ADDRESS="hello@ourlittlelist.test"`. Keep `SESSION_DRIVER=database`,
  `CACHE_STORE=database` and `QUEUE_CONNECTION=database`. Add commented port overrides
  `# APP_PORT=80`, `# VITE_PORT=5173`, `# FORWARD_DB_PORT=3306` and
  `# FORWARD_MAILPIT_DASHBOARD_PORT=8025`. Leave `APP_KEY` empty. It MUST contain no real secrets
- [X] T007 Copy `.env.example` to `.env`. Confirm `.env` is git-ignored (`git check-ignore .env`)
- [X] T008 Start the stack with `./vendor/bin/sail up -d` and wait until `sail ps` shows `mysql`
  as healthy. Run `sail artisan key:generate`
- [X] T009 Run `sail artisan migrate`. Confirm the tables `users`, `password_reset_tokens`,
  `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches` and `failed_jobs` exist
  (`sail artisan db:show`), and that `users` has the 2FA columns from data-model.md
- [X] T010 Run `sail npm install` then `sail npm run build`. Confirm `public/build/manifest.json`
  exists

**Checkpoint**: Stack running, schema migrated, assets built. User stories can begin.

---

## Phase 3: User Story 1 - Developer starts the full app from a fresh clone (Priority: P1) 🎯 MVP

**Goal**: A machine with only Docker can go from clone to a working, styled home page by
following the README, and data persists across restarts.

**Independent Test**: quickstart.md V1, V3 and V5.

### Tests for User Story 1

- [X] T011 [P] [US1] Replace the kit's example test in `tests/Feature/ExampleTest.php` with a Pest
  test that a GET to `/` returns 200 and the response contains `Our Little List` (FR-008)

### Implementation for User Story 1

- [X] T012 [US1] Check `compose.yaml`. The `laravel.test` service MUST `depends_on` `mysql` and
  `mailpit`, and the `mysql` service MUST have a healthcheck (Sail's default) and a named volume
  (`sail-mysql`) for persistence (FR-006). Ports MUST use the `${APP_PORT:-80}`,
  `${VITE_PORT:-5173}`, `${FORWARD_DB_PORT:-3306}` and `${FORWARD_MAILPIT_DASHBOARD_PORT:-8025}`
  variables (FR-011). Change nothing else
- [X] T013 [US1] Make the Vite dev server reachable from the host for hot reload inside Sail. In
  `vite.config.js`, if the kit does not already do this, add
  `server: { host: '0.0.0.0', hmr: { host: 'localhost' }, watch: { ignored: ['**/storage/framework/views/**'] } }`.
  Run `sail npm run dev`, edit `resources/views/welcome.blade.php` (or the kit's equivalent home
  view), and confirm the change appears on refresh. Revert the test edit afterwards
- [X] T014 [US1] Replace `README.md` with project documentation. Include a one-paragraph
  description of Our Little List, the prerequisites (Docker with Compose only), and every command
  in the "Developer command contract" table of `specs/001-laravel-docker-setup/contracts/ui-routes.md`,
  in order: first-time `docker run … composer install`, `cp .env.example .env`, `sail up -d`,
  `key:generate`, `migrate`, `npm install`, `npm run build`/`dev`, stop, test, Pint check/fix,
  `migrate:fresh`, and the Mailpit URL. Add a tip for an optional `sail` shell alias. Add a
  "Changing ports" section listing `APP_PORT`, `VITE_PORT`, `FORWARD_DB_PORT` and
  `FORWARD_MAILPIT_DASHBOARD_PORT`. Add a "Troubleshooting" note covering an unstyled page
  (run the asset build) and a database that isn't ready (wait for `sail ps` to show healthy)
- [X] T015 [US1] Check persistence (quickstart V3). Register an account (or run
  `sail artisan tinker` to create one), run `sail stop && sail up -d`, and confirm the user
  still exists. Check the port override (quickstart V5) with `APP_PORT=8080`, then restore `.env`

**Checkpoint**: The app runs from a fresh clone using only Docker. US1 is complete.

---

## Phase 4: User Story 2 - Visitor creates an account and signs in (Priority: P2)

**Goal**: The kit's registration, login, logout, password reset and profile flows work in the
containerised environment. Dashboard greets the user by name.

**Independent Test**: quickstart.md V2.

### Tests for User Story 2

- [X] T016 [P] [US2] Update `tests/Feature/DashboardTest.php` (Pest). A guest GET to `/dashboard`
  redirects to `/login` (FR-005). An authenticated user created with
  `User::factory()->create(['name' => 'Sam'])` sees 200 and the text `Sam` (FR-004)
- [X] T017 [P] [US2] Add a test to `tests/Feature/Auth/RegistrationTest.php`: registering with an
  email that already exists fails with a validation error on `email` (data-model: email
  **unique**) and creates no second user
- [X] T018 [P] [US2] Add a test to `tests/Feature/Auth/PasswordResetTest.php` (or a new
  `tests/Feature/MailConfigTest.php`): with `Notification::fake()`, requesting a reset for an
  existing user sends `Illuminate\Auth\Notifications\ResetPassword`. If the kit's existing test
  already covers this, skip and note it in the task
  — *Done: already covered by the kit's `reset password link can be requested` test; no new test added*

### Implementation for User Story 2

- [X] T019 [US2] Update the dashboard view (`resources/views/dashboard.blade.php`, or wherever
  `routes/web.php` points the `dashboard` route) so the existing placeholder content includes a
  greeting such as "Welcome, {{ auth()->user()->name }}". Use Blade escaping (`{{ }}`), not
  `{!! !!}`. Keep the kit's layout and Flux components
- [X] T020 [US2] Check that `app/Models/User.php` does NOT implement `MustVerifyEmail` (email
  verification is optional, per research R5) and that `config/fortify.php` features include
  `registration`, `resetPasswords`, `updateProfileInformation`, `updatePasswords` and
  `twoFactorAuthentication`. Change only what is missing
  — *Done: the kit now ships `User implements MustVerifyEmail` with `verified` middleware on the dashboard; removed `MustVerifyEmail` and replaced the kit test `unverified users are redirected to the email verification prompt` with `unverified users can use the app without verifying`. Profile and password updates are handled by the kit's Livewire settings pages, not Fortify features*
- [X] T021 [US2] Make sure the app name renders from config in the layouts. Search `resources/views`
  for any hard-coded "Laravel" brand text (e.g. in `components/app-logo*.blade.php`) and replace
  it with `{{ config('app.name') }}`. Keep the kit's logo icon
- [X] T022 [US2] Walk through the manual flow in quickstart.md V2 against the running stack
  (`http://localhost`), including opening the reset email in Mailpit at `http://localhost:8025`
  (FR-007). Fix any failure before continuing

**Checkpoint**: All account flows work end to end. US2 is complete.

---

## Phase 5: User Story 3 - Developer runs quality checks in the containers (Priority: P3)

**Goal**: The test suite, Pint and a from-scratch migration all pass inside the containers.

**Independent Test**: quickstart.md V4.

- [X] T023 [US3] Check `phpunit.xml`: `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`,
  `MAIL_MAILER=array`, `SESSION_DRIVER=array`, `CACHE_STORE=array` and `QUEUE_CONNECTION=sync`
  (research R4). Add any that are missing so tests never touch the MySQL dev database
- [X] T024 [US3] Confirm every file under `tests/` uses Pest syntax (`test()`/`it()`, no PHPUnit
  classes except `tests/TestCase.php`), and that `tests/Pest.php` applies `RefreshDatabase` to
  `Feature`. Convert any remaining PHPUnit-style test files by hand
- [X] T025 [US3] Run `sail artisan test`. All tests MUST pass, and the duration MUST be under
  2 minutes (SC-004). The suite MUST include passing tests for registration, authentication
  (login/logout), password reset, profile update and account deletion (SC-003). Add any missing
  test to the matching file in `tests/Feature/Auth/` or `tests/Feature/Settings/`
- [X] T026 [US3] Run `sail bin pint` then `sail bin pint --test`. The second run MUST report no
  violations
- [X] T027 [US3] Run `sail artisan migrate:fresh`. It MUST complete without errors. Then register
  a user via the UI to confirm the app still works

**Checkpoint**: Quality gates are green. US3 is complete.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T028 [P] Remove kit leftovers that conflict with the project, if any: unused `.env.example`
  entries for services we don't run (e.g. `REDIS_*`, `AWS_*`) MAY be left, but must not be set to
  anything that implies they're required. Do NOT remove 2FA or appearance settings (research R5)
- [X] T029 [P] Add or confirm an `.editorconfig` and a `.gitattributes` from the kit at the
  repo root (no changes expected)
- [X] T030 Run the full quickstart.md (V1–V5) from a fresh clone in a temporary directory:
  `git clone` the repo locally and use a different `APP_PORT`/`FORWARD_DB_PORT` so it doesn't
  clash with the running stack. Then stop that clone with `sail down -v` and delete it
- [X] T031 Final gate: `sail artisan test` and `sail bin pint --test` both pass, and
  `git status` shows no `.env`, `vendor/`, `node_modules/` or `public/build/` files staged

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: T001 → T002 → T003 → T004 → T005, strictly in order (each step operates on
  the previous one's output)
- **Foundational (Phase 2)**: depends on Phase 1. T006 → T007 → T008 → T009, and T010 after T008
- **US1 (Phase 3)**: depends on Phase 2
- **US2 (Phase 4)**: depends on Phase 2. It doesn't depend on US1 (it uses the running stack, not
  the README)
- **US3 (Phase 5)**: depends on Phase 2. T025 should run after the US1 and US2 test tasks so
  their tests are included
- **Polish (Phase 6)**: depends on all stories. T030 depends on T014 (README)

### Within Each Story

- Write or update tests first (T011, T016–T018), then implement (T019–T021) until they pass
- Manual verification tasks (T015, T022) come last in their phase

### Parallel Opportunities

- T011 can run alongside T012–T014 (different files)
- T016, T017 and T018 touch different test files: [P]
- After Phase 2, US1 (README, compose and Vite checks) and US2 (dashboard, auth tests) can proceed
  in parallel because they touch different files
- T028 and T029 are independent [P]

---

## Parallel Example: User Story 2

```text
# Write all US2 tests together:
T016 tests/Feature/DashboardTest.php
T017 tests/Feature/Auth/RegistrationTest.php
T018 tests/Feature/Auth/PasswordResetTest.php

# Then implement:
T019 resources/views/dashboard.blade.php
T021 resources/views/components/app-logo*.blade.php
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1 → Phase 2 → Phase 3 (US1)
2. **Stop and validate**: quickstart V1, V3 and V5 show the app runs from a fresh clone with only
   Docker
3. Commit. This alone unblocks all future feature work

### Incremental Delivery

1. Setup + Foundational → running stack
2. US1 → README and reproducibility → commit
3. US2 → account flows confirmed, dashboard greeting → commit
4. US3 → quality gates green → commit
5. Polish → full fresh-clone run → commit

---

## Notes

- Never run host `php`, `composer` or `npm`, even though they're installed. Everything goes
  through `docker run …` (before Sail exists) or `sail` (after)
- Don't hand-write auth pages or actions. They come from the kit (constitution Principle I)
- If the installer's flags differ from T002 (installer versions change), use the closest
  equivalent that produces Livewire + Pest + Laravel built-in auth, and record the actual command
  in research.md R2
