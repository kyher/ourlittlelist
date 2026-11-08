# Data Model: Filter, Search and Sort Reviews

**No schema changes.** No migrations, tables, columns or indexes are added. The feature reads
the tables from `specs/006-couple-reviews/data-model.md`.

## Existing data used

| Table | Columns used | How |
|-------|--------------|-----|
| `items` | `couple_id`, `category`, `title_key`, `updated_at`, `id` | Couple scope, category filter, search (R3), default order |
| `reviews` | `item_id`, `user_id`, `rating` | "Show" filter (R1), rating sort (R4) |
| `users` / `couples` | `couple_id`, partner | Identify "me" and "my partner" |

Indexes already covering the queries:
- `items (couple_id, updated_at)` for the couple scope and default order.
- `reviews (item_id, user_id)` unique, for the `EXISTS` filters and rating subquery.

## New code-level types

### `App\Enums\ReviewShow` (string-backed)

| Case | Value | Label | Rule |
|------|-------|-------|------|
| `All` | `all` | All reviews | No filter (default) |
| `Mine` | `mine` | Reviewed by me | Item has a review by me |
| `Partner` | `partner` | Reviewed by :partner | Item has a review by my partner |
| `Waiting` | `waiting` | Waiting for my review | Item has a review by my partner and none by me |

### `App\Enums\ReviewSort` (string-backed)

| Case | Value | Label | Order |
|------|-------|-------|-------|
| `Recent` | `recent` | Most recent | `updated_at desc, id desc` (default) |
| `Mine` | `mine` | My highest rated | My rating desc, unrated last, then Recent |
| `Partner` | `partner` | :partner's highest rated | Partner's rating desc, unrated last, then Recent |

### New `Item` query scopes

| Scope | Arguments | Effect |
|-------|-----------|--------|
| `reviewedBy` | `User $user` | Items with a review by `$user` |
| `notReviewedBy` | `User $user` | Items without a review by `$user` |
| `titleContains` | `string $search` | `title_key` contains the trimmed, lowercased, escaped search |
| `orderByRatingOf` | `User $user` | `$user`'s rating desc, unrated last, then `updated_at desc, id desc` |

All are chained after `forCouple()`, which stays the required first scope (Principle III).

## Validation

| Input | Rule | On invalid |
|-------|------|------------|
| `show` | One of `ReviewShow` values | Treated as `all` |
| `sort` | One of `ReviewSort` values | Treated as `recent` |
| `category` | One of `ReviewCategory` values (unchanged) | Treated as no category |
| `q` (search) | Trimmed, cut to 100 characters, matched literally | Empty after trim means no search |

No input produces a validation error; bad values fall back to defaults (FR-010).
