# Data Model: Application Foundation

This feature adds no custom domain tables. The schema is the starter kit's default migrations,
run against MySQL (development) and SQLite in memory (tests).

## User (`users`)

| Field | Type | Rules |
|-------|------|-------|
| id | bigint, PK | auto-increment |
| name | string(255) | required, max 255 |
| email | string(255) | required, valid email, lowercase, **unique** |
| email_verified_at | timestamp, nullable | set when the user verifies (verification not enforced) |
| password | string | required, hashed, confirmed. The kit's default password rules apply |
| two_factor_secret | text, nullable | Fortify 2FA (opt-in) |
| two_factor_recovery_codes | text, nullable | Fortify 2FA |
| two_factor_confirmed_at | timestamp, nullable | Fortify 2FA |
| remember_token | string(100), nullable | "remember me" |
| created_at / updated_at | timestamps | |

- **Lifecycle**: registered → (optionally verified) → (optionally 2FA-enabled) → deleted. Account
  deletion requires the current password and permanently removes the row.
- **Future relationships** (not in this feature): belongs to at most one Couple, and has many
  Reviews (constitution Principles III and V).

## Supporting framework tables (unchanged kit defaults)

- `passkeys`: WebAuthn passkeys a user can opt in to register (kit's Fortify passkeys feature).
- `password_reset_tokens`: email (PK), token, created_at.
- `sessions`: database session driver.
- `cache`, `cache_locks`: database cache driver.
- `jobs`, `job_batches`, `failed_jobs`: database queue driver.
