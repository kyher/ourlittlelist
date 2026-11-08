# Feature Specification: Usernames & Partner Search

**Feature Branch**: `002-usernames`

**Created**: 2026-11-06

**Status**: Draft

**Input**: User description: "Let's add usernames to the system so that couples can search for one another (Kyle can search for Harry)"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Choose a username at registration (Priority: P1)

A new person signing up picks a unique username alongside their name, email, and password. The username is how their partner will find them later.

**Why this priority**: Nobody can be searched for until usernames exist. Every other story depends on this.

**Independent Test**: Register a new account with a username and confirm the account is created and the username is shown on the profile. Try registering a second account with the same username and confirm it is rejected.

**Acceptance Scenarios**:

1. **Given** a visitor on the registration page, **When** they submit a valid, unused username with the other required details, **Then** their account is created with that username.
2. **Given** an existing user with username `harry`, **When** a visitor tries to register as `Harry`, **Then** registration is rejected with a message saying the username is taken (usernames are case-insensitive).
3. **Given** a visitor on the registration page, **When** they submit a username that breaks the format rules (too short, too long, or containing disallowed characters), **Then** registration is rejected with a message explaining the rules.

---

### User Story 2 - Search for a partner by username (Priority: P1)

A signed-in user (Kyle) types his partner's username (`harry`) into a search and sees Harry's account in the results, so he can tell he has found the right person.

**Why this priority**: This is the outcome the feature is for. It is how couples will find each other before connecting.

**Independent Test**: With two accounts, `kyle` and `harry`, sign in as Kyle, search for `harry`, and confirm Harry's account appears with his username and display name.

**Acceptance Scenarios**:

1. **Given** Kyle is signed in and Harry has the username `harry`, **When** Kyle searches for `harry`, **Then** Harry appears in the results showing his username and display name.
2. **Given** Kyle is signed in, **When** he searches for `HARRY`, **Then** Harry is still found (the search ignores case).
3. **Given** Kyle is signed in, **When** he searches for a username that does not exist, **Then** he sees a clear "no user found" message.
4. **Given** Kyle is signed in, **When** he searches for his own username, **Then** his own account is not offered as a result.
5. **Given** a visitor who is not signed in, **When** they try to reach the search, **Then** they are sent to the sign-in page.

---

### User Story 3 - Existing users set and change their username (Priority: P2)

Accounts created before this feature have no username. These users are asked to choose one before they carry on using the app. Any user can change their username later from their profile settings.

**Why this priority**: It covers accounts that already exist and lets people fix typos. It is secondary because there are very few existing accounts.

**Independent Test**: Sign in as an account without a username, confirm you are asked to choose one, set it, and confirm it can then be changed in profile settings.

**Acceptance Scenarios**:

1. **Given** a signed-in user with no username, **When** they open any page of the app, **Then** they are sent to a page asking them to choose a username first.
2. **Given** a user with username `kyle`, **When** they change it to `kyle_h` in profile settings, **Then** searches for `kyle_h` find them and searches for `kyle` no longer do.
3. **Given** a user editing their username, **When** they choose a username that another user already has, **Then** the change is rejected and their current username stays the same.

---

### Edge Cases

- Two people try to claim the same username at the same moment: only one succeeds, and the other sees a "username taken" message.
- Leading or trailing spaces in a username or search term are ignored.
- Reserved words that could be confused with the system or with app pages (e.g. `admin`, `support`, `settings`) cannot be chosen.
- A search term that is empty or only whitespace does not run a search.
- Searching never reveals a user's email address, reviews, or couple data.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every user account MUST have exactly one username, and no two accounts may share it.
- **FR-002**: Usernames MUST be 3–30 characters long and contain only letters, numbers, underscores, and full stops. They MUST start with a letter or number.
- **FR-003**: Username uniqueness and matching MUST ignore case. The system MUST store and show the username in lowercase.
- **FR-004**: The registration flow MUST require a username and MUST validate it against FR-001 to FR-003 and the reserved-word list.
- **FR-005**: Users MUST be able to change their username from their profile settings, under the same rules as registration.
- **FR-006**: A signed-in user without a username MUST be required to choose one before using any other part of the app (apart from signing out).
- **FR-007**: Only signed-in users MUST be able to search for other users by username.
- **FR-008**: Search MUST return only accounts whose username exactly matches the search term, ignoring case and surrounding whitespace. Searching for part of a username MUST NOT return any results.
- **FR-009**: Search results MUST show only the matched user's username and display name. They MUST NOT show email addresses or any couple or review data.
- **FR-010**: The searching user's own account MUST NOT appear in their search results.
- **FR-011**: When there is no match, the system MUST say so clearly.
- **FR-012**: The system MUST limit how often one user can search (e.g. no more than 30 searches per minute) to prevent bulk enumeration of usernames.
- **FR-013**: This feature MUST NOT create, request, or change any couple connection. Finding a user is only the step before the connect/invite flow, which is a separate feature.

### Key Entities

- **User**: An existing account. It gains a **username**: a unique, case-insensitive, public handle that the partner uses to find this account. The display name and email stay as they are, and email stays private.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A user who knows their partner's username can find their partner's account in under 30 seconds from signing in.
- **SC-002**: 100% of searches using the exact username, in any letter case, return the intended user.
- **SC-003**: 0 searches show an email address or any couple or review data.
- **SC-004**: 100% of active accounts have a username after their first sign-in following release.
- **SC-005**: Adding a username field adds less than 15 seconds to registration for a new user.

## Assumptions

- Only exact-username search is offered, not partial or name-based search. Partners share usernames with each other outside the app, as with a friend code, which keeps people from browsing the user list. This follows the privacy-first principle (Constitution III).
- Sending, accepting, and declining a partner connection request is a separate future feature. This feature stops at "found the user".
- Users who are already in a couple can still be found by search. Whether they can be invited is for the connect feature to decide.
- The display name ("name") stays as it is and is not unique. The username is an additional, separate field.
- The list of reserved usernames is short, is kept in the app, and can grow over time.
- Old usernames are not kept or redirected after a change. They become available for anyone to claim again.
