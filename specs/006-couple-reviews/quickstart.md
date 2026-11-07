# Quickstart: Couple Reviews

This guide shows how to check that the feature works. It points to [data-model.md](data-model.md)
for the rules and [contracts/ui-routes.md](contracts/ui-routes.md) for the pages and actions.

## Prerequisites

```bash
docker compose up -d
docker compose exec app php artisan migrate
```

You need two verified users with usernames who are a couple (Kyle and Harry), plus a third user
in a different couple (Sam and Alex). You can create them through the UI from feature 005, or in
tinker with `Couple::factory()->withMembers($kyle, $harry)->create()`.

## Automated checks

```bash
docker compose exec app php artisan test --filter=Reviews
docker compose exec app composer ci:check     # Pint, PHPStan, full suite (required before PR)
```

The tests go in `tests/Feature/Reviews/`:

| Test class | Covers |
|------------|--------|
| `CreateReviewTest` | US1 happy path. Missing or blank stars, body, title and category. Ratings 0, 0.25, 3.3 and 6 are refused, and 0.5 is accepted. Body over 5,000 characters. Title over 255 characters. Same title with different case or spacing attaches to the existing item. The same title in another category makes a new item. A second review by the same user is refused. A user who isn't in a couple is redirected |
| `ReviewPromptTest` | The first review creates a prompt for the partner only. Answering removes the prompt. Dismissing hides it for good. A review added from the item page still works after dismissing. An edit creates no prompt. Removing and re-adding doesn't prompt again. The nav badge count is correct |
| `EditDeleteReviewTest` | An edit updates the stars and body and sets the "Edited" marker. An edit with a blank body or missing stars is refused. Removing keeps the item when the partner has a review. Removing the last review deletes the item and its prompt. Answering a prompt after the item is deleted gives "This item no longer exists." |
| `BrowseReviewsTest` | Items are in most-recent-activity order. The category filter works and an invalid filter is ignored. Both ratings appear on the index. Both reviews appear on the item page, and the partner's review is visible before the user writes their own |
| `ReviewAuthorizationTest` | Another couple's item gives a 404 on `reviews.show` and on every action. A partner can't update or delete the other partner's review (403). A prompt can't be dismissed by anyone other than its recipient. After `EndCouple`, every item, review and prompt of that couple is deleted |

## Manual walkthrough

1. Sign in as **Kyle**. You land on `/reviews`, which shows "No reviews yet".
2. Click **Add review**. Choose **Film**, enter "Paddington 2", pick **4.5** stars and write some
   text, then save. → You land on the item page and see your review.
3. Try to save a new review with no stars, then with only spaces as the text. → Each attempt shows
   an error and nothing is saved.
4. Sign in as **Harry**. → The **Reviews** nav shows a badge of 1, and `/reviews` shows the prompt
   "Kyle reviewed Paddington 2 (Film)".
5. Open the prompt. → Kyle's full review is visible, the title and category are fixed, and Harry
   only enters stars and text. Save it. → The prompt is gone and both reviews are side by side.
6. As Harry, try **Add review** with Film and "  PADDINGTON 2 ". → You're told you've already
   reviewed it, and no duplicate item is created.
7. As Kyle, edit the review to 5 stars. → It shows "Edited". Harry sees no new prompt.
8. As Kyle, review a **Restaurant**. As Harry, dismiss that prompt. → It doesn't come back, but
   Harry can still review the item from its page.
9. As Kyle, remove the restaurant review and confirm. → The item disappears for both of you.
10. Sign in as **Sam** and open `/reviews/{id of Paddington 2}`. → You get a 404.
11. As Kyle, end the couple from `/couple`. → Running
    `docker compose exec app php artisan tinker --execute="dump(App\Models\Item::count(), App\Models\Review::count())"`
    shows that none of Kyle and Harry's rows are left.
