# Research: Initial UI Changes

No open technical questions were raised in the Technical Context. The research below records the decisions made from reading the current codebase (Livewire starter kit on Laravel 13, Flux 2, Livewire 4, Fortify).

## R1. How to provide the horizontal navigation

- **Decision**: Use the starter kit's existing header layout (`resources/views/layouts/app/header.blade.php`), which is built on Flux's `flux:header` + `flux:navbar` and already falls back to a mobile slide-out (`flux:sidebar collapsible="mobile"` + `flux:sidebar.toggle`) below the `lg` breakpoint. Point `resources/views/layouts/app.blade.php` at it instead of `app.sidebar`, wrap the slot in `flux:main container`, then strip it down to: logo, "Find your partner", user menu.
- **Rationale**: Constitution Principle I (build on starter-kit conventions) and VI (simplicity). It is the kit's official alternative layout, so there is nothing new to invent and responsive behaviour (FR-004) comes for free.
- **Alternatives considered**: Hand-rolling a Tailwind navbar with Alpine for the mobile toggle — rejected, it duplicates what Flux already provides. Keeping the sidebar and restyling it horizontally — rejected, fights the component.

## R2. Items to remove from the header layout

- **Decision**: Remove the "Dashboard" items (desktop and mobile), the placeholder "Search" button (`href="#"`), and the "Repository" and "Documentation" items (desktop and mobile). Keep "Find your partner" in both the desktop navbar and the mobile slide-out.
- **Rationale**: FR-002, FR-005, FR-008; the "Search" icon is a non-functional starter-kit placeholder.

## R3. User menu component

- **Decision**: Rewrite `resources/views/components/desktop-user-menu.blade.php` (rename to `user-menu.blade.php`) to use `flux:profile` (header variant, `icon-trailing="chevron-down"`, `align="end"`) instead of `flux:sidebar.profile`. Content stays the same: avatar + name + email (truncated), Settings, Log out (`data-test="logout-button"` kept).
- **Rationale**: `flux:sidebar.profile` is designed for the sidebar. The header layout renders the user menu at all widths, so one component serves desktop and mobile (FR-002, FR-004). Truncation covers the long-name edge case.
- **Alternatives considered**: Keeping two separate menus (desktop + mobile), as the sidebar layout did — rejected, unnecessary duplication.

## R4. Default post-login destination

- **Decision**: Set `config/fortify.php` `'home' => '/partner/search'`, and replace every `route('dashboard')` default with `route('partner.search')`:
  - `resources/views/pages/auth/⚡choose-username.blade.php` (already-has-username redirect and `redirectIntended` default)
  - `resources/views/pages/settings/⚡profile.blade.php` (`redirectIntended` default)
  - `resources/views/components/passkey-verify.blade.php` (`'/dashboard'` JS fallback → `'/partner/search'`)
  - App logo `href` in the header layout
- **Rationale**: FR-006. Fortify's `home` drives login, registration, email verification and two-factor redirects; `redirectIntended` keeps honouring an intended URL (edge case in spec).
- **Alternatives considered**: Adding a `/home` route that redirects to partner search — rejected for now (YAGNI), but noted as the natural place to change when lists/reviews arrive.

## R5. Old `/dashboard` address

- **Decision**: Delete the route and `resources/views/dashboard.blade.php`. `/dashboard` returns Laravel's standard 404. Keep `dashboard` in `config/usernames.php` reserved list.
- **Rationale**: Spec assumption (no external users with bookmarks) and edge case (reserved username kept).

## R6. Public home page

- **Decision**: Replace the 200-line Laravel welcome page (inline compiled CSS + Laravel logo SVG) with a short Blade view that uses the app's own `partials.head` (Vite assets, fonts, dark mode) and Flux components: `x-app-logo-icon`, app name heading, a one-line description, and either Log in / Register buttons (guest) or an "Open Our Little List" button linking to `partner.search` (authenticated).
- **Rationale**: FR-007; reusing `partials.head` keeps styling consistent with the rest of the app and removes the inlined Tailwind build.

## R7. Template-only files to delete

Confirmed unused after the changes above (by grep across `app/`, `resources/`, `routes/`, `tests/`, `config/`):

| File | Why it's safe to remove |
|------|-------------------------|
| `resources/views/layouts/app/sidebar.blade.php` | Replaced by the header layout |
| `resources/views/dashboard.blade.php` | Dashboard removed |
| `resources/views/components/placeholder-pattern.blade.php` | Only used by the dashboard |
| `resources/views/layouts/auth/card.blade.php` | Never referenced (`layouts/auth.blade.php` uses `auth.simple`) |
| `resources/views/layouts/auth/split.blade.php` | Never referenced; contains the random "Inspiring" quote panel |
| `resources/views/flux/icon/layout-grid.blade.php` | Only used by Dashboard nav items |
| `resources/views/flux/icon/folder-git-2.blade.php` | Only used by Repository links |
| `resources/views/flux/icon/book-open-text.blade.php` | Only used by Documentation links |
| `resources/views/flux/icon/chevrons-up-down.blade.php` | Only used by the sidebar profile button replaced in R3 |

Kept: `resources/views/flux/navlist/group.blade.php` (used by the settings sub-navigation), `x-app-logo` `sidebar` variant (used by the mobile slide-out), all auth/passkey/two-factor/settings views.

## R8. Tests

- **Decision**: Delete `tests/Feature/DashboardTest.php`. Update assertions in `Auth/AuthenticationTest`, `Auth/RegistrationTest`, `Auth/EmailVerificationTest`, `Auth/ChooseUsernameTest` from `route('dashboard')` to `route('partner.search')`. Add `tests/Feature/NavigationTest.php` (nav contents, current item, no Dashboard/Repository/Documentation, `/dashboard` is 404) and extend `HomePageTest` (guest vs authenticated links, no Laravel marketing copy).
- **Rationale**: Constitution Principle IV and FR-011.
