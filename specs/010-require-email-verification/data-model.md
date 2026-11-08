# Data Model: Require Email Verification

No schema changes or data migrations.

## User (existing)

| Field | Type | Role in this feature |
|-------|------|----------------------|
| `email` | string, unique | The address being verified |
| `email_verified_at` | timestamp, nullable | `null` means unverified; set when a valid link is followed |

## Verification state

```text
                registers, or existing account with null timestamp
                                   │
                                   ▼
   ┌──────────────────────►   Unverified
   │                               │
   │ changes email                 │ follows a valid link
   │                               ▼
   └───────────────────────    Verified
```

- Saving a profile with the same email does not change state (FR-013).
- A rejected link does not change state (FR-011).
- Being unverified never alters `couple_id`, couples or reviews (FR-014).

## Verification link (not stored)

A signed URL containing the user id and a SHA-1 of the current email, valid for 60 minutes. Changing the email invalidates earlier links because the hash no longer matches.

## Rate limit (cache only)

Key per user for profile-page sends, six per minute. See [research R4](research.md).
