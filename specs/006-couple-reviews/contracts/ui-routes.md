# UI Route Contract: Couple Reviews

All new routes are server-rendered Livewire pages inside the existing
`auth, verified, has-username` group, plus a new `in-couple` middleware. That middleware sends
single users to `partner.search` with the flash message "Connect with your partner to start
reviewing." Routes not listed here behave as in `specs/005-couple-management/contracts/ui-routes.md`.

| Path | Name | Purpose | Spec ref |
|------|------|---------|----------|
| `/reviews` *(new)* | `reviews.index` | Shows open prompts, then the couple's items, with a category filter | US2, US4, FR-013, FR-018 |
| `/reviews/new` *(new)* | `reviews.create` | Reviews a new item: category, title, stars and text | US1, FR-003 to FR-006 |
| `/reviews/{item}` *(new)* | `reviews.show` | Shows both reviews side by side. Lets you add your review if you have none, or edit or remove yours | US2, US3, FR-009 to FR-015, FR-019 |
| `/partner/search` *(changed)* | `partner.search` | A user in a couple is now redirected to `/reviews` instead of `/couple` | R10 |

`{item}` is resolved **only within the current user's couple**. Any other id returns a 404, so it
is never revealed whether the item exists (FR-020).

**Navigation for users in a couple** (header and mobile sidebar):
- **Reviews** (`reviews.index`), with a `flux:badge` showing the open-prompt count when it is above 0.
- **Your partner** (`couple.show`).

The logo links to `partner.search`, which forwards users in a couple to `/reviews`.

## `pages::reviews.index`

**State**:
- `category`: a `#[Url]` value that is either `null` or a `ReviewCategory` value. An invalid
  value is ignored.

**Computed values**:
- `prompts`: the open prompts for the current user. Each is `{id, item_id, title,
  category_label, partner_name}`.
- `items`: the couple's items in `updated_at desc` order, filtered by `category`. Each is
  `{id, title, category_label, my_stars|null, partner_stars|null}`.

| Action | Parameters | Success | Refusal |
|--------|------------|---------|---------|
| `dismissPrompt(int $id)` | prompt id | The prompt is removed from the list and the badge count drops | 404 if the prompt isn't the user's |

**Prompt card**: ":partner reviewed :title (:category)". It has a **Review it** link to
`reviews.show` and a **Dismiss** button.

**Empty state**: "No reviews yet", with a link to `reviews.create`.

## `pages::reviews.create`

**State**: `form` is a `ReviewForm` with `category`, `title`, `stars` and `body`.

| Action | Success | Refusal |
|--------|---------|---------|
| `save()` | Redirects to `reviews.show` for the item with the toast "Review added." If the item already existed, the toast is "Added to your existing review of :title." | Field errors (data-model validation). `ReviewActionException` messages are shown as a form error. If the user already reviewed the item, the message includes a link to it |

## `pages::reviews.show` (`{item}`)

**Computed values**:
- `item`: `{id, title, category_label}`.
- `myReview`: either `null` or `{id, stars, body, created_at, edited}`.
- `partnerReview`: either `null` or `{name, stars, body, created_at, edited}`. It is always
  visible (FR-014).
- `partnerName`.

**State**:
- `form`: a `ReviewForm` with only `stars` and `body`.
- `editing`: a bool.

| Action | Shown when | Success | Refusal |
|--------|-----------|---------|---------|
| `addReview()` | `myReview` is null. The title and category are shown as fixed text above the form | The user's review appears and their prompt is cleared. Toast: "Review added." | Field errors. "This item no longer exists." (redirects to the index). "You're no longer in a couple." |
| `startEditing()` | `myReview` is not null | Fills the form and sets `editing` to true | — |
| `updateReview()` | `editing` | The review updates and shows "Edited". Toast: "Review updated." | Field errors. 403 if the user isn't the author |
| `cancelEditing()` | `editing` | The form is discarded | — |
| `deleteReview()` | from the confirmation modal | Toast: "Review removed." If the item still has the partner's review, the page stays. Otherwise it redirects to `reviews.index` | 403 if the user isn't the author |

**Remove confirmation**: a `flux:modal` named `confirm-delete-review`.
- **Heading**: "Remove your review of :title?"
- **Body**: "This cannot be undone."
- **Buttons**: **Cancel**, and **Remove** (danger).

The partner's review never shows edit or remove controls.

## Blade components

- `<x-star-rating :stars="4.5" />`: read-only display with
  `aria-label="4.5 out of 5 stars"`.
- `<x-star-rating-input wire:model="form.stars" />`: ten radio inputs (0.5 to 5 in steps of 0.5),
  drawn as half stars.

## Data-test hooks

`review-prompt`, `review-prompt-link`, `dismiss-prompt-button`,
`review-item`, `category-filter`, `my-review`, `partner-review`, `add-review-form`,
`edit-review-button`, `delete-review-button`, `confirm-delete-review-button`, `review-edited`.

The nav badge is found by Flux's own `data-flux-navlist-badge` marker instead of a
`data-test` hook, because Flux copies `badge:` attributes onto the nav link as well.
