# Feature Specification: Replace the Laravel Logo

**Feature Branch**: `app-logo`

**Created**: 2026-11-07

**Status**: Draft

**Input**: User description: "Let's look at issue 13 to replace the laravel logo. We should replace with a couple emoji or icon as appropriate for now."

Related issue: #13 "Replace the Laravel logo with an Our Little List logo".

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The app shows its own couple-themed logo (Priority: P1)

Wherever the app shows its logo today, a visitor or signed-in user sees a simple couple-themed icon (two linked hearts) instead of the Laravel logo. This covers the top navigation bar (desktop and the phone slide-out menu), the public home page, and the log in / register / password and verification pages. The icon matches the current light or dark appearance, just as the old logo did.

**Why this priority**: The Laravel logo is the most visible leftover from the starter template and makes the app look unfinished. A placeholder icon that fits the app's purpose (a shared space for two people) is enough for now.

**Independent Test**: Visit the home page, the log in page, and any signed-in page in both light and dark appearance; the couple icon appears in every place the Laravel logo used to, and the Laravel logo appears nowhere.

**Acceptance Scenarios**:

1. **Given** a signed-out visitor, **When** they open the home page, **Then** the couple icon is shown above the app name and the Laravel logo is not shown.
2. **Given** a signed-out visitor, **When** they open the log in or register page, **Then** the couple icon is shown at the top of the form.
3. **Given** a signed-in user on a desktop-width screen, **When** they view any signed-in page, **Then** the navigation bar shows the couple icon next to the "Our Little List" name.
4. **Given** a signed-in user on a phone-width screen, **When** they open the slide-out menu, **Then** the couple icon is shown next to the app name.
5. **Given** a user who has switched between light and dark appearance, **When** they view any page with the logo, **Then** the icon remains clearly visible against the background.

---

### User Story 2 - Browser tabs and home-screen shortcuts use the new icon (Priority: P2)

The small icon shown in browser tabs, bookmarks and phone home-screen shortcuts uses the same couple icon instead of the Laravel logo.

**Why this priority**: Less prominent than the in-page logo, but still shows Laravel branding on every page and on any saved shortcut.

**Independent Test**: Open any page and look at the browser tab; add the site to a phone home screen (or inspect the provided home-screen image); both show the couple icon.

**Acceptance Scenarios**:

1. **Given** any visitor, **When** they open any page, **Then** the browser tab shows the couple icon.
2. **Given** a visitor using a browser that only supports the older icon format, **When** they open any page, **Then** the tab still shows the couple icon, not the Laravel logo.
3. **Given** a phone user, **When** they add the site to their home screen, **Then** the shortcut uses the couple icon.

---

### Edge Cases

- At the smallest size (a 16-pixel browser tab icon) the icon must still read as hearts rather than a blob; detail is kept minimal for this reason.
- In dark appearance, the in-page icon must not disappear into the dark background (it follows the page's text colour, as the old logo did).
- The browser-tab icon does not follow the page's light/dark setting, so it must be visible on both light and dark browser tab bars.
- Browsers that cached the old Laravel tab icon may keep showing it until their cache refreshes; this is acceptable.
- Screen readers continue to announce the app name ("Our Little List") for the logo link rather than describing the image.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The app logo MUST be replaced everywhere it appears (navigation bar on desktop and phone, public home page, all authentication pages) with a single-colour icon of two linked hearts representing a couple.
- **FR-002**: The in-page icon MUST take its colour from the surrounding text so it remains visible in both light and dark appearance.
- **FR-003**: The browser-tab icon in all formats the app provides (modern scalable icon, legacy icon file, and home-screen image) MUST be replaced with the same couple icon.
- **FR-004**: The browser-tab and home-screen icons MUST be legible on both light and dark backgrounds.
- **FR-005**: No Laravel logo artwork MUST remain in any page or in the public icon files.
- **FR-006**: The logo link MUST keep its existing accessible name (the app name) and destination.
- **FR-007**: The icon MUST look the same on every device and browser (it must not depend on the device's emoji font).
- **FR-008**: Automated tests MUST check that the home page and a signed-in page render the new icon and do not contain the Laravel logo.

### Key Entities

- **App icon**: the single couple-themed artwork used for the in-page logo, browser-tab icon and home-screen image. It is a placeholder until a designed logo exists.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 0 pages and 0 public icon files show the Laravel logo.
- **SC-002**: The couple icon appears in 100% of the places the old logo appeared (navigation bar desktop and phone, home page, every authentication page, browser tab, home-screen image).
- **SC-003**: The icon is recognisable as two hearts at a 16-pixel browser-tab size, as judged by a visual check.
- **SC-004**: The icon is clearly visible in both light and dark appearance on every page where it appears.

## Assumptions

- "Couple emoji or icon as appropriate": an icon is chosen over an emoji because emoji look different on every device, cannot follow the app's light/dark colours, cannot be used as a browser-tab icon without being converted to an image anyway, and the common couple emoji show specific genders, which may not represent every couple using the app. Two linked hearts are neutral, recognisable at small sizes, and fit "a shared space for two people".
- The icon is a placeholder; a professionally designed logo is out of scope and may replace it later through the same single source.
- Layout, sizing and placement of the logo stay exactly as they are today; only the artwork changes.
- The browser-tab and home-screen icons use the app's existing accent colour as a background (or a solid colour that contrasts on light and dark tab bars) so they remain visible regardless of browser theme.
- No change to the app name or any other branding text.
