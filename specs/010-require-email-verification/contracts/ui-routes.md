# UI and Route Contract: Require Email Verification

No routes are added. This records how existing routes behave once verification is enforced.

## Access for a signed-in user with an unverified email

| Route | Name | Result |
|-------|------|--------|
| `/partner/search` | `partner.search` | Redirect to `verification.notice` |
| `/couple` | `couple.show` | Redirect to `verification.notice` |
| `/reviews`, `/reviews/new`, `/reviews/{item}` | `reviews.*` | Redirect to `verification.notice` |
| `/settings/appearance` | `appearance.edit` | Redirect to `verification.notice` |
| `/settings/security` | `security.edit` | Redirect to `verification.notice` |
| `/email/verify` | `verification.notice` | Available |
| `/username` | `username.create` | Available |
| `/settings/profile` | `profile.edit` | Available (username required first, as today) |
| `/logout` | `logout` | Available |
| `/admin/*` | `admin.*` | Unaffected (separate admin accounts) |

Livewire actions from a page on a redirected route get the same redirect, as a full page navigation.

## Verification link: `GET /email/verify/{id}/{hash}` (`verification.verify`)

| Situation | Result |
|-----------|--------|
| Signed out | Redirect to log in, then the link completes |
| Valid link | Email verified, `Verified` event fired, redirect to the intended page or `/partner/search?verified=1` |
| Already verified | Same redirect, no `Verified` event |
| Expired or tampered signature | Redirect to `verification.notice` with status `verification-link-invalid`; account unchanged |
| Different account, or issued for a previous address | 403; account unchanged |

## "Verify your email" page (`verification.notice`)

- Shows the address that needs verifying.
- Does not claim that an email was just sent.
- Resend button posts to `verification.send` (limited to six per minute); shows the existing confirmation for status `verification-link-sent`.
- Shows a message for status `verification-link-invalid`, telling the person to request a new link.
- Link to `profile.edit`, labelled as changing the email address.
- Log out button.

## Profile page (`profile.edit`)

| Action | Result |
|--------|--------|
| Save with a changed email | Email saved, account unverified, link sent to the new address |
| Save with a changed email, over the limit | Validation error on `email`; nothing saved, nothing sent |
| Save with the same email | State unchanged, no email |
| Resend link | Link sent and confirmation shown |
| Resend link, over the limit | "Try again shortly" message, nothing sent |

Limit: six sends per user per minute, shared by both actions.
