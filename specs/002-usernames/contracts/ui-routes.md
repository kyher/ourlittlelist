# UI Route Contract: Usernames & Partner Search

All routes are server-rendered Livewire/Blade pages. The routes listed below are new or changed.
Every other route behaves as described in `specs/001-laravel-docker-setup/contracts/ui-routes.md`,
except that **app routes now also require a username** (see `has-username`).

| Path | Name | Middleware | Purpose | Spec ref |
|------|------|------------|---------|----------|
| `/register` *(changed)* | `register` | guest | Adds a required **Username** field next to name, email and password | US1, FR-004 |
| `/username` *(new)* | `username.create` | auth | Asks a user with no username to choose one. Redirects to `/dashboard` if they already have one. Submitting a valid username saves it and redirects to the intended URL or `/dashboard` | US3-1, FR-006 |
| `/settings/profile` *(changed)* | `profile.edit` | auth, has-username | Adds an editable **Username** field | US3-2/3, FR-005 |
| `/partner/search` *(new)* | `partner.search` | auth, verified, has-username | Exact-username search for a partner | US2, FR-007 to FR-012 |
| `/dashboard`, `/settings/appearance`, `/settings/security` *(changed)* | n/a | + has-username | Unchanged pages, now gated | FR-006 |

## `has-username` middleware

- Guest request: passes through. `auth` handles guests.
- Authenticated user with `username === null`: `302` to `/username`, and the intended URL is
  stored.
- Otherwise: passes through.
- Not applied to: `/username`, `POST /logout`, Fortify's email verification routes, or guest auth
  pages.

## Username input contract (shared by register, `/username` and profile)

| Input | Normalisation | Validation errors (field `username`) |
|-------|---------------|--------------------------------------|
| `username` | trimmed, lowercased | required; at least 3 characters; at most 30 characters; "may only contain letters, numbers, underscores and full stops, and must start with a letter or number"; reserved ("That username isn't available"); taken ("That username is taken"), including when a concurrent insert hits the unique index |

## Partner search component contract (`pages::partner.search`)

**Input**: `query` (string). Submitted with the form's `search` action, not live.

| Condition | Output |
|-----------|--------|
| `query` is empty or only whitespace | Validation error on `query`. No lookup |
| More than 30 searches by this user within 60 s | Error on `query`: "Too many searches. Try again in N seconds." No lookup |
| The normalised `query` equals another user's username | Result card showing **username** (`@harry`) and **name**. Nothing else is rendered or held in component state |
| The normalised `query` equals the searcher's own username | Same as no match |
| No exact match (including partial matches) | "No user found with that username." |

There is no connect or invite action on the result card in this feature (FR-013).
