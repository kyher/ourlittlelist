# Quickstart: Validating the Design Refresh

## Prerequisites

Docker stack running (`docker compose up -d`) and assets built (`docker compose exec app npm run build`, or the `vite` service running). A registered user with a partner and at least one review, for the signed-in pages.

## Automated checks

```bash
docker compose exec app php artisan test --filter='HomePageTest|AppLogoTest'
docker compose exec app composer ci:check
```

Expected: all green.

## Home page checks

| # | Check | Expected | Spec ref |
|---|-------|----------|----------|
| 1 | `/` signed out at 320×568, 390×844, 1440×900 | Headline, explanation and "Create your account" visible without scrolling; no horizontal scroll | US1-1, FR-020, SC-003 |
| 2 | Follow the hero and end-of-page actions | Each reaches `/register` in one click | US1-2, US1-6, SC-001 |
| 3 | Look at the top bar | "Log in" present and visually secondary | US1-3 |
| 4 | Scroll the page | Example, three steps, five categories and privacy statement present | US1-4, US1-5 |
| 5 | `/` signed in | Only "Open Our Little List" actions; each reaches the usual signed-in page | US3 |
| 6 | `/` signed in as admin only | Same as signed out | Edge case |
| 7 | Navigate by keyboard; check headings with a screen reader or the accessibility tree | Logical order, visible focus, one `<h1>` | FR-020 |

See [contracts/home-page.md](contracts/home-page.md) for actions and copy.

## Colour checks

Visit each page below in light and dark appearance. Backgrounds should be cream or warm near-black, with the berry accent on primary buttons, links, focus outlines and the current navigation item, and everything readable.

| Area | Pages |
|------|-------|
| Public | `/` |
| Account | `/login`, `/register`, `/forgot-password`, reset password, verify email, confirm password, two-factor challenge, `/username` |
| App | `/partner/search`, `/couple`, `/reviews`, `/reviews/new`, a review detail page |
| Settings | Profile, Security (including the two-factor setup modal), Appearance |
| Admin | `/admin/login`, `/admin` |

| # | Check | Expected | Spec ref |
|---|-------|----------|----------|
| 8 | Run a contrast checker (browser dev tools or axe) on each page, both appearances | No failures for FR-005 levels; disabled controls exempt | SC-005 |
| 9 | Compare a danger button, an error message, a success message and stars with the accent | Each is clearly a different colour | US2-5, FR-006 |
| 10 | Switch Appearance between Light, Dark and System; change the device setting in System mode | Site follows on app, home and login pages, with no flash on reload | US2-2, US2-3 |
| 11 | Browser tab and `/apple-touch-icon.png`, hard refresh | White hearts on berry, visible on light and dark tab bars | US2-4 |
| 12 | `grep -rnE '(stone\|neutral\|gray\|slate)-[0-9]' resources/views` | No output | FR-002 |
| 13 | Register, log in, connect partners, create and edit a review | Works exactly as before | SC-007, FR-022 |

See [contracts/design-tokens.md](contracts/design-tokens.md) for exact values.
