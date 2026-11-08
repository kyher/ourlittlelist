# Research: Couple Reviews

All Technical Context items were known from the existing codebase (feature 005). The plan had no
NEEDS CLARIFICATION items. This file records the design decisions.

## R1. What is reviewed: a shared `Item` with one review per member

**Decision**: Add three tables:
- `items`: a category, a title and the owning couple.
- `reviews`: one per (item, author).
- `review_prompts`: one per (item, recipient).

`items.couple_id` cascades from `couples`. `reviews` and `review_prompts` cascade from `items`.

**Rationale**:
- "Review the same thing" (FR-013) and side-by-side comparison (Constitution V) both need a
  shared row that both reviews point to.
- Ending a couple deletes one `couples` row. That cascades through items into reviews and prompts,
  which satisfies FR-022 and feature 005's FR-017 with no extra code.

**Alternatives considered**:
- Reviews that hold their own category and title, matched by text. This makes comparison, prompts
  and duplicate prevention fragile.
- A separate table per category. This breaks Constitution V.

## R2. Categories as a PHP backed enum

**Decision**: Add `App\Enums\ReviewCategory` (`film`, `tv_show`, `book`, `food`, `restaurant`)
with a `label()` method. It is stored as a `string` column and cast on `Item`.

**Rationale**:
- Constitution V allows "an enum or category table".
- An enum matches `CoupleRequestStatus` and needs no seeding.
- Adding a category later is a single new case.

**Alternatives considered**: A `categories` table. Rejected because nothing needs to edit
categories at runtime, so it fails Principle VI.

## R3. Rating stored as whole half-stars

**Decision**: Store `reviews.rating` as an unsigned tinyint counting half-stars, from 1 to 10.
The `Review::stars()` accessor returns `rating / 2` as a float (for example 9 means 4.5 stars, and 1 means 0.5).
Form input is validated as one of the strings `0.5, 1, … 5` and converted with
`(int) round($stars * 2)`.

**Rationale**:
- There are no float comparisons and every database stores the value exactly.
- The column can only hold valid steps when combined with the validation (FR-006).

**Alternatives considered**: `decimal(2,1)`. This works, but leaves the validation as the only
thing stopping values like 3.3.

## R4. Duplicate items: a normalised key plus a unique index

**Decision**:
- `items.title` stores the trimmed title as entered.
- `items.title_key` stores `mb_strtolower(trim(title))`.
- A unique index covers (`couple_id`, `category`, `title_key`).

Creating a review uses `Item::createOrFirst()` inside the transaction. If the item already exists,
the review is attached to it (FR-004). The user is then redirected to that item with a toast:
"Added to your existing review of :title."

**Rationale**:
- The unique index guarantees SC-006 even when both partners add the same title at the same time.
- `createOrFirst` handles the race without needing locks.

**Alternatives considered**: A check followed by an insert in the application. This is racy.

## R5. One review per member per item

**Decision**: Add a unique index on `reviews (item_id, user_id)`. The action checks first and
returns a friendly message: "You've already reviewed :title. Edit your review instead." If the
insert still hits the unique index (a race), it raises the same message.

## R6. Prompts are explicit rows, not derived

**Decision**:
- **Creating a prompt**: when a review creates a *new* item, `CreateReview` inserts a
  `review_prompts` row for the partner.
- **Answering a prompt**: when the recipient reviews the item, their prompt row is deleted.
- **Dismissing a prompt**: sets `dismissed_at`. Only prompts where `dismissed_at` is null are
  shown.
- **Removing an item**: when the item goes, its prompts cascade.

**Rationale**:
- A derived rule ("items my partner reviewed that I have not") would prompt again after someone
  removes their own review, which the spec rules out (FR-017 and Assumptions).
- Dismissal needs to be stored anyway (FR-016).

**Alternatives considered**: A derived query plus a `dismissals` table. This needs the same number
of tables and gives the wrong behaviour on removal.

## R7. Removing the last review removes the item

**Decision**: `DeleteReview` runs in a transaction:
1. Lock the item row (`lockForUpdate`).
2. Delete the review.
3. Delete the item if it has no reviews left (FR-011).

`CreateReview`, when it targets an existing item (answering a prompt or reviewing from the item
page), also locks the item row first. If the item is gone it fails with "This item no longer
exists." This covers the race in the edge cases.

## R8. Action classes and one exception type

**Decision**: Put four actions in `app/Actions/Reviews/`, mirroring `app/Actions/Couples`:
- `CreateReview`
- `UpdateReview`
- `DeleteReview`
- `DismissReviewPrompt`

Rule violations throw `App\Exceptions\ReviewActionException`, whose message is shown to the user
(FR-021). Field validation (required, length, allowed ratings) lives in a Livewire Form object,
`App\Livewire\Forms\ReviewForm`, which both pages share so the create and edit rules can't drift
apart (FR-009).

## R9. Authorization and scoping

**Decision**:
- **Couple scoping**: every lookup goes through `Item::query()->whereBelongsTo($user->couple)`.
  Route model binding on `/reviews/{item}` is scoped in the same way. Anything outside the couple
  returns a **404**, so its existence is never revealed (FR-020).
- **Policies**:
  - `ItemPolicy@view` and `ItemPolicy@review`: the user is a member of the item's couple.
  - `ReviewPolicy@update` and `ReviewPolicy@delete`: the user is the author *and* still in the
    item's couple. A partner gets a **403** (FR-012).
  - `ReviewPromptPolicy@dismiss`: the user is the recipient.
- **Being in a couple**: a new `in-couple` route middleware (`EnsureUserIsInCouple`) redirects
  single users to `partner.search` with a flash message (FR-001). The actions re-check this inside
  their transaction, in case the couple ended while the form was open.

**Rationale**: Three pages need the same guard, so one middleware is simpler than three `mount()`
redirects. It follows the existing `has-username` alias pattern.

## R10. Where prompts appear, and landing for couples

**Decision**:
- **Where prompts appear**: open prompts are shown at the top of the reviews index (`/reviews`).
  The header **Reviews** nav item shows a `flux:badge` with the open-prompt count.
- **Landing page**: users in a couple now land on `/reviews`, not `/couple`. `partner.search`
  redirects users in a couple to `reviews.index`, and the logo resolves to the same place. The
  **Your partner** nav item stays.

**Rationale**: SC-005 says the partner sees the prompt on their next page load. Putting prompts on
the landing page plus a nav badge means they see it straight after signing in.

## R11. Star rating input and display

**Decision**: Add two anonymous Blade components:
- `x-star-rating` (display): five stars, each full, half or empty, using Heroicon `star` with a
  clipped half. It includes an `aria-label` such as "4.5 out of 5 stars".
- `x-star-rating-input`: ten visually hidden radio inputs (0.5 to 5 in steps of 0.5) drawn as half
  stars, bound with `wire:model`. It is keyboard accessible through the native radio group.

**Rationale**: Free Flux has no rating component, and Principle VI rules out adding a package. The
native radios keep it accessible, and server validation is still what counts.

**Alternatives considered**: A `flux:select` with ten options. This works but is a poor fit for
"stars". It is the fallback if the component turns out to be fiddly.

## R12. "Most recent activity" ordering and "edited" marker

**Decision**:
- **Ordering**: `Review` has `$touches = ['item']`, so creating or editing a review bumps
  `items.updated_at`. The index orders by `items.updated_at desc` (FR-018).
- **Edited marker**: `reviews.edited_at` (nullable) is set only by `UpdateReview`. The view shows
  "Edited" when it is not null (FR-009).

Comparing `updated_at` against `created_at` was rejected because it gives wrong results for edits
within the same second and for touches.
