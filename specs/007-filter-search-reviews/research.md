# Research: Filter, Search and Sort Reviews

Builds on `specs/006-couple-reviews/research.md`. Nothing in the Technical Context was left
open; each decision below records the choice and why.

## R1. Filter in the database with `Item` query scopes

**Decision**: Add four local scopes to `App\Models\Item` and chain them in the index page's
`items` computed property, after `forCouple()`:

- `reviewedBy(User $user)`: `whereHas('reviews', user_id = $user->id)`
- `notReviewedBy(User $user)`: `whereDoesntHave('reviews', user_id = $user->id)`
- `titleContains(string $search)`: see R3
- `orderByRatingOf(User $user)`: see R4

The "Show" options map to the scopes like this:

| Show | Query |
|------|-------|
| `all` | no extra scope |
| `mine` | `reviewedBy($me)` |
| `partner` | `reviewedBy($partner)` |
| `waiting` | `reviewedBy($partner)->notReviewedBy($me)` |

**Rationale**: The `reviews (item_id, user_id)` unique index covers the `EXISTS` subqueries.
Named scopes keep the Livewire component short and can be unit tested on their own. They are
also how feature 006 already scoped items (`forCouple`). `forCouple()` always comes first,
so no scope can reach another couple's items (Principle III).

**Alternatives considered**:
- *Filter the loaded collection in PHP*: simple at today's size, but loads every item and
  review on each keystroke, and spreads the rules across Blade-side code.
- *Use `review_prompts` for "Waiting"*: prompts are deleted on dismiss and are not created on
  re-review (006 R6), so they miss items. The spec wants every item the partner reviewed and
  the user hasn't (FR-003).

## R2. "Show" and sort as small backed enums

**Decision**: Add `App\Enums\ReviewShow` (`all`, `mine`, `partner`, `waiting`) and
`App\Enums\ReviewSort` (`recent`, `mine`, `partner`). Each has a `label(string $partnerName)`
method for the select options. The page reads the URL strings with `tryFrom()`, falling back
to `All` / `Recent`, as the category filter already does with `ReviewCategory::tryFrom()`.

**Rationale**: Unknown values from a link are ignored without an error (FR-010). Enums give
one list of valid values for the select, the query, and the tests.

**Alternatives considered**: string constants on the component. They work, but the
`tryFrom()` fallback and labels would be written by hand.

## R3. Search on `title_key` with a portable `LIKE`

**Decision**: Search with `title_key LIKE ? ESCAPE '!'`, binding
`'%' . escaped(mb_strtolower(trim($search))) . '%'`. Escaping puts `!` before `!`, `%` and
`_`. The search string is trimmed and cut to 100 characters before use. An empty result
after trimming means no search.

**Rationale**:
- `title_key` is already the trimmed, lowercased title (006 R4), so case-insensitive
  matching doesn't depend on the database collation (MySQL in dev, SQLite in tests).
- `!` as the escape character works the same in MySQL and SQLite. A backslash needs
  different quoting in each (`'\\'` in MySQL, `'\'` in SQLite), which is easy to get wrong.
- Escaping means `%` and `_` typed by the user are matched literally (spec edge case).
- Cutting to 100 characters, rather than showing a validation error, keeps a live search box
  quiet; nothing useful is lost.

**Alternatives considered**:
- *MySQL `FULLTEXT` or Laravel Scout*: needs an index or a package, doesn't run on SQLite,
  and is overkill for a two-person list (Principle VI).
- *`LOWER(title) LIKE`*: works, but repeats what `title_key` already stores.

## R4. Sort by a member's rating with a correlated subquery

**Decision**: `orderByRatingOf($user)` orders by a subquery for that user's `reviews.rating`
on the item. Items without a rating come last (`ORDER BY (subquery) IS NULL`, then
`(subquery) DESC`). Ties then fall back to `updated_at desc, id desc`, the default order.

**Rationale**: MySQL and SQLite both put `NULL` first in `DESC` order unless told otherwise,
and neither supports `NULLS LAST` the same way. Ordering on `IS NULL` first is portable.
Ordering in SQL keeps one query for filter and sort.

**Alternatives considered**: sort the collection in PHP after loading. It works at this size,
but splits the order logic between SQL (`recent`) and PHP (ratings).

## R5. URL state with Livewire `#[Url]`

**Decision**: Keep all four choices as `#[Url]` public string properties on the page:
`category` (unchanged), `show`, `search` (as `q`) and `sort`. Use `except` with the default
value so a clean page keeps a clean address (`/reviews`). Bind the search box with
`wire:model.live.debounce.300ms` and the selects with `wire:model.live`.

**Rationale**: Livewire's `#[Url]` already gives reload and shareable links (FR-010) and is
what the category filter uses. The 300 ms debounce meets FR-006 without a submit button.

**Alternatives considered**: session-stored filters. The spec only asks for the address, and
sessions would make links unshareable.

## R6. Clearing filters and empty states

**Decision**: Add a `clearFilters()` action that resets `category`, `show` and `search` (not
`sort`, which only orders). Show a **Clear filters** button only when one of those is
set. Add a `hasItems` computed property (`Item::forCouple(...)->exists()`) so the page
can tell "No reviews yet" (the couple has no items) from "Nothing matches your filters" (it
does, but none match) (FR-012).

**Rationale**: The current page decides the empty message from the category filter alone.
With four controls, an explicit "has the couple got any items" check is simpler and correct.

## R7. Partner lookup and missing partner

**Decision**: Read the partner once through the existing `User::partner()`. If it is `null`
(the couple is being ended), the `partner` and `waiting` options and the partner rating sort
apply a condition that matches nothing (`whereRaw('1 = 0')`) / leave the default order,
rather than throwing.

**Rationale**: In normal use a member always has a partner. A defensive fallback avoids a
500 in the short window covered by 006 task T047, without taking on T047 itself.

## R8. Out of scope

- 006 task T047 (Livewire actions crashing when the couple ends while the page is open) is
  separate work and is not fixed here.
- No pagination: couples are expected to have well under 1,000 items (SC-004).
