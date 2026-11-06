# Research: Usernames & Partner Search

There were no open NEEDS CLARIFICATION items in the Technical Context. The decisions below make
the spec's defaults concrete for the existing Laravel 13 / Livewire 4 / Fortify codebase.

## R1. Storage & case-insensitivity

- **Decision**: Add a nullable `username` column, `string(30)`, with a unique index on `users`.
  Normalise the value on write with a model attribute mutator that applies
  `Str::lower(trim(...))`, so it is always stored in lowercase (FR-003).
- **Rationale**: Lowercase storage makes uniqueness and exact-match lookups case-insensitive on
  every database: MySQL in development and SQLite in tests and CI. It does this without relying on
  collation, and a plain unique index is enough. The column is nullable only so that existing rows
  can migrate. The app enforces "every user has one" (FR-001) through R4.
- **Alternatives considered**: A case-insensitive collation or a `LOWER()` functional index
  (behaves differently on MySQL and SQLite). A separate `username_normalized` column (not needed
  when the display form is lowercase anyway).

## R2. Validation rules

- **Decision**: Add `usernameRules(?int $userId = null)` to the existing
  `App\Concerns\ProfileValidationRules` trait. It returns `required`, `string`, `min:3`, `max:30`,
  `regex:/^[a-z0-9][a-z0-9_.]*$/`, `not_in:<reserved list>`, and
  `Rule::unique(User::class)->ignore($userId)`. The input is trimmed and lowercased **before**
  validation, so `Harry` collides with `harry`. Reserved words live in `config/usernames.php`
  (`reserved` array, e.g. `admin`, `support`, `settings`, `dashboard`, `login`, `register`,
  `logout`, `api`, `root`, `system`, `ourlittlelist`).
- **Rationale**: This reuses the starter kit's pattern (Principle I), so registration, the
  choose-username page and profile settings all share one rule set. A config array is the
  simplest way to keep a list that can grow, as the spec assumes.
- **Alternatives considered**: A custom `Rule` class (more code for the same result). A database
  table of reserved names (YAGNI).

## R3. Concurrent claims (edge case)

- **Decision**: The unique index is the final guard. Save inside a `try` and catch
  `Illuminate\Database\UniqueConstraintViolationException`. Turn it into a validation error on
  `username` ("That username is taken").
- **Rationale**: The `unique` validation rule leaves a race window between the check and the
  insert. The database constraint closes it, and the catch keeps the error friendly.

## R4. Forcing existing users to pick a username (FR-006)

- **Decision**: Add a new middleware, `App\Http\Middleware\EnsureUserHasUsername`, registered as
  the alias `has-username` in `bootstrap/app.php`. Apply it to the authenticated app route groups
  (dashboard, settings, partner search). The new route `GET /username`
  (`pages::auth.choose-username`, name `username.create`) sits outside that middleware. If a user
  has `username === null`, the middleware redirects to `username.create`. Once a username is set,
  the choose page redirects to the dashboard. Logout (POST) and Fortify's verify-email routes are
  not covered, so they still work.
- **Rationale**: This is route middleware rather than global `web` middleware. That avoids
  interfering with Fortify, guest pages and Livewire's update endpoint. Livewire re-applies route
  middleware to component updates on those routes, so actions cannot bypass the gate.
- **Alternatives considered**: Backfilling generated usernames in the migration (users would
  not know their handle, which breaks SC-001's "knows their partner's username"). A global
  middleware with an allow-list (more brittle).

## R5. Registration

- **Decision**: Add a `username` field to `resources/views/pages/auth/register.blade.php`.
  Validate and persist it in `App\Actions\Fortify\CreateNewUser`. Add `username` to the model's
  `#[Fillable]`. Login stays email-based, because `Fortify::username()` is unchanged.
- **Rationale**: Uses the kit's flow as Principle I requires. Signing in by username is not
  requested.

## R6. Partner search (FR-007 to FR-012)

- **Decision**: A Livewire page component `pages::partner.search` at `GET /partner/search`
  (name `partner.search`) with middleware `auth`, `verified` and `has-username`, plus a sidebar
  link "Find your partner". The form submits (no live-as-you-type, which keeps rate-limiting
  meaningful). The handler:
  1. Trims and lowercases the term. If it is empty, it shows a validation error and runs no query.
  2. Calls `RateLimiter::attempt('partner-search:'.$userId, 30, ..., 60)`. When the limit is
     exceeded, it shows a "Too many searches, try again in N seconds" error.
  3. Runs `User::where('username', $term)->whereKeyNot(auth()->id())->first(['id','name','username'])`.
  4. Renders the username and name, or "No user found with that username".
- **Rationale**: An exact match using the unique index is O(1) and cannot be used to enumerate
  users through partial terms. Selecting only `name` and `username` keeps the email out of the
  component state that is sent to the browser (FR-009). The first-party `RateLimiter` needs no
  extra infrastructure (Principle VI). It uses the default cache store, so it works with the
  array cache in tests.
- **Alternatives considered**: Throttle middleware on the route (that does not cover Livewire
  update requests in the same way, and it limits page views rather than searches). Live search
  (more queries for no value with an exact-match design).

## R7. Profile settings (FR-005)

- **Decision**: Add a `username` input to the existing `⚡profile.blade.php` component. Validate
  it with `profileRules($user->id)`, which gains a `username` key, and catch the unique-violation
  case from R3.
- **Rationale**: Profile settings already edit the name and email, so this is the smallest change.

## R8. Test data

- **Decision**: `UserFactory` generates `username` as `fake()->unique()->userName()`, normalised
  to the allowed character set (lowercase, with invalid characters replaced by `_`, and cut to 30
  characters). Add a `withoutUsername()` state for legacy-user tests.
- **Rationale**: Existing kit tests keep passing, because factory users get past `has-username`.
