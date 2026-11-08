# Feature Specification: Require Email Verification

**Feature Branch**: `implement-mustverifyemail`

**Created**: 2026-11-08

**Status**: Draft

**Input**: User description: "Let's implement must verify email - i believe it is currently commented out so the middleware does nothing."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - New members verify their email before using the app (Priority: P1)

Someone registers for an account. They are emailed a verification link and asked to check their inbox. Until they follow that link they cannot use the app. Once they follow it, they carry on into the app as normal.

**Why this priority**: This is the core of the feature. Our Little List connects two people through their accounts, so an account should belong to someone who really controls the email address on it. Today the check exists on the app's pages but never stops anyone.

**Independent Test**: Register a new account, confirm that every member page leads to the "verify your email" page, follow the emailed link, and confirm the member pages then open.

**Acceptance Scenarios**:

1. **Given** a person has just registered, **When** registration completes, **Then** a verification email is sent to the address they gave and they are shown a page asking them to verify.
2. **Given** a signed-in person whose email is not verified, **When** they try to open partner search, their couple, reviews, or the appearance or security settings, **Then** they are sent to the "verify your email" page instead.
3. **Given** a person who received a verification link, **When** they follow it while signed in, **Then** their email is marked as verified and they continue into the app, starting with choosing a username if they have not chosen one.
4. **Given** a person who has verified their email, **When** they open any member page, **Then** they are not asked to verify again.
5. **Given** a person who received a verification link but is signed out, **When** they follow it, **Then** they are asked to sign in and their email is verified once they have.

---

### User Story 2 - Getting unstuck while waiting to verify (Priority: P2)

A person waiting to verify may not have received the email, may have let the link expire, or may have mistyped their address when registering. From the "verify your email" page they can ask for a new link, go and correct their email address, or sign out.

**Why this priority**: Requiring verification creates a new way to get stuck. Without these ways out, a single typo at registration would lock someone out of their account for good.

**Independent Test**: Register with an address, use the "verify your email" page to request a new link and confirm it arrives, then correct the address and confirm a link arrives at the new address and verifies the account.

**Acceptance Scenarios**:

1. **Given** a person on the "verify your email" page, **When** they ask for a new link, **Then** a new link is emailed and the page confirms it was sent.
2. **Given** a person on the "verify your email" page who mistyped their address, **When** they choose to change it, **Then** they reach a page where they can correct their address.
3. **Given** a person on the "verify your email" page, **When** they choose to sign out, **Then** they are signed out.
4. **Given** a person who follows an expired or invalid link, **When** the link is rejected, **Then** their email stays unverified and they can ask for a new link.

---

### User Story 3 - Changing an email address requires verifying the new one (Priority: P2)

A verified member changes the email address on their account. The new address is unproven, so they are emailed a link at the new address and must follow it before they can carry on using the app.

**Why this priority**: Without it, verification would only ever prove the address used at registration, and anyone could swap in an address they do not control afterwards.

**Independent Test**: As a verified member, change the email address in profile settings, confirm a link arrives at the new address without asking for one, confirm member pages lead to the "verify your email" page, then follow the link and confirm access returns.

**Acceptance Scenarios**:

1. **Given** a verified member, **When** they change their email address, **Then** their email becomes unverified and a verification link is sent to the new address without them asking for one.
2. **Given** a member who has just changed their address, **When** they try to open a member page, **Then** they are sent to the "verify your email" page until they follow the new link.
3. **Given** a verified member, **When** they save their profile without changing their email address, **Then** their email stays verified and no email is sent.
4. **Given** a member who has changed their address, **When** they follow a link that was issued for their previous address, **Then** the link does not verify the new address.
5. **Given** a member in a couple who has changed their address, **When** they are waiting to verify, **Then** their couple and reviews are unchanged and their partner can carry on using the app.

---

### User Story 4 - Existing accounts are brought in line (Priority: P3)

People who registered before this change and never verified their email are asked to verify the next time they use the app. Their account, couple and reviews are untouched and are all available again once they verify.

**Why this priority**: Only applies once, to accounts that already exist, but the rule should hold for every account.

**Independent Test**: With an existing account that has never verified, sign in, confirm the "verify your email" page appears and that a link can be requested, follow it, and confirm all the account's data is intact.

**Acceptance Scenarios**:

1. **Given** an existing account that has never verified, **When** its owner signs in, **Then** they are asked to verify and can ask for a verification link.
2. **Given** an existing account that has already verified, **When** its owner signs in, **Then** nothing changes for them.
3. **Given** an existing unverified account with a couple and reviews, **When** its owner verifies, **Then** the couple and reviews are all still there.

---

### Edge Cases

- A person follows a verification link while signed in as a different account: the link is refused and neither account is changed.
- A person follows a link for an address that is already verified: they continue into the app, and it is not treated as a new verification.
- A person has a member page open in one tab and changes their email address in another: actions taken on the open page are refused until the new address is verified, rather than only being blocked on the next full page load.
- An unverified person who has not chosen a username tries to change their email: they choose a username first, as they do today, then reach the page where they can change it.
- A verification email fails to send: the person's registration or email change still stands, and they can ask for another link from the "verify your email" page.
- A person repeatedly asks for new links: requests are limited so that a mailbox cannot be flooded.
- An unverified person signs in by any supported method, including a passkey or two-factor authentication: the same rule applies once they are signed in.
- A verified member sends a partner request to someone who has not verified: the request waits, and the recipient can only respond once they have verified.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST treat a regular user's email address as unverified until they have followed a verification link sent to that address.
- **FR-002**: System MUST prevent an unverified user from using partner search, couple management, reviews, and the appearance and security settings, and MUST send them to the "verify your email" page instead.
- **FR-003**: System MUST apply the requirement to every action on those features, including actions on a page that was already open when the address became unverified.
- **FR-004**: System MUST keep the "verify your email" page, sign out, choosing a username, and the profile settings page available to unverified users.
- **FR-005**: System MUST email a verification link when a person registers.
- **FR-006**: System MUST email a verification link to the new address, without being asked, when a user changes their email address, and MUST mark the account as unverified.
- **FR-007**: Users MUST be able to request a new verification link from the "verify your email" page and from the profile settings page, and MUST be told that it has been sent.
- **FR-008**: The "verify your email" page MUST give users a way to reach the page where they can correct their email address, and a way to sign out.
- **FR-009**: System MUST limit how often new verification links can be requested.
- **FR-010**: Verification links MUST expire after 60 minutes and MUST only work for the account and the email address they were issued for.
- **FR-011**: System MUST reject expired, invalid or mismatched links without changing the account, and the user MUST be able to request a new link afterwards.
- **FR-012**: After verifying, users MUST continue into the app through the usual steps, including choosing a username if they have not done so.
- **FR-013**: System MUST leave already-verified users unaffected, and MUST NOT resend a verification email when a profile is saved with the email address unchanged.
- **FR-014**: System MUST NOT change or remove an account's couple, reviews or other data because the account is unverified.
- **FR-015**: Admin accounts MUST NOT be subject to email verification.

### Key Entities *(include if feature involves data)*

- **User account**: A regular member. Already records when its email address was verified; no value means unverified.
- **Verification link**: A time-limited link tied to one account and one email address. Emailed to the person and not stored as a record of its own.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: In a walk-through of every member page and action, an unverified account reaches none of them, on 100% of attempts.
- **SC-002**: A new person can go from submitting the registration form to using the app in under 3 minutes, with the emailed link as the only extra step.
- **SC-003**: Every change of email address results in a verification link arriving at the new address with no further action from the user.
- **SC-004**: A person who mistyped their address at registration can correct it and verify their account without any help from an operator.
- **SC-005**: Verified members see no verification prompts and no change in the pages available to them.

## Assumptions

- The requirement applies to all existing accounts: anyone who has never verified is asked to do so at their next visit, and no account is automatically marked as verified. This keeps the rule the same for everyone and loses no data.
- Email delivery is available wherever the app runs. Development already captures mail locally, but any live environment must have working email before this ships, because otherwise unverified people could not get in.
- This feature replaces the assumption in the initial setup feature that email verification is optional. The automated test that protects that old behaviour will change to match.
- Deleting an account stays available only to verified users, which is the default behaviour of the account pages once verification is required.
- Unverified users can still be found by username in partner search. Keeping them hidden would add complexity for little benefit, as they cannot respond to a request until they verify.
- Completing a password reset does not count as verifying an email address.
- The appearance of the verification email is out of scope, as it is not customised today.
- Requiring a sign-in again, or sending a notice to the old address, when an email address is changed is out of scope.
