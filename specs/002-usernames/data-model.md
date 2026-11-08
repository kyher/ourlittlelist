# Data Model: Usernames & Partner Search

## User (existing table `users`, modified)

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| `username` | string(30), nullable, **unique** | Required on every write path. Trimmed and lowercased first. 3–30 chars. Matches `^[a-z0-9][a-z0-9_.]*$`. Not in `config('usernames.reserved')`. Unique ignoring the current user | **New.** Nullable only for rows that existed before the migration (see R4). Stored lowercase (R1) |
| `name` | string | Unchanged | Display name, not unique, shown in search results |
| `email` | string, unique | Unchanged | Never shown in search results (FR-009) |

**Migration**: `add_username_to_users_table`. It adds the nullable `username` column after `name`
and a unique index. `down()` drops both. There is no data backfill.

**Model changes** (`App\Models\User`):
- Add `username` to `#[Fillable]` and the `@property string|null $username` docblock.
- Add a `username` attribute mutator that stores `Str::lower(trim($value))`, or `null` if the
  value is null.

**Factory** (`UserFactory`): generates a valid, unique `username`. A new `withoutUsername()` state
sets it to `null`.

## States

```text
(legacy user) username = null ──choose username──▶ username set ──change in settings──▶ username set (new value)
(new user)    register with username ────────────▶ username set
```

- When `username = null`, the user is blocked from app routes by the `has-username` middleware and
  sent to `/username`.
- Once set, a username is never cleared by the application. The old value becomes free to claim
  immediately after a change.

## Search projection

Partner search reads only `id`, `name` and `username`. The result object exposed to the view and
to Livewire state contains `username` and `name` only. No relationships are loaded. There is no
couple or review data yet, and the search must not expose any once it exists (FR-009, FR-013).

## Configuration

`config/usernames.php`:

```php
return ['reserved' => ['admin', 'administrator', 'support', 'help', 'settings', 'dashboard',
    'login', 'logout', 'register', 'api', 'root', 'system', 'username', 'partner', 'ourlittlelist']];
```
