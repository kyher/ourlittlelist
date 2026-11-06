# Feature Specification: Application Foundation & Containerised Environment

**Feature Branch**: `001-laravel-docker-setup`

**Created**: 2026-11-06

**Status**: Draft

**Input**: User description: "Set up Laravel app with Livewire starter kit in Docker"

> Note: This is a foundation feature. The constitution fixes the technology choices
> (Laravel, Livewire starter kit, Docker). This spec describes the outcomes the foundation
> must deliver for developers and end users, not how to build it.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Developer starts the full app from a fresh clone (Priority: P1)

A developer clones the repository onto a machine with only Docker installed. They follow
the README, run the documented start command, and open the app in a browser. No other
language runtimes or tools are needed on the machine.

**Why this priority**: Nothing else in the project can be built or tested until a
reproducible environment exists. This is the minimum viable foundation.

**Independent Test**: On a machine with only Docker, clone the repo, follow the README,
and load the app's home page in a browser.

**Acceptance Scenarios**:

1. **Given** a fresh clone and a machine with only Docker installed, **When** the developer
   runs the documented setup and start steps, **Then** the application home page loads
   in a browser at the documented local address.
2. **Given** the environment is running, **When** the developer stops it and starts it again,
   **Then** previously stored data (e.g. registered accounts) is still present.
3. **Given** the environment is running, **When** the developer edits application code or
   page templates, **Then** the change is visible after a browser refresh, with no rebuild.

---

### User Story 2 - Visitor creates an account and signs in (Priority: P2)

A visitor opens the app, registers with name, email and password, and lands on a
signed-in dashboard. They can sign out, sign back in, reset a forgotten password, and
update their profile (name, email, password) or delete their account.

**Why this priority**: Every later feature (connecting as a couple, writing reviews)
needs an identified user. The starter kit provides these flows, and this story confirms
they work in the containerised environment.

**Independent Test**: In the running environment, register a new account, sign out,
sign in, request a password reset and complete it using the captured email, and edit
the profile.

**Acceptance Scenarios**:

1. **Given** a visitor who is not signed in, **When** they submit valid registration
   details, **Then** an account is created and they see the signed-in dashboard.
2. **Given** a registered user, **When** they sign in with the correct credentials,
   **Then** they reach the dashboard. With wrong credentials they see an error and are
   not signed in.
3. **Given** a user who forgot their password, **When** they request a reset, **Then** a
   reset email is captured by the local mail viewer and the link lets them set a new password.
4. **Given** a visitor who is not signed in, **When** they try to open the dashboard,
   **Then** they are sent to the sign-in page.
5. **Given** a signed-in user, **When** they update their name or password on the profile
   page, **Then** the change is saved and confirmed.

---

### User Story 3 - Developer runs quality checks in the containers (Priority: P3)

A developer runs the automated test suite and the code-style check with documented
commands that execute inside the containers. Both pass on a fresh clone.

**Why this priority**: The constitution requires tests and style checks to pass before
merge. Having them work from day one keeps every later feature honest.

**Independent Test**: On a fresh clone with the environment running, run the documented
test and style commands. Both complete successfully.

**Acceptance Scenarios**:

1. **Given** a running environment, **When** the developer runs the documented test
   command, **Then** the full suite runs inside the containers and passes. The suite
   covers registration, sign-in, password reset and profile flows.
2. **Given** a running environment, **When** the developer runs the documented
   code-style check, **Then** it reports no violations on the untouched foundation.
3. **Given** a fresh database, **When** the developer runs the documented
   reset-and-migrate command, **Then** the schema builds cleanly from scratch.

---

### Edge Cases

- The default web or database port is already in use on the host: ports MUST be
  configurable through environment settings, and the README MUST explain how.
- A developer starts the app before creating their local environment settings file:
  the setup steps MUST create it from the committed example, and the README MUST cover this.
- Someone registers with an email that is already in use: registration is rejected
  with a clear message.
- The database container is not ready when the app starts: the app MUST recover
  without manual intervention, or the documented start steps MUST wait for it.
- The front-end assets have not been built yet: the documented steps MUST include building
  them, or running a development asset server, so pages render with styling.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The repository MUST contain everything needed to run the application, its
  database, and supporting services as containers, using only Docker on the host.
- **FR-002**: The repository MUST include a README with setup, start, stop, test,
  code-style, and database reset commands. All of them run through the containers.
- **FR-003**: The application MUST be bootstrapped from the project's chosen starter kit
  (per the constitution). Its registration, sign-in, sign-out, password reset, and profile
  management flows MUST be working.
- **FR-004**: Signed-in users MUST land on a dashboard page. For now it MAY be a
  placeholder that welcomes the user by name.
- **FR-005**: Pages that require sign-in MUST redirect visitors who are not signed in to
  the sign-in page.
- **FR-006**: Database data MUST persist across container restarts until the developer
  deliberately resets it.
- **FR-007**: Outgoing email in local development MUST be captured by a local mail viewer
  that developers can open in a browser. No real emails are sent.
- **FR-008**: The application name shown in the UI and page titles MUST be "Our Little List".
- **FR-009**: A committed example environment file MUST list every required setting.
  Real secrets MUST NOT be committed.
- **FR-010**: The automated test suite and code-style check MUST run inside the containers
  and pass on the untouched foundation.
- **FR-011**: Host ports for the app, the database and the mail viewer MUST be configurable
  without editing committed files.

### Key Entities

- **User**: A person with an account. Has a name, email (unique), password, and
  optional email-verification timestamp. This is the base for later couple and review features.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A developer with only Docker installed can go from a fresh clone to a
  working home page in under 15 minutes by following the README alone, excluding image
  download time.
- **SC-002**: A new visitor can register and reach the dashboard in under 1 minute.
- **SC-003**: 100% of the account flows (register, sign in, sign out, password reset,
  profile update, account deletion) are covered by automated tests that pass.
- **SC-004**: The full automated test suite completes in under 2 minutes on a typical
  developer laptop.
- **SC-005**: Stopping and restarting the environment keeps 100% of previously
  stored accounts.

## Assumptions

- Sign-in uses email and password, as provided by the starter kit. Social login is out
  of scope.
- Email verification is available but not required to use the app in this feature.
  Whether to enforce it can be decided in the couple-connection feature.
- Registration is open to anyone who can reach the app. Invite-only access is out of scope.
- Production hosting and deployment are out of scope. This feature covers local
  development and test environments only.
- Couple connection, review categories, and reviews are separate later features.
  The dashboard is a placeholder until then.
- The developer machine runs Linux, macOS or Windows (with WSL2) and has a current
  version of Docker with Compose support.
