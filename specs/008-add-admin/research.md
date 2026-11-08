# Research: Admin Users and System Totals

All technical unknowns are resolved. Framework behaviour below was checked against the installed
versions in `vendor/`.

## R1. How admins are stored and authenticated

**Decision**: A new `Admin` model and `admins` table, with an `admin` session guard and an `admins`
provider in `config/auth.php`. Admin pages are protected by `auth:admin`.

**Rationale**: Separate tables make FR-009 and FR-010 true by construction. The login for each kind
of account only looks in its own table, so the same email can exist in both and credentials can't
cross over.

**Alternatives considered**:
- `is_admin` column on `users`: shares accounts and login, which contradicts the spec.
- Admin accounts defined in environment variables: the spec requires a command that creates them.

## R2. Keeping the two logins independent in one browser

**Decision**: Give `/admin` paths their own session cookie, named `{session.cookie}-admin`. A
middleware prepended to the `web` group runs before `StartSession` and calls `setName()` on the
session store. It sets the name on every request: the admin cookie for `/admin` and `/admin/*`, and
the configured cookie for everything else.

**Rationale**: Separate guards alone are not enough. Fortify's logout calls
`$request->session()->invalidate()`, and so may other regular sign-out paths, which would also sign
the admin out of a shared session. The spec's edge case says logging out of one must not end the
other. Separate cookies mean two separate sessions, so every sign-out path is covered at once.

`setName()` is used rather than changing config because it works even if the session store has
already been resolved. `StartSession` reads both the cookie name and the response cookie from the
store (`getSession()`, `addCookieToResponse()`).

**Consequences**:
- The session is keyed by path, so admin pages must not submit to Livewire's `/livewire/update`
  endpoint, which falls outside `/admin` (R4).
- `url.intended` lives in the admin session, so an admin is only ever redirected to admin pages.
- With the database session driver, an admin session's `sessions.user_id` holds the admin's ID.
  The column has no foreign key and nothing in the app reads it.
- The `XSRF-TOKEN` cookie is shared, but forms post `_token` from the page, which takes precedence.

**Alternatives considered**:
- Replacing Fortify's logout controller: only covers one sign-out path and adds a Fortify override.
- Removing the spec's edge case: possible, but the cookie is about 15 lines and also gives cleaner
  isolation (FR-011).

## R3. Login rate limiting

**Decision**: A named `admin-login` limiter of 5 per minute, keyed by lowercase email and IP,
defined next to `login` in `FortifyServiceProvider` and applied with `throttle:admin-login` to the
login POST.

**Rationale**: This matches regular login's limit and its 429 behaviour (FR-013). A separate name
keeps admin attempts from using up the regular login's allowance for the same email and IP.

**Note**: `SessionGuard` in the installed version does not equalise timing for unknown emails.
Regular login has the same property. FR-012 concerns the message shown, and the rate limit bounds
probing, so no timing countermeasure is added.

## R4. Plain Blade pages, not Livewire

**Decision**: A controller-backed login form and dashboard, using the existing `x-layouts::auth`
layout and Flux inputs.

**Rationale**: The starter kit's own login page is a plain Blade form that posts to a route, so
this matches it. Neither page has interactive behaviour, so Principle I's Livewire requirement
doesn't apply. Livewire's update endpoint is also outside `/admin` and would use the wrong session
cookie (R2). `x-layouts::auth` references no user data, unlike the app header layout, so it is safe
to reuse and avoids a new layout.

**Alternatives considered**: A Livewire page for the dashboard adds a component with nothing to
update.

## R5. The command's prompting and validation

**Decision**: An `admin:create {email}` command class using `password()` from Laravel Prompts.

1. Trim and lowercase the email, then validate format and uniqueness before any prompt.
2. Fail with a clear message if input isn't interactive: `--no-interaction`, or STDIN is not a TTY
   (the TTY check is skipped under unit tests, as `ConfiguresPrompts` does).
3. Prompt for the password and confirmation, and validate them together with the existing
   `PasswordValidationRules::passwordRules()`. Show the errors and repeat until valid.
4. Create the admin. A `UniqueConstraintViolationException` from a concurrent run is reported as
   the duplicate error.

**Rationale**: Reusing `passwordRules()` keeps the rules identical to regular accounts in every
environment, including the production-only strength rules (FR-003). Its `confirmed` rule covers the
second entry. Nothing is written until the password is valid, so cancelling leaves no trace.

**Docker note**: `docker compose exec` allocates a TTY when run from a terminal, so prompts work.
`docker compose exec -T` or piped input takes the non-interactive failure path.

## R6. Sending guests to the right login page

**Decision**: `redirectGuestsTo` in `bootstrap/app.php` returns `route('admin.login')` for `/admin`
paths and `route('login')` otherwise. An already signed-in admin visiting the login page is
redirected by the controller.

**Rationale**: `auth:admin` would otherwise use the default redirect to the regular login, which
breaks FR-015. Handling the signed-in case in the controller avoids changing `redirectUsersTo`,
which would affect the regular `guest` routes.

## R7. Calculating totals

**Decision**: `User::count()`, `Couple::count()` and `Review::count()` on each request, with no
caching.

**Rationale**: `EndCouple` deletes couples, so `Couple::count()` is exactly the current couples.
Neither `Couple` nor `Review` has a global scope or soft deletes to account for, and each review is
its own row (FR-018). Admins are in another table, so `User::count()` already excludes them
(FR-017). A count over these tables is fast at this scale, and SC-004 needs exact figures.

## R8. Email normalisation

**Decision**: Store admin emails lowercased, and lowercase the submitted email at login. The
`email` column has a unique index.

**Rationale**: This handles letter casing consistently on MySQL and SQLite, whose default
collations differ. The unique index guards against concurrent runs.

## R9. Testing pitfalls

- `actingAs($admin, 'admin')` makes `admin` the default guard for the test, so the regular `auth`
  middleware would then see the admin as signed in. Tests for FR-010 and FR-011 must sign in
  through `POST /admin/login`, which doesn't change the default guard.
- Under test, requests share one session store and one app instance, so a test can't demonstrate
  the two sessions staying independent after logout. Assert the store's cookie name per path
  instead, and confirm real independence with quickstart scenario 5.
- `password()` falls back to a plain question under unit tests, so `expectsQuestion()` works with
  the prompt labels.
