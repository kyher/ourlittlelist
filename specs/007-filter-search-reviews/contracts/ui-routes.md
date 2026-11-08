# UI Route Contract: Filter, Search and Sort Reviews

No new routes. Only `pages::reviews.index` (`/reviews`, `reviews.index`) changes. It keeps the
`auth, verified, has-username, in-couple` middleware from feature 006. Routes not listed here
behave as in `specs/006-couple-reviews/contracts/ui-routes.md`.

## `pages::reviews.index`

**Query string** (all optional; defaults are left out of the address):

| Param | Values | Default | Spec ref |
|-------|--------|---------|----------|
| `category` | `ReviewCategory` values | none (all) | FR-004 |
| `show` | `all`, `mine`, `partner`, `waiting` | `all` | FR-001 to FR-003 |
| `q` | free text, max 100 characters used | empty | FR-005, FR-006 |
| `sort` | `recent`, `mine`, `partner` | `recent` | FR-007, FR-008 |

Unknown values fall back to the default with no error (FR-010).

Example: `/reviews?category=restaurant&show=partner&sort=partner`

**State** (`#[Url]` public strings): `category` (unchanged), `show`, `search` (as `q`), `sort`.

**Computed values**:
- `prompts`: unchanged. Not affected by any filter, search or sort (FR-014).
- `filter`: unchanged (`?ReviewCategory`).
- `showFilter`: `ReviewShow`, from `show`.
- `sortOrder`: `ReviewSort`, from `sort`.
- `hasItems`: `bool`, whether the couple has any items at all.
- `items`: the couple's items matching category, show and search, in the chosen order. Each
  entry keeps its existing shape: `{id, title, category_label, my_stars|null, partner_stars|null}`.

| Action | Parameters | Success | Refusal |
|--------|------------|---------|---------|
| `dismissPrompt(int $id)` | prompt id | Unchanged | Unchanged |
| `clearFilters()` *(new)* | none | Resets `category`, `show` and `search` to defaults. `sort` is kept | none |

**Controls** (one row above the list, wrapping on small screens):

| Control | Type | Binding | `data-test` |
|---------|------|---------|-------------|
| Search | `flux:input` with a magnifying-glass icon, placeholder "Search by name" | `wire:model.live.debounce.300ms="search"` | `review-search` |
| Show | `flux:select`: All reviews / Reviewed by me / Reviewed by :partner / Waiting for my review | `wire:model.live="show"` | `show-filter` |
| Category | `flux:select` (unchanged) | `wire:model.live="category"` | `category-filter` |
| Sort | `flux:select`: Most recent / My highest rated / :partner's highest rated | `wire:model.live="sort"` | `sort-select` |
| Clear filters | `flux:button` ghost, shown only when category, show or search is set | `wire:click="clearFilters"` | `clear-filters-button` |

**Empty states**:

| Condition | Shown | `data-test` |
|-----------|-------|-------------|
| `hasItems` is false | "No reviews yet" card with an **Add review** link (unchanged) | `reviews-empty` |
| `hasItems` is true and `items` is empty | "Nothing matches your search and filters." with a **Clear filters** button | `reviews-no-matches` |
