# Quickstart: Filter, Search and Sort Reviews

How to check the feature works end to end. See [contracts/ui-routes.md](contracts/ui-routes.md)
for the controls and [data-model.md](data-model.md) for the rules.

## Prerequisites

- The Docker stack is running: `docker compose up -d`.
- Migrations are up to date: `docker compose exec app php artisan migrate`. This feature adds none.
- Two users in a couple, e.g. Kyle and Harry (see `specs/005-couple-management/quickstart.md`).

## Automated checks

```sh
docker compose exec app php artisan test --filter=Reviews
docker compose exec app composer ci:check
```

Expected: all tests pass, including the new filter, search and sort tests, and the existing
`BrowseReviewsTest` (default order and category filter unchanged).

## Manual scenarios

Set up as Kyle and Harry:

| Item | Category | Kyle | Harry |
|------|----------|------|-------|
| Paddington 2 | Film | 5 | 4 |
| The Grand Budapest Hotel | Film | 3.5 | — |
| Dishoom | Restaurant | — | 4.5 |
| 100%_Juice | Food | — | 2 |

Signed in as Kyle, open `/reviews`:

1. **Show**: pick each option and check the list.
   - All reviews: all 4 items.
   - Reviewed by me: Paddington 2, The Grand Budapest Hotel.
   - Reviewed by Harry: Paddington 2, Dishoom, 100%_Juice.
   - Waiting for my review: Dishoom, 100%_Juice. Dismiss the Dishoom prompt first; it is still listed.
2. **Search**: type `BUDAPEST` and only The Grand Budapest Hotel shows. Type `%_` and only
   100%_Juice shows (characters matched literally). Clear the box and all items return.
3. **Sort**:
   - My highest rated: Paddington 2, The Grand Budapest Hotel, then the two Kyle hasn't rated.
   - Harry's highest rated: Dishoom, Paddington 2, 100%_Juice, then The Grand Budapest Hotel.
4. **Combine and reload**: pick category Restaurant, Reviewed by Harry, and Harry's highest
   rated. Only Dishoom shows. Reload; the same choices stay, and the address contains
   `category=restaurant&show=partner&sort=partner`.
5. **No matches**: search `zzz`. "Nothing matches your search and filters." shows; **Clear
   filters** brings back the list. The "Waiting for your review" prompts are unaffected throughout.
6. **Bad link**: open `/reviews?show=nope&sort=nope&category=nope`. The full list shows in
   most recent order with no error.
7. **Privacy**: as a user in another couple with an item titled "Paddington 2", search
   `paddington`. Only your own couple's item shows.
