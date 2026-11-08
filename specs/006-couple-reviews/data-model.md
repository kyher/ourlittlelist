# Data Model: Couple Reviews

Builds on `couples` and `users.couple_id` from feature 005. All three new tables are deleted
when their couple is deleted (FR-022), through the cascade `couples → items → reviews / review_prompts`.

## Enum: `App\Enums\ReviewCategory` (string-backed)

| Case | Value | Label |
|------|-------|-------|
| Film | `film` | Film |
| TvShow | `tv_show` | TV show |
| Book | `book` | Book |
| Food | `food` | Food |
| Restaurant | `restaurant` | Restaurant |

## Table: `items`

| Column | Type | Rules |
|--------|------|-------|
| `id` | bigint PK | |
| `couple_id` | FK → `couples.id` | required, `cascadeOnDelete` |
| `category` | string(20) | a `ReviewCategory` value, cast to the enum |
| `title` | string(255) | trimmed title, as entered |
| `title_key` | string(255) | `mb_strtolower(trim(title))`, set by a mutator when `title` is set |
| `created_by` | FK → `users.id`, nullable | `nullOnDelete`. Records who started the item, for display only |
| `created_at`, `updated_at` | timestamps | `updated_at` is touched by any review create or edit (R12) |

**Indexes**:
- unique (`couple_id`, `category`, `title_key`) (FR-004, SC-006)
- (`couple_id`, `updated_at`) for the index page

**Model `Item`**:
- `couple()` belongs to
- `reviews()` has many
- `prompts()` has many `ReviewPrompt`
- `reviewBy(User)` returns `?Review`
- scope `forCouple(Couple)`

## Table: `reviews`

| Column | Type | Rules |
|--------|------|-------|
| `id` | bigint PK | |
| `item_id` | FK → `items.id` | required, `cascadeOnDelete` |
| `user_id` | FK → `users.id` | required, `cascadeOnDelete`. The author |
| `rating` | unsigned tinyint | half-stars, from 1 to 10 (R3) |
| `body` | text | required, not just whitespace, ≤ 5,000 characters |
| `edited_at` | timestamp, nullable | set only by `UpdateReview` |
| `created_at`, `updated_at` | timestamps | |

**Indexes**: unique (`item_id`, `user_id`) (FR-008)

**Model `Review`**:
- `item()` and `author()` (belongs to `User` via `user_id`)
- `$touches = ['item']`
- `stars(): float` returns `rating / 2`
- `isEdited(): bool`

## Table: `review_prompts`

| Column | Type | Rules |
|--------|------|-------|
| `id` | bigint PK | |
| `item_id` | FK → `items.id` | required, `cascadeOnDelete` |
| `user_id` | FK → `users.id` | required, `cascadeOnDelete`. The recipient |
| `dismissed_at` | timestamp, nullable | |
| `created_at`, `updated_at` | timestamps | |

**Indexes**: unique (`item_id`, `user_id`)

**Model `ReviewPrompt`**: `item()`, `recipient()`, and scope `open()`, which is `whereNull('dismissed_at')`.

**Additions to `User`**:
- `reviews()` has many
- `reviewPrompts()` has many
- `openReviewPromptCount(): int`

## Validation (`App\Livewire\Forms\ReviewForm`)

| Field | Create (new item) | Create (existing item) | Edit | Message |
|-------|-------------------|------------------------|------|---------|
| `category` | required, `Enum(ReviewCategory)` | fixed by item, not submitted | not editable | "Choose a category." |
| `title` | required, string, trimmed, max 255 | fixed by item | not editable | "Enter a title." / "The title must be 255 characters or fewer." |
| `stars` | required, `in:0.5,1,1.5,2,2.5,3,3.5,4,4.5,5` | same | same | "Choose a rating from 0.5 to 5 stars." |
| `body` | required, string, max 5000, not just whitespace (trimmed before the required check) | same | same | "Write your review." / "Your review must be 5,000 characters or fewer." |

## Action rules and messages (`ReviewActionException`)

| Action | Rule | Message |
|--------|------|---------|
| any | the actor must be in a couple (re-checked in the transaction) | "You're no longer in a couple." |
| `CreateReview` | an existing item must still exist (locked) | "This item no longer exists." |
| `CreateReview` | the actor has no review on the item | "You've already reviewed :title. Edit your review instead." |
| `UpdateReview` / `DeleteReview` | the actor is the author (policy, 403) | — |
| `DismissReviewPrompt` | the actor is the recipient (policy, 403) | — |

## State and side effects

```text
CreateReview(user, category, title | item, stars, body)
  ├─ item created now?       → create ReviewPrompt for partner
  ├─ item already existed?   → attach review; delete actor's own prompt for item (if any)
  └─ touches item.updated_at

UpdateReview(review, stars, body) → set rating, body, edited_at = now; touches item
                                   (no prompts created, FR-017)

DeleteReview(review) → lock item; delete review;
                        item has 0 reviews? → delete item (cascades prompts)   (FR-011)

DismissReviewPrompt(prompt) → dismissed_at = now   (never shown again, FR-016)

EndCouple (005) → delete couple → cascades items → reviews, review_prompts       (FR-022)
```

**Prompt lifecycle**: `open` → `answered` (row deleted when the recipient reviews), or
`open` → `dismissed` (`dismissed_at` set), or deleted along with its item.

## Factories

- `ItemFactory`: has a `forCouple(Couple)` state.
- `ReviewFactory`: has a `by(User)` state, and `stars(float)`.
- `ReviewPromptFactory`: has a `for(User)` state, and `dismissed()`.
