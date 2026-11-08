# Quickstart: Validate Email Verification

Expected behaviour is defined in [contracts/ui-routes.md](contracts/ui-routes.md).

## Prerequisites

```sh
docker compose up -d
docker compose exec app php artisan migrate
```

Emails appear in Mailpit at <http://localhost:8025>.

## Automated checks

```sh
docker compose exec app composer ci:check
```

Expected: all checks pass.

## Manual scenarios

| # | Steps | Expected |
|---|-------|----------|
| 1 | Register a new account | "Verify your email" page shows your address; one email in Mailpit |
| 2 | Visit `/partner/search`, `/couple`, `/settings/appearance` | Each returns to the verify page |
| 3 | Follow the emailed link | You reach partner search; the verify page no longer appears |
| 4 | Register again, use **Resend** | Confirmation shown; second email in Mailpit |
| 5 | From the verify page, follow the change-address link and save a corrected address | Email arrives at the new address, and its link verifies the account |
| 6 | As a verified user, change your email in profile settings | Email arrives without asking; member pages lead to the verify page |
| 7 | Follow a link issued before the change in scenario 6 | Refused; account stays unverified |
| 8 | Open `/settings/appearance` in one tab, change your email in another, then change a setting in the first tab | The first tab goes to the verify page |
| 9 | Change a link's `expires` value, then open it | Verify page shows the invalid-link message |
| 10 | Stop Mailpit (`docker compose stop mailpit`), register, then restart it | No error page; you reach the verify page and can resend once it is back |
| 11 | Sign in at `/admin/login` | Admin pages work with no verification prompt |
