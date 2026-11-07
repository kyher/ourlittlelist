# Research: Couple Management

The Technical Context has no open unknowns, because the stack was fixed by features 001 and 002.
The decisions below cover the design choices this feature introduces.

## R1: How couple membership is stored

- **Decision**: Add a `couples` table and a nullable `users.couple_id` foreign key that references
  it (`nullOnDelete`). A user's couple is whatever `couple_id` points to.
- **Rationale**: A single column means the database itself makes it impossible for a user to be
  in two couples (FR-013, SC-002). Ending a couple deletes the `couples` row. The foreign key then
  clears `couple_id` for both members (FR-016). Future couple-owned tables (such as reviews) will
  reference `couples.id` with `cascadeOnDelete`, so FR-017 is met by the database instead of by
  hand-written clean-up code.
- **Alternatives considered**:
  - `couples.user_one_id` and `user_two_id`, each with a unique index: a user could still sit in
    column one of one couple and column two of another, and every query has to check both
    columns.
  - A `couple_user` pivot table with a unique `user_id`: it gives the same guarantee as R1 but
    needs an extra table and join for no benefit.

## R2: Keeping a couple to two members and one couple per user under concurrency

- **Decision**: Accepting a request runs in a single database transaction:
  1. Re-read the request with `lockForUpdate` and check that it is still `pending`.
  2. Create the `couples` row.
  3. Run one conditional update:
     `User::whereKey([$sender, $receiver])->whereNull('couple_id')->update(['couple_id' => $couple->id])`.
  4. If fewer than 2 rows were updated, roll back and refuse with "<name> is already in a
     couple".
  5. Mark the request `accepted` and close the other pending requests (R4).
- **Rationale**: The conditional update is atomic on both MySQL and SQLite, so even two accepts
  racing for the same user cannot both succeed (SC-002). A couple only ever gets members in this
  one place, always exactly two, and only while both are single. That makes "no more than two
  members" true by construction.
- **Alternatives considered**: Relying only on `lockForUpdate`: SQLite (used by tests and CI)
  ignores it, so the guarantee would not be tested. A database trigger or check constraint: it
  doesn't work the same way on MySQL and SQLite, and adds complexity (Principle VI).

## R3: Stopping duplicate pending requests and the single outgoing request rule

- **Decision**: `SendCoupleRequest` runs in a transaction. It locks both user rows in id order
  (`lockForUpdate`), then re-checks in this order:
  1. The receiver is not the sender.
  2. Neither user is in a couple.
  3. There is no pending request from the receiver to the sender. If there is one, the action
     refuses and points the sender to that incoming request (FR-007).
  4. The sender has no other pending outgoing request (FR-005).
  5. The rate limit allows it (R6).
- **Rationale**: MySQL has no partial unique indexes, so "one pending request per sender" can't be
  a portable unique constraint. Locking the user rows serialises the competing sends on MySQL.
  SQLite allows only one writer at a time anyway. At this scale (pairs of users) the window is
  tiny, and a rare duplicate would cause no harm, because accepting re-checks everything (R2) and
  closes the other requests (R4).
- **Alternatives considered**: A `pending_pair_key` column with a unique index, set to null when a
  request is closed: it works on both databases, but it adds a column that exists only for a race
  that is already harmless.

## R4: What a request records over its life

- **Decision**: Keep request rows and use a backed enum `CoupleRequestStatus` with the values
  `pending`, `accepted`, `declined`, `cancelled` and `closed`:
  - `cancelled`: the sender withdrew the request.
  - `closed`: the system closed it automatically, because either user joined a couple (FR-012) or
    because a request was refused when accepted (R2).
- **Rationale**: The sender needs to see "declined" (FR-008, US2-4). If the system closed the
  request, it would be misleading to show "declined", so `closed` shows "<name> is no longer
  available" instead. The sender's panel shows only their latest outgoing request when it is
  `pending`, `declined` or `closed`. Sending a new request replaces it, and the old one can also be
  dismissed. `responded_at` records when the request left `pending`.
- **Alternatives considered**: Deleting rows when a request is cancelled or declined: the sender
  could then never see "declined", which US2-4 requires.

## R5: Authorization

- **Decision**:
  - `CoupleRequestPolicy`: `accept` and `decline` are allowed for the receiver only, and `cancel`
    for the sender only.
  - `CouplePolicy`: `end` is allowed only when `$user->couple_id === $couple->id`.
  - Livewire actions call `$this->authorize(...)` before calling the action class.
  - Livewire receives request ids, which are re-loaded server-side. They are never trusted
    models.
- **Rationale**: Principle III requires every mutation to go through policies. It also maps
  directly to FR-010, FR-019 and SC-004.

## R6: Request rate limit

- **Decision**: Use the first-party `RateLimiter` with the key `couple-request:{userId}`, allowing
  10 attempts per 3600 s. Only successful sends count, so a refused send does not use up a
  user's quota. This is the same pattern the partner search already uses.
- **Rationale**: This meets FR-018 and needs no new packages (Principle VI).

## R7: Where the business rules live

- **Decision**: Put the rules in invokable action classes in `app/Actions/Couples/`:
  `SendCoupleRequest`, `AcceptCoupleRequest`, `DeclineCoupleRequest`, `CancelCoupleRequest` and
  `EndCouple`. A rule violation throws `App\Exceptions\CoupleActionException`. Its message is the
  plain-language reason the user sees (FR-020), and Livewire shows it as an error or toast.
- **Rationale**: This follows the starter kit's existing `app/Actions` convention. It keeps the
  transactional and concurrency rules out of Blade files, and lets the rules be tested directly
  with PHPUnit as well as through Livewire.
- **Alternatives considered**: Putting the logic inside the Livewire single-file components: it
  can't be reused by the account-deletion path (R8), and it would make the components large.

## R8: Deleting an account that is in a couple or has requests

- **Decision**:
  - `couple_requests.sender_id` and `receiver_id` use `cascadeOnDelete`, so pending requests
    disappear with the account.
  - The `User` model has a `deleting` hook. If the user has a `couple_id`, the hook calls
    `EndCouple`, so the partner is freed and the couple's data is deleted.
- **Rationale**: Covers the spec's account-deletion edge case on every deletion path (the
  settings modal, tinker, future admin tools) without changing the starter kit's delete flow.
  If `couple_id` were only nulled, the partner would be left in a one-member couple.

## R9: Pages and navigation

- **Decision**:
  - Keep `/partner/search` (`partner.search`) as the page for single users. It adds three
    things:
    1. An **incoming requests** list with accept and reject buttons.
    2. An **outgoing request** status panel with a cancel button.
    3. A **Send request** action on the search result card.
  - Add `/couple` (`couple.show`) for users in a couple. It shows the partner's name and
    username and an **End couple** button, which opens a `flux:modal` confirmation. The modal
    warns that the couple's reviews will be permanently deleted.
  - Each page redirects to the other when it doesn't apply to the user's current state (FR-001).
    This happens in `mount()`.
  - The header and mobile nav item changes between **Find your partner** and **Your partner**
    based on the user's state.
  - The Fortify `home` stays `/partner/search`, which forwards coupled users to `/couple`.
- **Rationale**: This is the smallest change. Existing routes, tests and the post-login redirect
  keep working, and there are no new middleware aliases.
- **Alternatives considered**:
  - One combined `/partner` page that switches on state: it mixes two unrelated UIs in one
    component.
  - A `single`/`coupled` middleware pair: it adds two aliases where a two-line `mount()` check is
    enough.

## R10: What search shows for a user who is in a couple

- **Decision**: The result card still shows the username and display name. In place of the send
  button it shows "Not available", and it never reveals the user's partner (FR-003). If the found
  user already has a pending request to the searcher, the card shows **Accept** and **Reject**.
  If the searcher already has a pending request to them, it shows "Request pending".
- **Rationale**: This keeps the behaviour from feature 002, where users in a couple can still be
  found. It also gives the clear refusal reasons required by FR-020.
