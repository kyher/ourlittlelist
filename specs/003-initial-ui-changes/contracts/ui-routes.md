# UI Route & Layout Contract: Initial UI Changes

Routes not listed here behave as described in `specs/002-usernames/contracts/ui-routes.md`.

## Routes

| Path | Name | Middleware | Behaviour | Spec ref |
|------|------|------------|-----------|----------|
| `/` *(changed)* | `home` | none | Our Little List landing page. Guest: app name, one-line description, **Log in** and **Register** links. Authenticated: app name, description, and a link to `/partner/search`. No Laravel marketing copy or links | US3, FR-007 |
| `/dashboard` *(removed)* | none | none | `404 Not Found` | US2-4, FR-005 |
| `/partner/search` *(changed)* | `partner.search` | auth, verified, has-username | Unchanged page; now the default signed-in destination and rendered in the header layout | US2, FR-006 |

## Redirect destinations

| Flow | Before | After |
|------|--------|-------|
| Log in (password, passkey, two-factor) | `/dashboard` | intended URL, else `/partner/search` |
| Register | `/dashboard` | `/partner/search` (via `has-username`, `/username` if no username) |
| Email verification link | `/dashboard?verified=1` | `/partner/search?verified=1` |
| `/username` when user already has one | `/dashboard` | `/partner/search` |
| `/username` submit | intended, else `/dashboard` | intended, else `/partner/search` |
| Profile update (`redirectIntended` default) | `/dashboard` | `/partner/search` |

## Authenticated layout (`layouts::app`)

Every page using `layouts::app` (partner search, all settings pages) renders:

| Element | Desktop (≥ lg) | Narrow (< lg) |
|---------|----------------|---------------|
| Top bar | Always shown | Always shown |
| App logo + name → `/partner/search` | In top bar | In top bar and slide-out |
| "Find your partner" → `/partner/search`, marked current on that route | In top bar | In slide-out menu opened by the menu toggle |
| User menu (avatar → name, email, **Settings** → `/settings/profile`, **Log out** with `data-test="logout-button"`) | Right of top bar | Right of top bar |
| Vertical sidebar | Not rendered | Only as the closed-by-default slide-out |
| "Dashboard", "Repository", "Documentation", placeholder "Search" | Not rendered | Not rendered |
