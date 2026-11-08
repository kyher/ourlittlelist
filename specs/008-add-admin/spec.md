# Feature Specification: Admin Users and System Totals

**Feature Branch**: `add-admin`

**Created**: 2026-11-08

**Status**: Draft

**Input**: User description: "Add a command to add an admin user. The command will take an email and then interactively allow for a password to be set. Admins can log in (completely separate to normal user) and they can view the amount of users, couples and reviews in the system."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Create an admin from the command line (Priority: P1)

Someone with access to the server runs a command, giving an email address. The command then asks them to choose a password, without showing it on screen, and creates an admin account. This is the only way an admin account can come into existence.

**Why this priority**: Nothing else in this feature can happen until an admin account exists, and restricting creation to server access is what keeps the admin area trustworthy.

**Independent Test**: Run the command with a new email and a valid password, then confirm that exactly one admin account exists for that email and that no regular user account was created.

**Acceptance Scenarios**:

1. **Given** no admin exists with the email, **When** the operator runs the command with that email and enters a valid password twice, **Then** an admin account is created and the command confirms success.
2. **Given** the command is prompting for a password, **When** the operator types it, **Then** the characters are not displayed on screen.
3. **Given** the command is prompting for a password, **When** the two entries do not match or the password does not meet the strength rules, **Then** the operator is told why and asked again, and no account exists until a valid password is confirmed.
4. **Given** an admin already exists with the email, **When** the operator runs the command with that email, **Then** the command reports the conflict, exits with a failure status, and leaves the existing admin unchanged.
5. **Given** a malformed email, **When** the operator runs the command, **Then** the command reports that the email is invalid, exits with a failure status, and creates nothing.
6. **Given** a regular user already has the email, **When** the operator runs the command with that email, **Then** the admin account is created and the regular user account is unaffected.

---

### User Story 2 - Log in to a separate admin area (Priority: P2)

An admin goes to a dedicated admin login page and signs in with their admin email and password. They can later log out. Admin sign-in is entirely separate from regular sign-in: neither kind of account can be used in the other's login, and being signed in as one grants nothing in the other's area.

**Why this priority**: The admin area has to be reachable and securely separated from regular users before any information can be shown in it.

**Independent Test**: Create an admin, log in through the admin login page, and confirm access to the admin area. Then confirm that regular user credentials are rejected there, and that admin credentials are rejected at the regular login.

**Acceptance Scenarios**:

1. **Given** an admin account, **When** the admin submits correct credentials on the admin login page, **Then** they are signed in and taken to the admin area.
2. **Given** an admin login attempt with a wrong password or unknown email, **When** it is submitted, **Then** it is rejected with one generic message that does not reveal whether the email exists.
3. **Given** a regular user's credentials, **When** they are submitted on the admin login page, **Then** they are rejected in the same way as any other invalid credentials.
4. **Given** an admin's credentials, **When** they are submitted on the regular login page, **Then** they are rejected in the same way as any other invalid credentials.
5. **Given** a signed-in regular user, **When** they visit any admin page, **Then** they are denied access and sent to the admin login page.
6. **Given** a visitor who is not signed in as an admin, **When** they visit any admin page, **Then** they are sent to the admin login page.
7. **Given** a signed-in admin, **When** they visit a page that requires a regular user account, **Then** they are treated as not signed in on that page.
8. **Given** a signed-in admin, **When** they log out, **Then** they can no longer reach admin pages without logging in again.

---

### User Story 3 - View system totals (Priority: P3)

A signed-in admin sees the total number of users, couples and reviews in the system, so they can understand at a glance how much the app is being used.

**Why this priority**: This is the feature's visible value, but it relies on the two stories above.

**Independent Test**: With known numbers of users, couples and reviews in the system, sign in as an admin and confirm that the three totals match.

**Acceptance Scenarios**:

1. **Given** known numbers of users, couples and reviews, **When** an admin views the admin area, **Then** each total is shown and correct.
2. **Given** a category has no records, **When** an admin views the admin area, **Then** that total is shown as 0.
3. **Given** totals have changed since the admin last viewed the page, **When** the admin reloads it, **Then** the current totals are shown.
4. **Given** any amount of data, **When** an admin views the admin area, **Then** no names, email addresses, usernames, review content or per-couple information is shown.

---

### Edge Cases

- The email is given with different letter casing from an existing admin: it is treated as the same email and rejected as a duplicate.
- The command is run where it cannot prompt for input: it fails with a clear message and creates nothing.
- The operator cancels the command while being prompted: no admin account is created.
- Repeated failed admin login attempts: further attempts are temporarily blocked, in line with the regular login.
- A browser is signed in as both a regular user and an admin: each session stays independent, and logging out of one does not end the other.
- An admin account is removed while its admin is signed in: their next request to an admin page sends them to the admin login page.

## Requirements *(mandatory)*

### Functional Requirements

**Creating admins**

- **FR-001**: System MUST provide a command-line command that takes an email address and creates an admin account for it.
- **FR-002**: The command MUST prompt for the password interactively, with hidden input and a confirmation entry, and MUST NOT accept the password as a command argument.
- **FR-003**: The command MUST apply the same password strength rules as regular user accounts in that environment.
- **FR-004**: The command MUST re-prompt when the password is invalid or the confirmation does not match, stating the reason.
- **FR-005**: The command MUST reject malformed emails and emails already used by an admin (ignoring letter casing), exit with a failure status, and leave existing data unchanged.
- **FR-006**: System MUST NOT offer any way to create an admin account other than the command, including through registration or any web page.
- **FR-007**: System MUST store admin passwords only in a non-reversible form.

**Admin authentication**

- **FR-008**: System MUST provide an admin login page, separate from the regular login page, that signs in with email and password.
- **FR-009**: Admin accounts MUST be stored separately from regular user accounts, and the existence of one kind of account MUST NOT affect the other, including when both use the same email.
- **FR-010**: Regular user credentials MUST NOT authenticate at the admin login, and admin credentials MUST NOT authenticate at the regular login.
- **FR-011**: Being signed in as an admin MUST NOT grant access to regular user pages or couple data, and being signed in as a regular user MUST NOT grant access to admin pages.
- **FR-012**: Failed admin logins MUST show one generic message that does not reveal whether the email exists.
- **FR-013**: System MUST temporarily block repeated failed admin login attempts, at the same limit as the regular login.
- **FR-014**: Admins MUST be able to log out.
- **FR-015**: System MUST redirect anyone not signed in as an admin to the admin login page when they request an admin page.

**System totals**

- **FR-016**: System MUST show signed-in admins the current total number of users, couples and reviews.
- **FR-017**: The total of users MUST count regular user accounts only and exclude admin accounts.
- **FR-018**: The total of reviews MUST count each individual review, so that two partners reviewing the same item count as two reviews.
- **FR-019**: The admin area MUST show aggregate totals only, and MUST NOT expose any individual user's or couple's data.

### Key Entities

- **Admin**: A person who operates the system. Has an email address (unique among admins) and a password. Has no relationship to users, couples or reviews.
- **System totals**: Figures calculated from existing users, couples and reviews when viewed. Not stored as a record of their own.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An operator with server access can create an admin account and sign in with it in under 2 minutes.
- **SC-002**: 100% of attempts to use regular credentials at the admin login, or admin credentials at the regular login, are rejected.
- **SC-003**: 100% of admin pages are inaccessible to visitors and regular users who are not signed in as an admin.
- **SC-004**: The three totals exactly match the actual number of users, couples and reviews each time the page is loaded.
- **SC-005**: An admin can see all three totals within 5 seconds of signing in, with no further navigation.
- **SC-006**: No personal information or review content appears anywhere in the admin area.

## Assumptions

- Removing admins, changing an admin's password and resetting a forgotten admin password are out of scope. An admin who needs a change has it made directly by someone with server access.
- Admin login uses email and password only. Two-factor authentication and passkeys are out of scope, even though regular users have them.
- Admin accounts need no username, email verification or couple membership.
- Admins have read-only access to the three totals, with no management of users, couples or reviews.
- Totals cover what currently exists. Disconnected couples are not counted, because couples are removed on disconnect, and no historical figures or trends are shown.
- Users are counted regardless of whether they have verified their email, chosen a username or joined a couple.
- Whoever can run the command is already trusted with server access, so the command requires no additional authentication.
