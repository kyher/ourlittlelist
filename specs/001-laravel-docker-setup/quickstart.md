# Quickstart Validation: Application Foundation

Proves the feature end to end. The exact commands are in
[contracts/ui-routes.md](contracts/ui-routes.md#developer-command-contract).
Throughout, `sail` means `./vendor/bin/sail`.

## Prerequisites

- Docker with Compose v2+. No PHP, Composer or Node is needed on the host.
- Ports 80, 3306, 5173 and 8025 are free, or overridden in `.env` (`APP_PORT`, `FORWARD_DB_PORT`,
  `VITE_PORT`, `FORWARD_MAILPIT_DASHBOARD_PORT`).

## V1: Fresh clone to running app (US1, SC-001)

1. Clone into a new directory and run the first-time Composer install container.
2. `cp .env.example .env`, then `sail up -d`, `sail artisan key:generate`, `sail artisan migrate`,
   `sail npm install`, `sail npm run build`.
3. Open `http://localhost`. **Expect** the welcome page, styled, with "Our Little List" in the
   title.
4. Edit a Blade view, then refresh with `sail npm run dev` running. **Expect** the change to
   appear without rebuilding containers.

## V2: Account flows (US2)

1. Register at `/register`. **Expect** to land on `/dashboard`, greeted by name.
2. Log out, then visit `/dashboard`. **Expect** a redirect to `/login`.
3. Log in with a wrong password (**expect** an error), then with the right one (**expect** the
   dashboard).
4. Use `/forgot-password`, open Mailpit at `http://localhost:8025`, follow the link and set a new
   password. **Expect** to be able to sign in with it.
5. Change your name at `/settings/profile`. **Expect** a saved confirmation.
6. Register again with the same email. **Expect** a "taken" validation error.

## V3: Persistence (US1-2, SC-005)

`sail stop && sail up -d`, then sign in with the account from V2. **Expect** success.

## V4: Quality gates (US3, SC-003, SC-004)

- `sail artisan test`: **expect** all tests to pass in under 2 minutes, covering registration,
  authentication, password reset, profile update and account deletion.
- `sail bin pint --test`: **expect** no violations.
- `sail artisan migrate:fresh`: **expect** a clean run. Then re-run V2 step 1 to confirm.

## V5: Port override (edge case)

Set `APP_PORT=8080` in `.env`, then `sail up -d`. **Expect** the app at `http://localhost:8080`.
