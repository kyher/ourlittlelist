# Contract: Admin routes

All routes are in the `web` middleware group and use the admin session cookie
([research R2](../research.md)).

| Method | Path | Name | Middleware | Result |
|--------|------|------|------------|--------|
| GET | `/admin/login` | `admin.login` | `web` | Login form. A signed-in admin is redirected to `admin.dashboard` |
| POST | `/admin/login` | `admin.login.store` | `web`, `throttle:admin-login` | See below |
| POST | `/admin/logout` | `admin.logout` | `web`, `auth:admin` | Ends the admin session and redirects to `admin.login` |
| GET | `/admin` | `admin.dashboard` | `web`, `auth:admin` | The three totals |

## `POST /admin/login`

**Fields**: `email` and `password`, both required strings. There is no remember-me option.

| Case | Result |
|------|--------|
| Correct admin credentials | Session regenerated, redirect to the intended admin page or `admin.dashboard` |
| Wrong password, unknown email, or regular user's credentials | Back to the form with the `auth.failed` message on `email`. The same response in every case (FR-012) |
| More than 5 attempts in a minute for one email and IP | `429`, as with regular login (FR-013) |

## `GET /admin`

Shows only labelled totals for Users, Couples and Reviews, plus a logout button. No names, emails,
usernames, review content or per-couple figures appear (FR-019). A total with no records shows `0`.

## Access rules

| Visitor | `/admin` | Regular signed-in pages |
|---------|----------|-------------------------|
| Not signed in | Redirect to `admin.login` | Redirect to `login` |
| Regular user only | Redirect to `admin.login` | Allowed |
| Admin only | Allowed | Redirect to `login` |
| Signed in as both | Allowed | Allowed. Logging out of either leaves the other signed in |

Regular login (`/login`) doesn't change. Admin credentials fail there because it only checks `users`.
