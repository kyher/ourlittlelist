# Data Model: Admin Users and System Totals

## Admin (new table: `admins`)

An operator account, unrelated to users, couples and reviews.

| Field | Type | Rules |
|-------|------|-------|
| `id` | big integer, primary key | |
| `email` | string(255), unique | Valid email, stored trimmed and lowercase, unique among admins |
| `password` | string | Stored hashed through the model's `hashed` cast. Hidden from serialisation |
| `created_at`, `updated_at` | timestamps | |

No `name`, `username`, `email_verified_at`, two-factor or remember-token columns, because the spec
needs none of them.

**Relationships**: None. No foreign keys to or from any other table.

**Validation at creation** (`admin:create`):
- `email`: required, valid format, at most 255 characters, unique in `admins`. Uniqueness against
  `users` is deliberately not checked (FR-009).
- `password`: the existing `passwordRules()`, including confirmation (FR-003).

**Lifecycle**: Created by the command only. This feature does not update or delete admins. If a row
is removed by hand, that admin's next request fails `auth:admin` and goes to the admin login.

## System totals (derived, not stored)

| Figure | Source | Notes |
|--------|--------|-------|
| Users | `users` row count | Admins are in another table (FR-017). Includes unverified users and users with no username or couple |
| Couples | `couples` row count | Ended couples are deleted, so this is current couples only |
| Reviews | `reviews` row count | Each partner's review counts separately (FR-018) |

## Existing tables

No changes. `sessions` also holds admin sessions when the database session driver is used
([research R2](research.md)).
