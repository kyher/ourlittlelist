# Implementation Plan: Filter, Search and Sort Reviews

**Branch**: `filtering-reviews` | **Date**: 2026-11-07 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/007-filter-search-reviews/spec.md`

## Summary

Add a "Show" filter, a title search and a rating sort to the existing reviews list
(`pages::reviews.index`), alongside the category filter it already has. Everything is done in
one couple-scoped query:

- **Show**: `Item` scopes `reviewedBy()` / `notReviewedBy()` built on `EXISTS` subqueries
  against `reviews` (R1). "Waiting for my review" is partner-reviewed and not me-reviewed,
  so dismissed prompts are still included.
- **Search**: `title_key LIKE ? ESCAPE '!'`, case-insensitive through the stored lowercase key,
  with `%` and `_` matched literally (R3).
- **Sort**: by the chosen member's rating with a subquery, unrated items last, ties by most
  recent activity (R4).
- **State**: all four choices are `#[Url]` properties, so links and reloads keep them (R5).
  A **Clear filters** action and a distinct "nothing matches" message round it off (R6).

No schema changes and no new packages.

## Technical Context

**Language/Version**: PHP 8.4 (Docker app image)

**Primary Dependencies**: Laravel 13, Livewire 4, Flux UI 2 (free). No new packages

**Storage**: MySQL 8.4 in Docker for development; SQLite in tests and CI. No migrations. Queries
must behave the same on both (R3, R4)

**Testing**: Class-based PHPUnit (`Tests\TestCase`) with Livewire test helpers, run with
`docker compose exec app php artisan test`; `composer ci:check` before a PR

**Target Platform**: Linux containers (Docker Compose)

**Project Type**: Server-rendered web application (single Laravel project)

**Performance Goals**: One indexed query for the list per change; results in under 1 second
for up to 1,000 items per couple (SC-004)

**Constraints**:
- Every query starts from `Item::forCouple()` (Principle III).
- Unknown query-string values fall back to defaults with no error (FR-010).
- Search text is escaped and matched literally, at most 100 characters (FR-005).
- The prompts section is not affected by any control (FR-014).

**Scale/Scope**: Two users per couple. One Livewire page changed, two small enums, four model
scopes, and their tests

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Laravel & Livewire conventions | ✅ | Changes stay in the existing Livewire page with Flux controls, Eloquent scopes and PHP enums. No SPA code |
| II. Docker-first | ✅ | No new services. Tests and checks run in the `app` container |
| III. Couple-scoped privacy | ✅ | All filters, search and sort chain after `Item::forCouple($user->couple)`, so results never include another couple's items (FR-013). No new routes or write actions beyond `clearFilters()`, which only resets page state. A test proves a same-titled item in another couple never appears in search |
| IV. Tested behaviour | ✅ | Feature tests cover each Show option, search (case, spaces, literal `%`/`_`), each sort including unrated-last and ties, combinations, URL fallback, empty states, clear filters, and the cross-couple search |
| V. Extensible review categories | ✅ | The category filter still comes from `ReviewCategory::cases()`, and the new filters are category-agnostic |
| VI. Simplicity | ✅ | Uses `LIKE` on the existing `title_key` instead of full-text search or Scout; no schema changes; no pagination (R3, R8) |

**Post-design re-check**: ✅ All gates still pass after Phase 1. No violations, so Complexity
Tracking is empty.

## Project Structure

### Documentation (this feature)

```text
specs/007-filter-search-reviews/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output (no schema changes; enums and scopes)
├── quickstart.md        # Phase 1 output
├── contracts/
│   └── ui-routes.md     # Phase 1 output (reviews.index query string and controls)
├── checklists/
│   └── requirements.md  # From /speckit-specify
└── tasks.md             # Phase 2 output (/speckit-tasks, not created here)
```

### Source Code (repository root)

```text
app/
├── Enums/
│   ├── ReviewShow.php                       # new: all, mine, partner, waiting (+ labels)
│   └── ReviewSort.php                       # new: recent, mine, partner (+ labels)
└── Models/
    └── Item.php                             # changed: reviewedBy, notReviewedBy, titleContains, orderByRatingOf scopes

resources/views/pages/reviews/
└── ⚡index.blade.php                         # changed: show/q/sort URL state, clearFilters, hasItems, controls, empty states

tests/
├── Feature/Reviews/
│   ├── BrowseReviewsTest.php                # unchanged; must keep passing
│   ├── FilterReviewsTest.php                # new: Show filter, category combos, clear filters, empty states, URL fallback
│   ├── SearchReviewsTest.php                # new: search matching, escaping, length, cross-couple privacy
│   └── SortReviewsTest.php                  # new: rating sorts, unrated last, ties, default order
```

**Structure Decision**: Single Laravel project, following the layout from features 005 and 006.
Query rules live as `Item` scopes next to `forCouple()`; the page component only reads its URL
state, picks the scopes, and maps results to the existing array shape.

## Complexity Tracking

No constitution violations to justify.
