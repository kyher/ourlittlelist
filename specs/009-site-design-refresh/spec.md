# Feature Specification: Site Colour Scheme & Welcoming Home Page

**Feature Branch**: `ui-changes`

**Created**: 2026-11-08

**Status**: Draft

**Input**: User description: "We should change the site's design. We should have an appropriate colour scheme which we apply through the site and add a more welcoming home page to encourage sign ups."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A signed-out visitor is encouraged to sign up (Priority: P1)

Someone who has just heard about Our Little List, perhaps from their partner, opens the home page. Straight away they understand what it is (a private place for two people to review the things they experience together), feel it is warm and made for couples, and see an obvious way to create an account. If they keep reading, the page explains how it works, what they can review, and that only the two of them can see what they write. A second invitation to sign up is waiting when they reach the end.

**Why this priority**: Encouraging sign-ups is the stated goal. The app needs two people to be useful, so the home page often has to persuade a partner who was sent a link. Today's page is only a logo, one sentence and two buttons.

**Independent Test**: Open the home page signed out at phone and desktop widths. Confirm the purpose and the sign-up action are visible without scrolling, the supporting sections are present, and following the sign-up action leads to the registration page.

**Acceptance Scenarios**:

1. **Given** a signed-out visitor, **When** they open the home page, **Then** they see the app name and logo, a welcoming headline, a sentence explaining what the app is for, and a prominent sign-up action, all without scrolling on phone and desktop screens.
2. **Given** a signed-out visitor, **When** they follow any sign-up action on the home page, **Then** they arrive at the registration page.
3. **Given** a signed-out visitor who already has an account, **When** they look for a way in, **Then** a log in link is available at the top of the page, visually secondary to the sign-up action.
4. **Given** a signed-out visitor, **When** they scroll the page, **Then** they see how it works (create an account, connect with your partner, review things together), the kinds of things they can review (films, series, books, food and restaurants), and how both partners' reviews of the same thing sit side by side.
5. **Given** a signed-out visitor, **When** they read the page, **Then** they are told plainly that their reviews are visible only to the two of them.
6. **Given** a signed-out visitor who scrolls to the end of the page, **When** they finish reading, **Then** another sign-up action is available without scrolling back to the top.

---

### User Story 2 - The whole site shares one recognisable colour scheme (Priority: P1)

Wherever someone goes on the site, the same warm colour scheme is used for backgrounds, surfaces, buttons, links, highlighted navigation items and the logo. The site stops looking like an unstyled grey template and looks like one product, from the home page through to registration, the signed-in pages and settings. The scheme works in both light and dark appearance.

**Why this priority**: This is the other half of the request, and it can ship without the new home page. Every page benefits, and the home page depends on these colours to look right.

**Independent Test**: Browse every page in light and dark appearance and confirm the scheme is applied consistently, all content stays readable, and no page keeps the old neutral look.

**Acceptance Scenarios**:

1. **Given** a user on any page, **When** they look at primary buttons, links, focus outlines and the current navigation item, **Then** those elements use the scheme's accent colour.
2. **Given** a user who has chosen light or dark appearance, **When** they visit any page, **Then** the page uses the matching version of the scheme and stays readable.
3. **Given** a user who has chosen system appearance, **When** their device switches between light and dark, **Then** the site follows it, including on the home page and the log in and registration pages.
4. **Given** any visitor, **When** they open the site, **Then** the browser-tab icon and home-screen image use the scheme's colours.
5. **Given** a user performing a destructive action or seeing an error, success message or star rating, **When** it is displayed, **Then** its meaning is still clear and it cannot be mistaken for the scheme's accent colour.

---

### User Story 3 - A signed-in visitor can get back into the app from the home page (Priority: P2)

Someone who is already signed in and lands on the home page, for example from a bookmark, sees the same welcoming page but is offered a clear way into the app. They are not asked to register or log in.

**Why this priority**: This preserves existing behaviour on the redesigned page. It affects few visits but would be confusing if it were missed.

**Independent Test**: Sign in, open the home page, confirm no sign-up or log in actions appear anywhere on it, and follow the link into the app.

**Acceptance Scenarios**:

1. **Given** a signed-in user, **When** they open the home page, **Then** every place that would show a sign-up or log in action instead shows a single action that takes them into the app.
2. **Given** a signed-in user, **When** they follow that action, **Then** they reach their usual signed-in destination.

---

### Edge Cases

- If registration is turned off for the site, the home page shows no sign-up actions and does not lead to a broken link. Visitors are offered log in instead.
- A very narrow phone screen (320 pixels wide) shows the whole page without horizontal scrolling, and the sign-up action is not cut off.
- A visitor signed in only as an admin is treated as signed out on the home page, because admin accounts are separate from user accounts.
- A visitor whose browser or device requests reduced motion sees no animation on the home page.
- If images or illustrations fail to load, the page still explains the app and the sign-up action still works.
- A visitor who has never chosen an appearance gets one matching their device setting, with no visible flash of the wrong appearance on load.
- Long usernames, long review titles and other user-entered text stay readable on the new backgrounds and do not break the layout.
- Browsers that have cached the old browser-tab icon may show it until the cache refreshes. This is acceptable.

## Requirements *(mandatory)*

### Functional Requirements

**Colour scheme**

- **FR-001**: The site MUST have one defined colour scheme with a light and a dark version, each covering backgrounds, surfaces, text, borders and an accent colour.
- **FR-002**: The colour scheme MUST be warm and affectionate in character, to suit a private space for couples, and MUST be defined in one place so that changing it later does not mean editing each page.
- **FR-003**: The colour scheme MUST be applied to every page: the public home page, all account pages (log in, registration, password reset, email verification, password confirmation, two-factor challenge, choosing a username), partner search, couple management, reviews (list, new and detail), all settings pages, and the admin log in and totals pages.
- **FR-004**: Primary buttons, links, focus outlines, selected states and the current navigation item MUST use the scheme's accent colour.
- **FR-005**: All text and interactive elements MUST meet WCAG 2.1 AA contrast in both light and dark appearance: at least 4.5:1 for normal text, and at least 3:1 for large text and interface components.
- **FR-006**: Colours that carry meaning (errors, destructive actions, success messages, warnings, star ratings) MUST remain distinct from the accent colour and from each other.
- **FR-007**: The in-page logo MUST remain clearly visible and fit the scheme in both appearances, and the browser-tab icon and home-screen image MUST be updated to use the scheme's colours while staying visible on light and dark browser tab bars.
- **FR-008**: The existing light, dark and system appearance choices MUST continue to work, and the home page and account pages MUST honour them.

**Home page**

- **FR-009**: For signed-out visitors, the home page MUST show the app name and logo, a welcoming headline, a short explanation of what the app is for, and a prominent sign-up action, all visible without scrolling on phone and desktop screens.
- **FR-010**: The home page MUST offer a log in action to signed-out visitors, visually secondary to the sign-up action.
- **FR-011**: The home page MUST explain how the app works in three steps: create an account, connect with your partner (who must accept), and review things together.
- **FR-012**: The home page MUST show the kinds of things that can be reviewed: films, series, books, food and restaurants.
- **FR-013**: The home page MUST illustrate how both partners' reviews of the same thing can be compared, using clearly fictional sample content.
- **FR-014**: The home page MUST state plainly that reviews are visible only to the two people in the couple.
- **FR-015**: The home page MUST repeat the sign-up action after the supporting sections, at the end of the page.
- **FR-016**: The home page MUST NOT display any real user data, or any live figures such as user or review counts.
- **FR-017**: Home page copy MUST describe only what the app does today, and MUST NOT make claims about features that do not exist, pricing, or endorsements and testimonials from real people.
- **FR-018**: For signed-in users, the home page MUST replace every sign-up and log in action with an action that takes them to their usual signed-in destination.
- **FR-019**: If registration is unavailable, the home page MUST NOT show sign-up actions.
- **FR-020**: The home page MUST be usable at all screen widths from 320 pixels upward with no horizontal scrolling, and MUST be fully operable by keyboard and understandable with a screen reader.
- **FR-021**: Any motion on the home page MUST be disabled for visitors who request reduced motion.

**Regression and quality**

- **FR-022**: Existing behaviour of every page MUST remain unchanged apart from its appearance and the home page content. This includes registration, log in, partner connection, reviews, settings and admin.
- **FR-023**: Automated tests MUST cover the home page content for signed-out and signed-in visitors, the sign-up and log in links, the absence of sign-up actions when registration is unavailable, and the absence of real user data on the page.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A signed-out visitor can get from the home page to the registration form in one click, from both the top and the end of the page.
- **SC-002**: In an informal check with at least 5 people unfamiliar with the app, at least 4 can say what it is for and who it is for within 10 seconds of seeing the home page.
- **SC-003**: The purpose and the sign-up action are visible without scrolling on 100% of tested screen sizes, from a 320-pixel-wide phone to a desktop.
- **SC-004**: 100% of the pages listed in FR-003 use the new colour scheme in both light and dark appearance, confirmed by a page-by-page visual check.
- **SC-005**: 100% of text and interactive elements checked across those pages meet the contrast levels in FR-005.
- **SC-006**: 0 real user details or live usage figures appear on the home page.
- **SC-007**: After the change, sign-up, log in, partner connection and reviews can all be completed exactly as before, and the full automated test suite passes.

## Assumptions

- "Appropriate colour scheme" is taken to mean warm and intimate: a deep berry accent on soft cream backgrounds in light appearance, and on warm near-black backgrounds in dark appearance. Berry is chosen over a brighter rose or red so it stays distinct from error and destructive reds. Exact colour values are decided at planning, within the contrast requirements above.
- This is a colour and home page change. Page layouts, navigation structure and component shapes on existing pages stay as they are. Typography stays the same unless the home page needs a larger headline size.
- The existing logo artwork (two linked hearts) is kept. Only its colour may change.
- The illustrated review comparison uses sample content and makes no use of real reviews, which keeps it consistent with couple-scoped privacy.
- No analytics are being added, so actual sign-up rate is not measured. Success is judged by the clarity and availability of the sign-up route. Measuring conversion would be a separate feature.
- Pricing is not mentioned on the home page because no pricing decision has been recorded.
- The signed-in destination stays as it is today (the "Find your partner" page), and the existing registration flow is unchanged.
- The framework's default error pages and the transactional emails (verification and password reset) are out of scope, as neither is customised today.
- Replacing the logo with a designed one, adding new appearance options and rewriting copy on existing pages are out of scope.
