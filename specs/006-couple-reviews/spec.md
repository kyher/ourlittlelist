# Feature Specification: Couple Reviews

**Feature Branch**: `couple-reviews`

**Created**: 2026-11-07

**Status**: Draft

**Input**: User description: "Issue 4 on GH. A user in a couple should be able to add reviews for various things such as film, tv shows, food. Each review has a rating from 1 to 5 stars, going up in 0.5 star increment. Reviews have stars and a text body to be filled - both required. A review can be edited, and be removed. When a review is created, the other user in the couple should see a prompt to review the same thing."

GitHub issue #4 ("Add ability to add a review for various things"):
- Add a review for various categories - film, show, food, restaurant
- Review is under a couple but tied to one of the couple (the one who added it).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Review something new (Priority: P1)

Kyle and Harry are a couple. They watched a film last night. Kyle opens the couple's reviews, chooses to add a review, picks the category "Film", enters the title "Paddington 2", gives it 4.5 stars, and writes a few sentences about it. The review is saved under their couple and shows Kyle as the author.

**Why this priority**: Writing a review is the core of the app. Nothing else in this feature has value without it.

**Independent Test**: With an active couple of Kyle and Harry, sign in as Kyle, add a Film review for "Paddington 2" with 4.5 stars and a text body, and confirm it appears in the couple's review list with Kyle as author. Try to submit without a rating, or without text, and confirm it is refused.

**Acceptance Scenarios**:

1. **Given** Kyle is in a couple, **When** he adds a review choosing a category, entering a title, picking 4.5 stars, and writing a text body, **Then** the review is saved under the couple, shows Kyle as its author, and appears in the couple's review list.
2. **Given** Kyle is adding a review, **When** he submits without a star rating, **Then** the review is not saved and he is told a rating is required.
3. **Given** Kyle is adding a review, **When** he submits with an empty (or whitespace-only) text body, **Then** the review is not saved and he is told the review text is required.
4. **Given** Kyle is adding a review, **When** he submits without a title or category, **Then** the review is not saved and he is told what is missing.
5. **Given** Kyle is adding a review, **When** he picks a rating, **Then** he can only choose one of 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, or 5 stars.
6. **Given** Kyle is not in a couple, **When** he tries to open or add reviews, **Then** he is refused and directed to connect with a partner first.

---

### User Story 2 - Partner is prompted to review the same thing (Priority: P1)

After Kyle reviews "Paddington 2", Harry signs in and sees a prompt: Kyle reviewed "Paddington 2" (Film) — add your review. Harry picks it, and the category and title are already filled in, so he only gives his stars and text. Both reviews are now shown together for that film so they can compare.

**Why this priority**: The value of the app is a shared, comparable list (Constitution Principle V). The prompt is how the second review gets written.

**Independent Test**: With Kyle having reviewed "Paddington 2", sign in as Harry, confirm a prompt for "Paddington 2" is shown, answer it with a rating and text, and confirm the prompt is gone and both reviews show side by side on that item.

**Acceptance Scenarios**:

1. **Given** Kyle has reviewed "Paddington 2" and Harry has not, **When** Harry signs in, **Then** he sees a prompt to review "Paddington 2" naming Kyle as the partner who reviewed it.
2. **Given** Harry has a prompt for "Paddington 2", **When** he chooses to review it, **Then** the category and title are pre-filled and cannot be changed in that form, and he only needs to add a rating and text.
3. **Given** Harry submits his review of "Paddington 2", **When** the review is saved, **Then** his prompt for that item disappears and the item shows both Kyle's and Harry's reviews together.
4. **Given** Harry has a prompt for "Paddington 2", **When** he dismisses it, **Then** the prompt disappears for him and is not shown again, and he can still review the item later from the item itself.
5. **Given** Kyle has reviewed "Paddington 2", **When** Kyle looks at his own prompts, **Then** he is not prompted for something he has already reviewed.
6. **Given** Kyle already reviewed "Paddington 2", **When** Harry tries to add a new review for a Film titled "paddington 2", **Then** he is directed to the existing item instead of creating a duplicate.
7. **Given** Kyle has reviewed "Paddington 2" and Harry has not, **When** Harry opens the item, **Then** he can see Kyle's full review (rating and text) before writing his own.

---

### User Story 3 - Edit or remove my own review (Priority: P2)

Kyle rewatched "Paddington 2" and liked it even more. He edits his review to 5 stars and updates the text. Later he adds a review of the wrong restaurant by mistake and removes it.

**Why this priority**: Mistakes and changed opinions are common, but the feature still delivers value without editing for a first release.

**Independent Test**: With a review by Kyle, sign in as Kyle, change its rating and text and confirm the changes show. Remove it after a confirmation step and confirm it is gone. Sign in as Harry and confirm he cannot edit or remove Kyle's review.

**Acceptance Scenarios**:

1. **Given** Kyle has a review, **When** he edits its rating and text and saves, **Then** the review shows the new rating and text, and indicates it was edited.
2. **Given** Kyle is editing his review, **When** he clears the rating or the text and saves, **Then** the change is refused with the same rules as creating a review.
3. **Given** Kyle has a review, **When** he chooses to remove it, **Then** he is asked to confirm, and after confirming the review is permanently deleted.
4. **Given** Kyle removes his review of an item that Harry has also reviewed, **When** the removal completes, **Then** the item and Harry's review remain, no new prompt is created for either partner, and Kyle can review the item again from the item itself.
5. **Given** Kyle removes his review of an item that nobody else has reviewed, **When** the removal completes, **Then** the item is removed too and Harry's prompt for it disappears.
6. **Given** Kyle has a review, **When** Harry tries to edit or remove it, **Then** the action is refused.

---

### User Story 4 - Browse the couple's reviews (Priority: P2)

Kyle and Harry open their reviews and see every item they have reviewed, newest first, with each partner's rating. They filter to "Restaurant" to decide where to go for dinner again.

**Why this priority**: Once there are reviews, the couple needs to find and compare them. Basic listing comes with Story 1; filtering is an improvement.

**Independent Test**: With several reviews across categories, sign in as either partner, confirm all of the couple's items are listed with both partners' ratings, and filter by a category to see only that category.

**Acceptance Scenarios**:

1. **Given** the couple has reviewed several items, **When** Kyle opens the review list, **Then** he sees each item's title, category, and each partner's rating (or that a partner has not reviewed it), newest activity first.
2. **Given** the couple has reviews in several categories, **When** Kyle filters by "Restaurant", **Then** only restaurant items are shown.
3. **Given** Kyle opens an item, **When** the item has reviews from both partners, **Then** both reviews (author, rating, text, date) are shown side by side for comparison.
4. **Given** another couple has reviews, **When** Kyle browses, searches, or opens items by direct link, **Then** he never sees or reaches any item or review that belongs to another couple.

---

### Edge Cases

- A partner adds an item with the same category and title (ignoring case and surrounding spaces) as an existing item in the couple: no duplicate is created; they are taken to the existing item to add their own review there.
- The same title in different categories (e.g. "Dune" as a Film and as a Book) are separate items.
- A user tries to add a second review of an item they have already reviewed: refused; they are offered to edit their existing review instead.
- A user submits a rating outside the allowed values (e.g. 0, 0.25, 3.3, 6) by tampering with the form: refused.
- Very long text: refused above the maximum length with a clear message (see FR-005).
- Harry answers a prompt at the same moment Kyle removes the only review for that item: Harry's submit fails with a clear message that the item no longer exists.
- A user opens an item, review, or edit link from another couple, or from their own old couple: refused, and nothing about the item is revealed.
- The couple ends: all items, reviews, and prompts belonging to the couple are permanently deleted (feature 005, FR-017).
- A user's partner ends the couple while the user is writing a review: the submit fails with a clear message that they are no longer in a couple.

## Requirements *(mandatory)*

### Functional Requirements

**Access**

- **FR-001**: Only signed-in users who are in a couple MUST be able to view, create, edit, or remove reviews. Users not in a couple MUST be directed to connect with a partner.
- **FR-002**: Every item, review, and prompt MUST belong to exactly one couple, and MUST be visible and reachable only by the two members of that couple.

**Items and categories**

- **FR-003**: Each review MUST be for an item. An item has a category and a title and belongs to the couple. The available categories MUST be: Film, TV show, Book, Food, and Restaurant.
- **FR-004**: Within a couple, there MUST be at most one item for a given category and title (title compared ignoring case and surrounding spaces). Adding a review for an existing category and title MUST attach it to the existing item.

**Writing reviews**

- **FR-005**: A review MUST have a star rating and a text body, both required. The text body MUST contain non-whitespace text and MUST be no longer than 5,000 characters. The title MUST be no longer than 255 characters.
- **FR-006**: The rating MUST be one of 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, or 5 stars. Any other value MUST be refused.
- **FR-007**: Each review MUST record its author (the couple member who wrote it), the date it was created, and the date it was last edited.
- **FR-008**: Each member MUST have at most one review per item.

**Editing and removing**

- **FR-009**: The author of a review MUST be able to edit its rating and text, using the same validation rules as creation. An edited review MUST show that it has been edited.
- **FR-010**: The author of a review MUST be able to remove it after an explicit confirmation step. Removal is permanent.
- **FR-011**: When an item has no reviews left, the item and any prompts for it MUST be removed.
- **FR-012**: A member MUST NOT be able to edit or remove their partner's reviews; their partner's reviews are read-only to them.

**Prompts to review**

- **FR-013**: When a member creates the first review of an item, the system MUST show the other member an in-app prompt to review the same item until they review it or dismiss it. The prompt MUST name the item, its category, and which partner reviewed it.
- **FR-014**: A member MUST be able to see their partner's full review (rating and text) at any time, including before writing their own review of the same item. Reviews are not hidden.
- **FR-015**: Answering a prompt MUST open the review form with the category and title already set to that item, so the member only adds a rating and text.
- **FR-016**: A member MUST be able to dismiss a prompt. A dismissed prompt MUST NOT be shown again for that item, but the member MUST still be able to review the item later from the item itself.
- **FR-017**: A member MUST NOT receive a prompt for an item they have already reviewed. Editing a review MUST NOT create a new prompt.

**Browsing**

- **FR-018**: Members MUST be able to see a list of the couple's items showing title, category, and each partner's rating (or that they have not reviewed it), ordered by most recent review activity, and filterable by category.
- **FR-019**: Members MUST be able to open an item and see both partners' reviews of it together for comparison.

**Validation and authorization**

- **FR-020**: Every create, edit, remove, dismiss, and view action MUST be authorized against the current user's couple and the review's author, and refused otherwise, without revealing whether the requested item or review exists.
- **FR-021**: All refused actions and validation failures MUST give the user a clear, plain-language reason.
- **FR-022**: When a couple ends, all of its items, reviews, and prompts MUST be permanently deleted (feature 005, FR-017).

### Key Entities

- **Item**: A thing the couple has reviewed, such as a film or a restaurant. It has a category, a title, and the couple it belongs to. It is created along with its first review, and removed when its last review is removed.
- **Category**: The kind of item: Film, TV show, Book, Food, or Restaurant. Shared review behaviour (rating, text, author, dates) is the same for every category, so more can be added later.
- **Review**: One member's opinion of one item. It has an author, a rating (0.5–5 in half-star steps), a text body, a created date, and a last-edited date. Each member has at most one review per item.
- **Review Prompt**: A request to one member to review an item their partner has reviewed. It is open until the member reviews the item or dismisses it, or the item is removed.
- **Couple** and **User**: Existing from feature 005. Items, reviews, and prompts belong to a couple and are deleted with it.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A member can add a review of a new item in under 1 minute.
- **SC-002**: A member answering a prompt can submit their review in under 30 seconds, without retyping the category or title.
- **SC-003**: 100% of reviews saved have a rating in the allowed half-star steps and a non-empty text body.
- **SC-004**: 100% of attempts to view or change another couple's items or reviews, or to edit or remove a partner's review, are refused.
- **SC-005**: After a member's first review of an item, the partner sees the prompt on their next page load, every time.
- **SC-006**: 0 duplicate items (same category and title) exist within a couple.
- **SC-007**: 100% of refused actions and validation errors show a reason the user can understand without help.

## Assumptions

- Ratings start at 0.5 stars rather than the 1 star in the original request. Changed after review on 2026-11-07, because a scale of half-star steps that can't go below 1 felt odd.
- Categories are a fixed list for now: Film, TV show, Book, Food, and Restaurant. This combines the request (film, TV shows, food), issue #4 (film, show, food, restaurant), and the constitution (films, series, books, food, restaurants). Users cannot add their own categories.
- Items have only a category and a title. Category-specific details (e.g. author, restaurant location), images, and lookups from external film or book databases are out of scope.
- Prompts are in-app only, shown on the user's next visit. Email or push notifications are out of scope.
- Only the first review of an item creates a prompt. Removing and re-adding a review does not prompt the partner again if they have already reviewed or dismissed it.
- Item titles cannot be renamed after creation. A wrong title is fixed by removing the review and adding a new one.
- Search by title, sorting options other than most recent, and comments on reviews are out of scope.
- This feature depends on feature 005 (couple management): couples must exist, and ending a couple deletes its data.
