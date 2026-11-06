# Feature Specification: Initial UI Changes (Remove Template Boilerplate & Horizontal Navigation)

**Feature Branch**: `initial-ui-changes`

**Created**: 2026-11-06

**Status**: Draft

**Input**: User description: "Remove the boilerplate from the laravel template. Change the nav to a horizontal nav when authenticated. We can remove the dashboard for now."

## Clarifications

### Session 2026-11-06

- Q: Is removing the starter-kit "Repository" and "Documentation" links in scope? → A: Yes — they are removed from all pages and not carried over into the new top navigation bar (FR-008, User Story 3 scenario 3).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Signed-in users navigate via a horizontal top bar (Priority: P1)

A signed-in user sees a single horizontal navigation bar across the top of every authenticated page, instead of a vertical sidebar. The bar shows the Our Little List logo/name, the app's main destinations (currently "Find your partner"), and a user menu giving access to Settings and Log out. On small screens the destinations collapse into a menu that can be opened and closed.

**Why this priority**: Navigation is on every authenticated page; it is the most visible change and the frame every future feature (lists, reviews) will sit inside.

**Independent Test**: Sign in, visit any authenticated page at desktop and phone widths, and confirm that a top bar (not a sidebar) is shown, every item works, and the current page is highlighted.

**Acceptance Scenarios**:

1. **Given** a signed-in user with a username, **When** they open any authenticated page on a desktop-width screen, **Then** a horizontal bar is shown at the top containing the app logo, "Find your partner", and a user menu, and no vertical sidebar is shown.
2. **Given** a signed-in user on the "Find your partner" page, **When** they look at the navigation bar, **Then** the "Find your partner" item is visibly marked as the current page.
3. **Given** a signed-in user, **When** they open the user menu, **Then** they see their name and email, a "Settings" link that opens their profile settings, and a "Log out" action that signs them out.
4. **Given** a signed-in user on a phone-width screen, **When** they view an authenticated page, **Then** the navigation destinations are reachable through a toggleable menu and the page content is not obscured or horizontally scrollable.
5. **Given** a signed-in user, **When** they click the app logo in the navigation bar, **Then** they are taken to their signed-in home destination (the "Find your partner" page).

---

### User Story 2 - Dashboard is removed; users land somewhere useful (Priority: P1)

The placeholder dashboard (a greeting and empty placeholder panels) no longer exists. Everywhere the app previously sent a user to the dashboard — after logging in, registering, choosing a username, verifying their email, signing in with a passkey, or updating their profile — it instead sends them to the "Find your partner" page, which is the only meaningful signed-in page today.

**Why this priority**: Removing the dashboard without redirecting these flows would leave users at a broken page; this must ship together with the nav change.

**Independent Test**: Register, log in, choose a username, and verify an email; in each case confirm the user ends up on the "Find your partner" page and that the old dashboard address no longer shows a dashboard.

**Acceptance Scenarios**:

1. **Given** a registered user with a username, **When** they log in, **Then** they are taken to the "Find your partner" page.
2. **Given** a newly registered user, **When** they choose a username, **Then** they are taken to the "Find your partner" page.
3. **Given** a user who clicks an email verification link, **When** verification succeeds, **Then** they are taken to the "Find your partner" page.
4. **Given** any user, **When** they visit the old dashboard address, **Then** they do not see a dashboard page (they receive a "not found" response).
5. **Given** a signed-in user, **When** they look at the navigation, **Then** there is no "Dashboard" item.

---

### User Story 3 - Starter-template boilerplate is gone (Priority: P2)

Visitors and users no longer see leftovers from the framework's starter template. The public home page is replaced with a simple Our Little List landing page (app name, a one-line description of what the app is for, and Log in / Register links — or a link into the app if already signed in) instead of the framework's marketing page. Links to the framework's GitHub repository and documentation are removed from the navigation. Unused template-only pieces (placeholder graphics, the unused navigation layout variant, unused authentication layout variants and the random inspirational-quote panel, unused icons) are removed.

**Why this priority**: Important for the app to feel like its own product, but nothing breaks functionally if it lands after the nav change.

**Independent Test**: Visit the public home page signed out and signed in, and browse every authenticated page; confirm no framework branding, framework marketing copy, or links to the framework's repository/docs appear anywhere.

**Acceptance Scenarios**:

1. **Given** a signed-out visitor, **When** they open the home page, **Then** they see the Our Little List name, a short description of the app, and links to Log in and Register, and no framework marketing content.
2. **Given** a signed-in user, **When** they open the home page, **Then** they see a link that takes them into the app instead of Log in / Register.
3. **Given** a signed-in user, **When** they view any authenticated page, **Then** there are no "Repository" or "Documentation" links pointing at the framework's starter kit.
4. **Given** the existing log in, register, password reset, email verification, two-factor and settings pages, **When** a user uses them, **Then** they continue to work exactly as before.

---

### Edge Cases

- A signed-in user without a username is still sent to the choose-username page before reaching any page with the main navigation (existing behaviour is preserved).
- A user who was mid-flow with an "intended" destination (e.g. they tried to open Settings before logging in) is still returned to that destination after logging in, rather than always to "Find your partner".
- Someone with a bookmarked old dashboard address receives a standard "not found" page rather than an error.
- "dashboard" remains a reserved username, so a future dashboard can be reintroduced without conflicting with a user's handle.
- Long user names in the user menu are truncated rather than breaking the navigation bar layout.
- The settings pages (profile, security, appearance, two-factor) still render correctly inside the new top-bar layout, including their own section sub-navigation.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: All authenticated pages MUST use a horizontal top navigation bar; the vertical sidebar MUST no longer be shown.
- **FR-002**: The navigation bar MUST contain the app logo/name, the "Find your partner" destination, and a user menu with the user's name and email, a "Settings" link, and a "Log out" action.
- **FR-003**: The navigation bar MUST visually indicate which destination is the current page.
- **FR-004**: On narrow (phone-width) screens, the navigation destinations MUST be reachable through a toggleable menu, and the user menu MUST remain accessible.
- **FR-005**: The dashboard page MUST be removed, and its address MUST no longer serve a page.
- **FR-006**: Every flow that previously redirected users to the dashboard by default (log in, registration, choosing a username, email verification, passkey sign-in, profile update, already-has-username redirect) MUST instead redirect to the "Find your partner" page, while still honouring any previously intended destination.
- **FR-007**: The public home page MUST be replaced with a simple Our Little List landing page showing the app name, a short description, and Log in / Register links for signed-out visitors, or a link into the app for signed-in users.
- **FR-008**: Links to the framework's starter-kit repository and documentation MUST be removed from all pages.
- **FR-009**: Template-only assets that are no longer used after these changes (placeholder pattern graphic, unused navigation and authentication layout variants including the random-quote panel, and icons used only by removed items) MUST be removed.
- **FR-010**: Existing authentication, username, partner search and settings functionality MUST continue to work unchanged apart from the destination changes in FR-006.
- **FR-011**: Automated tests MUST be updated to reflect the removed dashboard and new redirect destination, and MUST cover the navigation bar contents and the new landing page.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of authenticated pages display the horizontal navigation bar and 0 display a sidebar.
- **SC-002**: 0 occurrences of framework starter-kit branding, marketing copy, or repository/documentation links are visible on any page of the app.
- **SC-003**: After logging in, registering, choosing a username, or verifying their email, users reach a working page in a single redirect with no "not found" or error pages along the way.
- **SC-004**: A signed-in user can reach every navigation destination (Find your partner, Settings, Log out) in at most 2 clicks from any authenticated page, at both desktop and phone widths.
- **SC-005**: The full automated test suite passes after the change.

## Assumptions

- "Find your partner" is the default signed-in destination for now, since it is the only meaningful authenticated page; this will be revisited when lists/reviews are added.
- The public home page is kept (not removed) because it is the app's entry point for signed-out visitors, but its content is replaced with minimal Our Little List copy; a richer marketing page is out of scope.
- "Boilerplate" means framework starter-kit content that is not part of the app's own product: the framework welcome page, starter-kit repository/docs links, placeholder graphics, and unused layout variants/icons. The starter kit's authentication, two-factor, passkey and settings flows are product functionality and are kept (per the constitution's starter-kit principle).
- The settings area keeps its existing section sub-navigation (Profile, Security, Appearance); only the app-level navigation changes.
- No visual redesign (colours, typography, theming) is in scope beyond replacing the navigation layout and the home page content.
- The old dashboard address is simply removed rather than redirected, because the app has no external users with bookmarks yet.
