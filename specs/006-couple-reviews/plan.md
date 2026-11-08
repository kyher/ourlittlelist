# Implementation Plan: Couple Reviews

**Branch**: `add-reviews` | **Date**: 2026-11-07 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/006-couple-reviews/spec.md`

## Summary

Members of a couple review **items**. An item is a category plus a title and belongs to the
couple. Each member writes at most one review per item, with a rating in half-star steps from 0.5
to 5 and a required text body.

- **Prompts**: the first review of an item creates a **prompt** row for the partner. It shows on
  the new `/reviews` landing page and as a nav badge until the partner answers or dismisses it.
- **Comparison**: both reviews are shown side by side on the item page and are always visible.
- **Edit and remove**: authors can edit or remove only their own review. Removing the last review
  removes the item.
- **Deletion with the couple**: all review data cascades from `couples`, so ending a couple
  (feature 005) deletes it.

Rules live in `app/Actions/Reviews` (in transactions). Policies and couple-scoped lookups enforce
privacy. Three Livewire pages provide the UI.

## Technical Context

**Language/Version**: PHP 8.4 (Docker app image)

**Primary Dependencies**: Laravel 13, Livewire 4, Flux UI 2 (free), Laravel Fortify. No new packages

**Storage**: MySQL in Docker for development. SQLite in tests and CI. The migrations create
`items`, `reviews` and `review_prompts`, all cascading from `couples`.

**Testing**: Class-based PHPUnit (`Tests\TestCase`) with Livewire test helpers, in
`tests/Feature/Reviews/`. `docker compose exec app composer ci:check` runs before the PR

**Target Platform**: Linux containers (Docker Compose)

**Project Type**: Server-rendered web application (single Laravel project)

**Performance Goals**: Each page needs a few indexed queries. The index eager-loads reviews, so
there are no N+1 queries. A member can review a new item in under 1 minute (SC-001)

**Constraints**:
- No data from another couple is ever reachable. Out-of-couple ids give a 404.
- There are never duplicate items. A unique index enforces this even under races.
- Ratings can only be 0.5 to 5 in steps of 0.5.
- Ending a couple deletes all review data.

**Scale/Scope**: Two users per couple and a small user base. The work is:
- 3 migrations, 3 models, 1 enum, 4 actions, 1 exception, 3 policies, 1 middleware and 1 form object
- 3 new Livewire pages and 2 Blade components
- changes to the header nav and the `partner.search` redirect

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Evidence |
|-----------|--------|----------|
| I. Laravel & Livewire starter kit | ✅ | Eloquent models, a backed enum, migrations, factories and policies. A Livewire Form object, `app/Actions` classes, and Livewire single-file pages using Flux. No SPA framework |
| II. Docker-first | ✅ | Every command in the quickstart runs through `docker compose exec app` |
| III. Couple-scoped privacy | ✅ | Every item lookup is scoped with `whereBelongsTo($user->couple)`, so out-of-couple ids give a 404 (R9). `ReviewPolicy` limits update and delete to the author, so a partner's review is read-only (FR-012). `ReviewPromptPolicy` limits dismissing to the recipient. The `in-couple` middleware applies to all pages, and actions re-check couple membership in the transaction. **Disconnect outcome**: it cascades `couples → items → reviews/prompts`, as feature 005's FR-017 requires, and a test covers it |
| IV. Tested behaviour | ✅ | Five feature test classes (see the quickstart). They cover happy paths, every validation rule, authorization (other couple, partner, non-recipient) and the cascade when a couple ends |
| V. Extensible categories | ✅ | Categories are a `ReviewCategory` enum. One `items` table, one `reviews` table and one set of pages serve every category, so a new category is one enum case. Both partners review the same item independently, and the item page compares them side by side |
| VI. Simplicity | ✅ | No packages, queues or notifications. Prompts are plain rows that show on the next page load. Free Flux plus two small Blade components for the stars. One middleware replaces three `mount()` redirects (R9) |
| Stack constraints | ✅ | Schema changes go through migrations. Validation happens on the server in the Form object and the actions. Review text is escaped by Blade (`{{ }}`) and never rendered as raw HTML |

**Post-design re-check**: ✅ All gates still pass after Phase 1. There are no violations, so
Complexity Tracking is empty.

## Project Structure

### Documentation (this feature)

```text
specs/006-couple-reviews/
├── plan.md              # This file
├── research.md          # Phase 0: decisions R1–R12
├── data-model.md        # Phase 1: tables, enum, validation, side effects
├── quickstart.md        # Phase 1: validation guide
├── contracts/
│   └── ui-routes.md     # Phase 1: routes, component actions, modals, data-test hooks
└── tasks.md             # Phase 2 (/speckit-tasks)
```

### Source Code (repository root)

```text
app/
├── Actions/Reviews/
│   ├── CreateReview.php             # new: new or existing item, prompt for partner (R4–R7)
│   ├── UpdateReview.php             # new: stars, body, edited_at
│   ├── DeleteReview.php             # new: removes the item when its last review goes
│   └── DismissReviewPrompt.php      # new
├── Enums/ReviewCategory.php         # new
├── Exceptions/ReviewActionException.php   # new: user-facing message (FR-021)
├── Http/Middleware/EnsureUserIsInCouple.php  # new: alias `in-couple`
├── Livewire/Forms/ReviewForm.php    # new: shared field validation
├── Models/
│   ├── Item.php                     # new
│   ├── Review.php                   # new
│   ├── ReviewPrompt.php             # new
│   ├── Couple.php                   # + items()
│   └── User.php                     # + reviews(), reviewPrompts(), openReviewPromptCount()
└── Policies/
    ├── ItemPolicy.php               # new: view, review
    ├── ReviewPolicy.php             # new: update, delete
    └── ReviewPromptPolicy.php       # new: dismiss
bootstrap/app.php                    # + `in-couple` alias
database/
├── factories/ItemFactory.php, ReviewFactory.php, ReviewPromptFactory.php   # new
└── migrations/
    ├── 2026_11_07_000002_create_items_table.php
    ├── 2026_11_07_000003_create_reviews_table.php
    └── 2026_11_07_000004_create_review_prompts_table.php
resources/views/
├── components/star-rating.blade.php        # new: display
├── components/star-rating-input.blade.php  # new: half-star radio input
├── pages/reviews/⚡index.blade.php          # new: prompts, list, filter
├── pages/reviews/⚡create.blade.php         # new
├── pages/reviews/⚡show.blade.php           # new: compare, add, edit, remove
├── pages/partner/⚡search.blade.php         # users in a couple are redirected to reviews.index
└── layouts/app/header.blade.php            # + Reviews nav item with prompt badge
routes/web.php                              # + reviews.* routes in an `in-couple` group
tests/Feature/Reviews/
├── CreateReviewTest.php
├── ReviewPromptTest.php
├── EditDeleteReviewTest.php
├── BrowseReviewsTest.php
└── ReviewAuthorizationTest.php
tests/Feature/Couples/CouplePagesTest.php   # update the expected redirect for users in a couple (/reviews)
```

**Structure Decision**: This is a single Laravel project that follows the layout from feature 005:
- rules in `app/Actions/Reviews`
- Livewire single-file pages under `resources/views/pages/reviews/`
- class-based PHPUnit tests in `tests/Feature/Reviews/`

## Complexity Tracking

No constitution violations.
