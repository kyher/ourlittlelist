# UI Route Contract: Couple Management

All the routes are server-rendered Livewire pages inside the existing
`auth, verified, has-username` group. Every route not listed here behaves as described in
`specs/002-usernames/contracts/ui-routes.md`.

| Path | Name | Purpose | Spec ref |
|------|------|---------|----------|
| `/partner/search` *(changed)* | `partner.search` | Page for single users: incoming requests, outgoing request status, search, and sending requests. A user in a couple gets a `302` to `/couple` | US1, US2, FR-001 to FR-014 |
| `/couple` *(new)* | `couple.show` | Page for users in a couple: shows the partner and an End couple button. A single user gets a `302` to `/partner/search` | US3, FR-015 to FR-017 |

**Navigation**: the header and the mobile sidebar show **Find your partner** (`partner.search`) to
single users and **Your partner** (`couple.show`) to users in a couple. The logo links to
`partner.search`, which forwards users in a couple.

## `pages::partner.search` component (single users)

**State exposed to the view**:
- `incoming`: a list of `{id, name, username}`.
- `outgoing`: either `null` or `{id, name, username, status}`, where `status` is one of
  `pending`, `declined` or `closed`.
- The existing `query`, `result` and `searched` properties.

The view never exposes emails.

| Action | Parameters | Success | Refusal (shown as an error or toast with the message) |
|--------|------------|---------|-----------------------------------------------------|
| `search()` | — | The existing behaviour. The result card also gets a `relation` value (see below) | The existing errors |
| `sendRequest()` | none (uses the current `result`'s username, re-resolved on the server) | The outgoing panel shows "Request sent, waiting for :name" | The rule messages in data-model.md |
| `cancelRequest(int $id)` | request id | The outgoing panel is cleared | 403 if the user isn't the sender. "This request is no longer available." if it isn't pending |
| `dismissOutgoing()` | — | Hides a `declined` or `closed` outgoing notice | — |
| `acceptRequest(int $id)` | request id | Redirects to `/couple` | 403 if the user isn't the receiver. "no longer available" or ":name is already in a couple" |
| `declineRequest(int $id)` | request id | The request is removed from `incoming` | 403 if the user isn't the receiver. "no longer available" |

**Search result `relation`** (decides what the result card shows in place of a plain result):

| `relation` | Card shows |
|------------|-----------|
| `available` | A **Send request** button. It is disabled with a hint when the user has a pending outgoing request to someone else |
| `pending_outgoing` | "Request pending" and a **Cancel** button |
| `pending_incoming` | ":name has sent you a request" with **Accept** and **Reject** |
| `unavailable` | "Not available". The partner's identity is never shown |

## `pages::couple.show` component (users in a couple)

**State**: `partner` is `{name, username}`.

| Action | Success | Refusal |
|--------|---------|---------|
| `endCouple()` | The couple is deleted and the user is redirected to `/partner/search` with a toast saying "Your couple has ended." | 403 if the user isn't a member (`CouplePolicy::end`) |

**Confirmation modal** (a `flux:modal` opened by the **End couple** button, `data-test="end-couple-button"`):
- **Heading**: "End your couple with :name?"
- **Body**: "This will permanently delete all of your couple's reviews, for both of you. This
  cannot be undone."
- **Buttons**: **Cancel**, which closes the modal and changes nothing, and **End couple**, a
  danger button (`data-test="confirm-end-couple-button"`) that calls `endCouple()`.

## Data-test hooks (for feature tests)

`incoming-request`, `accept-request-button`, `decline-request-button`, `outgoing-request`,
`cancel-request-button`, `send-request-button`, `partner-unavailable`, `couple-partner`,
`end-couple-button`, `confirm-end-couple-button`.
