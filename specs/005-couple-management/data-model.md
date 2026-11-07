# Data Model: Couple Management

## Tables

### `couples` (new)

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `created_at` / `updated_at` | timestamps | `created_at` is the couple's start date |

The members are the two `users` rows whose `couple_id` is this id. Nothing else is stored.
Deleting the row ends the couple (R1).

**Future couple-owned tables** (for example reviews) MUST reference `couples.id` with
`cascadeOnDelete`. This keeps FR-017 working: ending a couple deletes all of its data.

### `users` (changed)

| Column | Type | Notes |
|--------|------|-------|
| `couple_id` | bigint, nullable, FK → `couples.id`, `nullOnDelete`, indexed | `null` means the user is single |

### `couple_requests` (new)

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `sender_id` | FK → `users.id`, `cascadeOnDelete` | |
| `receiver_id` | FK → `users.id`, `cascadeOnDelete` | |
| `status` | string(16) | `CoupleRequestStatus` enum, default `pending` |
| `responded_at` | timestamp, nullable | Set when the status leaves `pending` |
| `created_at` / `updated_at` | timestamps | `created_at` is when the request was sent |

Indexes: `(receiver_id, status)` and `(sender_id, status)`.

## Models

- **`Couple`**:
  - `members(): HasMany<User>`, which uses `couple_id`.
  - `partnerOf(User $user): User`.
- **`User`** (changed):
  - Adds `couple(): BelongsTo<Couple>`.
  - Adds `partner(): ?User`, which returns the other member.
  - Adds `isInCouple(): bool`.
  - Adds `sentCoupleRequests()` and `receivedCoupleRequests()`.
  - Adds a `deleting` hook that ends the couple (R8).
  - `couple_id` is **not** fillable. Only the couple actions set it.
- **`CoupleRequest`**:
  - `sender()` and `receiver()`, both `BelongsTo<User>`.
  - `status` is cast to `CoupleRequestStatus`.
  - Adds a `pending()` scope.

## Enum `App\Enums\CoupleRequestStatus` (string-backed)

| Value | Meaning | Who sees what |
|-------|---------|---------------|
| `pending` | Waiting for the receiver | The sender sees "Waiting for X" and a **Cancel** button. The receiver sees **Accept** and **Reject** |
| `accepted` | The receiver accepted it, and the couple was created | Both are taken to `/couple` |
| `declined` | The receiver rejected it | The sender sees "X declined your request" and can send a new one |
| `cancelled` | The sender withdrew it | Hidden from both |
| `closed` | The system closed it because a user joined a couple, or because accepting it failed (R2) | The sender sees "X is no longer available". Hidden from the receiver |

## State transitions

```text
                   accept (receiver; both single)         → accepted   [creates Couple]
pending ──────────┼ reject (receiver)                     → declined
                   ├ cancel (sender)                      → cancelled
                   └ system: either user joined a couple  → closed
                     system: accept found a user coupled  → closed
```

Only `pending` can change. Every other status is final.

```text
User: single (couple_id null) ──accept──▶ in couple (couple_id = C)
          ▲                                       │
          └──── end by either member / account deleted (Couple C deleted) ┘
```

## Validation rules (enforced in actions, re-checked inside a transaction)

| Rule | Spec | Error message (FR-020) |
|------|------|------------------------|
| The sender is not the receiver | FR-006 | "You can't send a request to yourself." |
| The sender is single | FR-004, FR-006 | "You're already in a couple." |
| The receiver is single | FR-003, FR-006 | ":name is not available." |
| There is no pending request from the receiver to the sender | FR-007 | ":name has already sent you a request. Accept it below." |
| The sender has no pending outgoing request | FR-005, FR-006 | "You already have a pending request. Cancel it first." |
| At most 10 sends per hour | FR-018 | "You've sent too many requests. Try again in :minutes minutes." |
| The request is still pending when it is accepted, declined or cancelled | Edge cases | "This request is no longer available." |
| Both users are still single when the request is accepted | FR-011 | ":name is already in a couple." (the request becomes `closed`) |
| Only a member can end the couple | FR-019 | 403 through `CouplePolicy` |
| Only the receiver can accept or decline, and only the sender can cancel | FR-010 | 403 through `CoupleRequestPolicy` |

## Side effects

- **On accept**: every other `pending` request where either member is the sender or the receiver
  becomes `closed` (FR-012).
- **On end** (`EndCouple`):
  - Delete the `couples` row. The FK nulls both members' `couple_id`.
  - Future couple data is deleted by its cascading FK (FR-016, FR-017).
