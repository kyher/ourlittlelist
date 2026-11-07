# Quickstart: Validating the New Logo

## Prerequisites

Docker stack running (`docker compose up -d`) and assets built (`docker compose exec app npm run build`, or the `vite` service running).

## Automated checks

```bash
docker compose exec app php artisan test --filter=AppLogoTest
docker compose exec app composer ci:check
```

Expected: all green.

## Manual / screenshot checks

| # | Check | Expected | Spec ref |
|---|-------|----------|----------|
| 1 | `/` signed out, light and dark | Two linked hearts above "Our Little List", clearly visible in both | US1-1, US1-5 |
| 2 | `/login` and `/register` | Hearts above the form | US1-2 |
| 3 | `/partner/search` at desktop width | Hearts in the dark square next to "Our Little List" in the top bar | US1-3 |
| 4 | `/partner/search` at ~375px, open slide-out | Hearts next to the app name in the slide-out | US1-4 |
| 5 | Browser tab (hard refresh to bypass cache) | Hearts on a dark rounded square, readable at tab size on light and dark browser themes | US2-1, SC-003 |
| 6 | Open `/favicon.ico` and `/apple-touch-icon.png` directly | Same hearts artwork; no Laravel logo | US2-2, US2-3 |
| 7 | `grep -rn "M17.2 5.633\|M162.041" resources public` | No output | SC-001 |

See `contracts/logo.md` for the exact component and file contract.
