# Feature Specification: Filter, Search and Sort Reviews

**Feature Branch**: `filtering-reviews`

**Created**: 2026-11-07

**Status**: Draft

**Input**: User description: "issue 6. We're going to add some filtering and searching to the reviews. A user in a couple on the review page can filter for - Reviews by themselves, reviews by other. They can also filter by review required by themselves. They can also filter by type (already in place). Additionally they can search the list which will search the name of the items. Addtionally, they can sort by their highest rated vs other member's highest rated."

GitHub issue #6 ("Show and filter reviews"):
- Should be able to show both reviews at the same time for different items, or filter by just one member or type.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Filter by who reviewed it (Priority: P1)

Kyle and Harry have reviewed lots of things. Kyle opens the reviews list and wants to see only what Harry has reviewed, so he can pick a film Harry rated. He switches the "Show" filter to "Reviewed by Harry". Later he switches to "Waiting for my review" to see everything Harry has reviewed that he hasn't got round to yet.

**Why this priority**: This is the core of issue #6: filtering by one member. "Waiting for my review" also gives the user a full to-do list, which the prompts alone don't, because prompts can be dismissed.

**Independent Test**: With a couple where some items are reviewed by Kyle only, some by Harry only and some by both, sign in as Kyle and check each option of the "Show" filter lists exactly the expected items.

**Acceptance Scenarios**:

1. **Given** the couple has items reviewed by Kyle only, by Harry only, and by both, **When** Kyle picks "All reviews" (the default), **Then** every item of the couple is listed.
2. **Given** the same items, **When** Kyle picks "Reviewed by me", **Then** only items Kyle has reviewed are listed (including those Harry has also reviewed).
3. **Given** the same items, **When** Kyle picks "Reviewed by Harry", **Then** only items Harry has reviewed are listed (including those Kyle has also reviewed).
4. **Given** the same items, **When** Kyle picks "Waiting for my review", **Then** only items Harry has reviewed and Kyle has not are listed, including items whose prompt Kyle dismissed.
5. **Given** Kyle has picked "Reviewed by Harry", **When** Harry signs in and opens the same list, **Then** Harry's own view is not affected; each member's choices apply only to their own view.

---

### User Story 2 - Search by name (Priority: P1)

Kyle can't remember whether they reviewed "The Grand Budapest Hotel". He types "budapest" in the search box and the list shrinks to the matching item.

**Why this priority**: As the list grows, finding one item by name is the fastest way to check whether something has been reviewed and what each partner thought.

**Independent Test**: With several items, sign in as Kyle, type part of one title in any letter case and confirm only items whose title contains that text are listed.

**Acceptance Scenarios**:

1. **Given** the couple has items "The Grand Budapest Hotel" and "Paddington 2", **When** Kyle searches for "budapest", **Then** only "The Grand Budapest Hotel" is listed.
2. **Given** the same items, **When** Kyle searches for "BUDAPEST" or " budapest ", **Then** the result is the same (letter case and surrounding spaces are ignored).
3. **Given** Kyle has searched, **When** he clears the search box, **Then** the full list (with any other filters still applied) comes back.
4. **Given** nothing matches, **When** Kyle searches for "zzz", **Then** he sees a message that nothing matches his search and filters, with a way to clear them.

---

### User Story 3 - Sort by highest rated (Priority: P2)

Kyle and Harry want to rewatch something good. Kyle sorts the list by "My highest rated" to see his favourites first, then by "Harry's highest rated" to see Harry's.

**Why this priority**: Useful for picking favourites, but the list is still usable with the existing newest-first order.

**Independent Test**: With items rated differently by each member, sign in as Kyle, pick each sort option and confirm the order.

**Acceptance Scenarios**:

1. **Given** no sort is chosen, **When** Kyle opens the list, **Then** items are ordered by most recent review activity, as today.
2. **Given** Kyle rated items 5, 3.5 and 4 stars, **When** he sorts by "My highest rated", **Then** they are listed 5, 4, 3.5, followed by items Kyle has not reviewed.
3. **Given** Harry rated items 2, 4.5 and 3 stars, **When** Kyle sorts by "Harry's highest rated", **Then** they are listed 4.5, 3, 2, followed by items Harry has not reviewed.
4. **Given** two items have the same rating from the chosen member, **When** sorted by that member's rating, **Then** the more recently active item comes first.

---

### User Story 4 - Combine filters, search and sort (Priority: P2)

Kyle wants Harry's favourite restaurants. He picks category "Restaurant", "Reviewed by Harry", and sorts by "Harry's highest rated". He refreshes the page and his choices are still there, and he can send the link to Harry.

**Why this priority**: Each control is useful on its own; combining them and keeping them on refresh makes the page practical without being needed for a first release.

**Independent Test**: Set a category, a "Show" option, a search and a sort together, confirm only items matching all of them are listed in the chosen order, then reload the page and confirm the same choices and results remain.

**Acceptance Scenarios**:

1. **Given** Kyle has chosen a category, a "Show" option, a search and a sort, **When** the list updates, **Then** only items matching all chosen filters and the search are shown, in the chosen order.
2. **Given** Kyle has made choices, **When** he reloads the page or opens the same link again, **Then** the same choices are applied.
3. **Given** a link with an unknown "Show", sort or category value, **When** Kyle opens it, **Then** the unknown value is ignored and its default is used, with no error.
4. **Given** Kyle has any filter or search applied, **When** he chooses to clear filters, **Then** the category, "Show" option and search are reset to their defaults.

---

### Edge Cases

- Kyle is not in a couple: the page refuses him and directs him to connect with a partner, as today (feature 006, FR-001).
- Another couple has an item with the same title as Kyle's search: it is never listed (feature 006, FR-002).
- A search containing characters with special meaning in patterns (e.g. `%`, `_`, `\`) is matched literally as text.
- A search longer than 100 characters is refused or trimmed to 100 without an error page.
- A search of only spaces is treated as no search.
- Kyle's partner is named "Harry"; if the partner's name is unavailable, labels use "your partner" (as the list already does).
- Kyle sorts by his highest rated while "Waiting for my review" is selected: every item lacks Kyle's rating, so the list falls back to most recent activity order.
- The "Waiting for your review" prompts section above the list is not affected by the filters, search, or sort.
- A filter or search returns no items while the couple does have reviews: a "nothing matches" message is shown, not the "No reviews yet" message for an empty couple.

## Requirements *(mandatory)*

### Functional Requirements

**Show filter (who reviewed)**

- **FR-001**: The reviews list MUST offer a "Show" filter with these options: All reviews (default), Reviewed by me, Reviewed by [partner's name], and Waiting for my review.
- **FR-002**: "Reviewed by me" MUST list only items the current user has reviewed. "Reviewed by [partner]" MUST list only items the partner has reviewed. Items both have reviewed MUST appear under both options.
- **FR-003**: "Waiting for my review" MUST list only items the partner has reviewed and the current user has not, whether or not the user dismissed the prompt for that item.

**Category filter**

- **FR-004**: The existing category filter MUST keep working and MUST combine with the other filters, the search, and the sort.

**Search**

- **FR-005**: The list MUST offer a search box that matches item titles containing the search text anywhere, ignoring letter case and surrounding spaces. Search text MUST be matched literally (no wildcard characters). Search text MUST be at most 100 characters.
- **FR-006**: The list MUST update as the user types, without a separate submit step, with a short pause so it doesn't update on every keystroke.

**Sort**

- **FR-007**: The list MUST offer a sort with these options: Most recent (default; most recent review activity first, as today), My highest rated, and [partner's name]'s highest rated.
- **FR-008**: When sorting by a member's rating, items MUST be ordered by that member's rating from highest to lowest; items that member has not reviewed MUST come last; ties MUST be ordered by most recent review activity.

**Combining and keeping choices**

- **FR-009**: Category, "Show", search and sort MUST all apply together: only items matching every filter and the search are listed, in the chosen order.
- **FR-010**: The current choices MUST be kept in the page address, so reloading or sharing the link reproduces the same view. Unknown or invalid values in the address MUST be ignored and the default used.
- **FR-011**: When any filter or search is applied, the user MUST be able to clear them back to their defaults in one action.
- **FR-012**: When filters or search return no items but the couple has items, the list MUST show a "nothing matches" message distinct from the empty-couple message.

**Scope and privacy**

- **FR-013**: Filters, search and sort MUST only ever consider the current user's couple's items and reviews (feature 006, FR-002). Only couple members can use the page (feature 006, FR-001).
- **FR-014**: The "Waiting for your review" prompts section MUST be unaffected by the list's filters, search and sort.

### Key Entities

No new data is stored. This feature reads existing data from feature 006:

- **Item**: Has a title (searched), a category (filtered) and its most recent review activity (default order).
- **Review**: Has an author (used by the "Show" filter) and a star rating (used by the rating sorts).
- **Couple** and **User**: Scope all results to the current user's couple, and identify "me" versus "my partner".

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A member can find a known item by name in under 10 seconds from opening the reviews list.
- **SC-002**: A member can list everything waiting for their review in one action (a single filter choice).
- **SC-003**: For each "Show" option, 100% of listed items match its rule, and 0 matching items are missing.
- **SC-004**: The list reflects a changed filter, search or sort in under 1 second for a couple with up to 1,000 items.
- **SC-005**: 100% of reloaded or shared links reproduce the same filters, search and sort.
- **SC-006**: 0 items or reviews from another couple ever appear in filtered, searched or sorted results.

## Assumptions

- "Reviews by themselves / by other" means items the member or the partner has reviewed. Since the list shows items with both partners' ratings side by side, filtering keeps that layout rather than switching to a list of individual reviews. This also covers issue #6's "show both reviews at the same time".
- "Review required by themselves" means items the partner has reviewed and the user hasn't. Dismissed prompts are included, so the filter is a complete to-do list, while the prompts section stays as it is.
- The "Show" options are one choice, not checkboxes, because "Waiting for my review" can't be combined with "Reviewed by me".
- Search covers item titles only, not review text.
- Only highest-first rating sorts are needed. Lowest-first, alphabetical, and an "average of both" sort are out of scope.
- Filter and sort choices are not saved between visits except through the page address.
- This feature depends on feature 006 (couple reviews) and its existing review list page.
