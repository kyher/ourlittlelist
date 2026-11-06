# Quickstart: Validating Initial UI Changes

## Prerequisites

The Docker stack is running (`docker compose up -d`) and assets are built (`docker compose exec app npm run build`, or `npm run dev` for hot reload).

## Automated checks

```bash
docker compose exec app php artisan test
docker compose exec app composer ci:check   # Pint, Larastan, tests (same as CI)
```

Expected: all green. `DashboardTest` no longer exists. `NavigationTest` and the extended `HomePageTest` pass.

## Manual walkthrough

| # | Step | Expected | Spec ref |
|---|------|----------|----------|
| 1 | Signed out, open `/` | Our Little List name, one-line description, Log in + Register. No Laravel logo, "Let's get started" or Laracasts/docs links | US3-1 |
| 2 | Log in | Lands on `/partner/search` | US2-1 |
| 3 | Look at the page at desktop width | Horizontal top bar: logo, "Find your partner" (highlighted), user menu on the right. No sidebar, no Dashboard/Repository/Documentation | US1-1, US1-2, US3-3 |
| 4 | Open user menu | Name, email, Settings, Log out. Settings opens `/settings/profile` inside the same top-bar layout with its own sub-navigation intact | US1-3 |
| 5 | Click the logo | Goes to `/partner/search` | US1-5 |
| 6 | Resize to phone width (~375px) | Top bar shows menu toggle, logo, user menu; toggle opens slide-out with "Find your partner"; no horizontal scroll | US1-4 |
| 7 | Visit `/dashboard` | 404 page | US2-4 |
| 8 | Signed in, open `/` | Link into the app instead of Log in / Register | US3-2 |
| 9 | Register a new account | Ends on `/partner/search` (after choosing a username, if prompted) | US2-2 |
| 10 | Log out, try `/settings/profile`, then log in | Returns to `/settings/profile` (intended URL honoured) | Edge case |

See `contracts/ui-routes.md` for the full route and layout contract.
