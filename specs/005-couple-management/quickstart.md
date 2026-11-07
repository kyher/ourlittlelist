# Quickstart: Validating Couple Management

## Prerequisites

The Docker stack is running (`docker compose up -d`) and the front-end assets are built.

```bash
docker compose exec app php artisan migrate   # creates couples and couple_requests, adds users.couple_id
```

## Automated checks

```bash
docker compose exec app php artisan test --filter=Couple
docker compose exec app composer ci:check     # Pint + PHPStan + full suite (SQLite, matches CI)
```

The expected test files are all class-based PHPUnit tests extending `Tests\TestCase`:

- `tests/Feature/Couples/SendCoupleRequestTest.php`:
  - Sending to a single user works.
  - Each of the following is refused with its message: yourself, a coupled sender, a coupled
    receiver, a second outgoing request, and a duplicate request.
  - If the receiver has already sent a request to the sender, the sender is pointed to it and no
    new row is created.
  - The 11th request within an hour is rate-limited.
- `tests/Feature/Couples/RespondToCoupleRequestTest.php`:
  - Accepting creates a couple of exactly two users, and the other pending requests for both
    users become `closed`.
  - Declining creates no couple, and the sender can send again.
  - Cancelling works for the sender only.
  - Accept, decline and cancel are refused once the request is no longer pending.
  - Accepting when either user is already in a couple fails, creates no couple and closes the
    request.
  - Another user trying to accept, decline or cancel gets a 403.
- `tests/Feature/Couples/EndCoupleTest.php`:
  - Either member can end the couple, after which both are single and can search again.
  - A non-member gets a 403.
  - Deleting a member's account ends the couple and frees the partner.
- `tests/Feature/Couples/CouplePagesTest.php`:
  - Users in a couple are redirected from `/partner/search` to `/couple`, and single users the
    other way.
  - The nav label matches the user's state.
  - The end confirmation modal contains the warning about deleting reviews.
  - The search result for a user in a couple shows "Not available" and never their partner's
    name.
  - No email address appears on either page.
- `tests/Feature/PartnerSearchTest.php` (existing): still passes unchanged for single users.

## Manual walkthrough

1. Register `kyle` and `harry` (plus `sam` for the multi-request checks). You don't need to verify
   any emails: `User` doesn't implement `MustVerifyEmail`, so new accounts can use the app
   straight away.
2. **Send** (US1):
   - As Kyle, search `harry` and click **Send request**. The panel shows "waiting for Harry".
   - Search `sam`. **Send request** is disabled.
   - Cancel the request, then send it again.
3. **Reject** (US2):
   - As Harry, open the app. Kyle's request is listed. Click **Reject**.
   - As Kyle, the panel now shows "Harry declined your request". Send it again.
4. **Accept** (US2):
   - As Sam, send a request to Harry.
   - As Harry, accept Kyle's request. Harry lands on `/couple` and sees Kyle.
   - As Sam, the panel shows "Harry is no longer available".
5. **Unavailable**: as Sam, search `kyle`. The card shows "Not available" and does not mention
   Harry.
6. **End** (US3):
   - As Kyle, on `/couple`, click **End couple**. The modal warns that the reviews will be
     deleted.
   - Click **Cancel**. Nothing changes.
   - Open the modal again and confirm. Kyle lands on `/partner/search`.
   - As Harry, reload. Harry is also back on `/partner/search`.
