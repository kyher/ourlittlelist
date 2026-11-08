# Research: Require Email Verification

All findings were checked against the installed versions in `vendor/`.

## R1. Turning enforcement on

- **Decision**: Add `Illuminate\Contracts\Auth\MustVerifyEmail` to `User`'s `implements` list.
- **Rationale**: `EnsureEmailIsVerified` only blocks users that are `instanceof MustVerifyEmail`, which is why the `verified` middleware currently does nothing. The base `Authenticatable` user already uses the `MustVerifyEmail` trait, so only the interface is missing. The same check switches on the framework's `Registered` listener (FR-005) and the profile page's unverified banner and resend link.
- **Alternatives considered**: A custom middleware (re-implements the framework, against Principle I).

## R2. Actions on pages that are already open (FR-003)

- **Decision**: Add `EnsureEmailIsVerified::class` to `Livewire::addPersistentMiddleware()` in `FortifyServiceProvider`, next to `RequirePassword`.
- **Rationale**: Livewire re-runs only its persistent list on update requests, and `EnsureEmailIsVerified` is not in the default list. Without this, a tab left open keeps working after the address becomes unverified. It applies only to components on routes that carry `verified`, so the profile page keeps working.
- **Behaviour**: Livewire's update requests do not send a JSON `Accept` header, so the middleware redirects instead of returning 403. The client sees `response.redirected` and does a full-page redirect to the verify page. `Redirect::guest` on a POST stores the referring page as the intended URL, so the person returns to that page after verifying, not to the Livewire endpoint.
- **Testing note**: `Livewire::test()` skips persistent middleware, so the test must POST to the real update endpoint using a snapshot from the rendered page.
- **Alternatives considered**: Per-component checks (easy to forget on new components).

## R3. Sending a link when the address changes (FR-006)

- **Decision**: In the profile component, record whether the email is dirty before saving, then call `sendEmailVerificationNotification()` after a successful save.
- **Rationale**: The component clears `email_verified_at` but sends nothing, and the starter kit leaves re-sending to the user. Sending after the save means a failed save (for example a username collision) sends no email.
- **Alternatives considered**: A model observer (hides the side effect and would also fire from console or admin changes).

## R4. Limiting link sends (FR-009)

- **Decision**: Keep Fortify's `throttle:6,1` on the "verify your email" page resend. In the profile component, share one `RateLimiter` key per user, six per minute, across resend and email change. When the limit is hit on an email change, reject the save with an error on the email field, so an address never becomes unverified without a link being sent. When it is hit on resend, show a "try again shortly" message.
- **Rationale**: Livewire actions do not pass through Fortify's route throttle. Without a limit, alternating between two addresses could flood someone else's mailbox.
- **Alternatives considered**: Counting both surfaces under one limit (needs a custom Fortify limiter for little gain; the combined ceiling is 12 per minute).

## R5. When a verification email cannot be sent

- **Decision**: Override `sendEmailVerificationNotification()` on `User` to catch Symfony's `TransportExceptionInterface` and `report()` it.
- **Rationale**: The notification is synchronous. Fortify fires `Registered` before signing the person in, so a mail failure would otherwise produce a 500 with the account created but not signed in, and retrying would fail with "email taken". One override covers registration, email change and resend.
- **Known limitation**: During a mail outage the page will still say a link was sent. The failure is logged, and the person can ask again.
- **Alternatives considered**: Queueing the notification (`compose.yaml` runs no worker, so mail would never go out, and adding one is not justified under Principle VI).

## R6. Expired and invalid links (FR-010, FR-011)

- **Decision**: Keep Fortify's checks. In `bootstrap/app.php`, render `InvalidSignatureException` on the `verification.verify` route as a redirect to `verification.notice` with a `verification-link-invalid` status.
- **Rationale**: Fortify already ties links to the account id and a hash of the current email, and the signature enforces the 60-minute expiry. Expiry is the most common failure and otherwise ends on a bare 403 page. Links for the wrong account or a previous address stay a 403, since that page is rarely reached and the person is sent to the verify page on their next visit.
- **Alternatives considered**: Replacing `VerifyEmailController` (re-implements Fortify).

## R7. Correcting a mistyped address (FR-004, FR-008)

- **Decision**: Add a link to `profile.edit` on the verify page and show the address the link was sent to. Reword the page so it does not claim an email was just sent, since existing accounts will not have received one.
- **Rationale**: The profile route already omits `verified`, so it is reachable. Showing the address makes a typo visible.

## R8. Existing accounts and access for other account types

- **Decision**: No data migration: existing accounts with no `email_verified_at` must verify at their next visit (spec assumption). Admin routes use neither `verified` nor the `web` user model, so admins are unaffected (FR-015).
- **Passkeys and two-factor**: The check runs on routes after sign-in, so every sign-in method is covered.

## R9. Tests that change

- `EmailVerificationTest::test_unverified_users_can_use_the_app_without_verifying` and `RegistrationTest::test_newly_registered_users_reach_partner_search_without_verifying_their_email` assert the old behaviour and are replaced.
- `UserFactory` defaults to verified, so other tests are unaffected.
