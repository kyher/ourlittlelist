# UI Route Contract: Application Foundation

All routes are server-rendered Livewire/Blade pages. The paths are the starter kit's defaults and
later features build on them. "Guest" means the route is only for users who are not signed in.
"Auth" means it requires sign-in, and visitors are redirected to `/login` (FR-005).

| Path | Access | Purpose | Spec ref |
|------|--------|---------|----------|
| `/` | public | Welcome page with links to sign in or register | US1 |
| `/register` | guest | Create account (name, email, password, confirmation) → `/dashboard` | US2-1 |
| `/login` | guest | Sign in (email, password, remember me). Wrong credentials show an error | US2-2 |
| `/logout` (POST) | auth | Sign out → `/` | US2 |
| `/forgot-password` | guest | Request a reset link (the email lands in Mailpit) | US2-3 |
| `/reset-password/{token}` | guest | Set a new password | US2-3 |
| `/dashboard` | auth | Placeholder dashboard that greets the user by name | FR-004 |
| `/settings/profile` | auth | Update name or email. Delete account (requires password) | US2-5 |
| `/settings/security` (kit name may vary) | auth | Change password. Manage optional 2FA | US2-5 |
| `/settings/appearance` | auth | Light, dark or system theme | n/a (kit) |
| `/verify-email`, `/confirm-password`, `/two-factor-challenge` | auth/guest | Kit flows, kept unchanged | R5 |

## Developer command contract

The README (FR-002) MUST document these commands, and they MUST work as described:

| Intent | Command |
|--------|---------|
| First-time dependency install (fresh clone) | `docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install --ignore-platform-reqs` |
| Create env file | `cp .env.example .env` |
| Start / stop | `./vendor/bin/sail up -d` / `./vendor/bin/sail stop` |
| App key, migrations, front-end deps | `sail artisan key:generate`, `sail artisan migrate`, `sail npm install` |
| Front-end assets | `sail npm run dev` (hot reload) or `sail npm run build` |
| Tests | `sail artisan test` |
| Code style check / fix | `sail bin pint --test` / `sail bin pint` |
| Rebuild DB from scratch | `sail artisan migrate:fresh` |
| Mail viewer | `http://localhost:${FORWARD_MAILPIT_DASHBOARD_PORT:-8025}` |
