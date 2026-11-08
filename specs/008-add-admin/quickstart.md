# Quickstart: Validate Admin Users and System Totals

**Prerequisites**: The Docker stack is running (`docker compose up -d`), and migrations have been
applied with `docker compose exec app php artisan migrate`. You have a regular user account that
has verified its email and chosen a username.

Behaviour details are in [contracts/admin-command.md](contracts/admin-command.md) and
[contracts/admin-routes.md](contracts/admin-routes.md).

## 1. Create an admin

```bash
docker compose exec app php artisan admin:create admin@example.com
```

**Expect**: Two hidden password prompts. A mismatched confirmation re-prompts. After valid entries,
a success message and exit code 0.

## 2. Command failures

```bash
docker compose exec app php artisan admin:create ADMIN@example.com   # duplicate, any case
docker compose exec app php artisan admin:create not-an-email        # malformed
docker compose exec -T app php artisan admin:create new@example.com  # no TTY
```

**Expect**: Each prints a clear message, exits with a non-zero code and creates nothing. Check with
`echo $?`.

## 3. Log in and see totals

1. Open `/admin` while signed out. **Expect**: redirected to `/admin/login`.
2. Sign in as the admin. **Expect**: the totals page with Users, Couples and Reviews.
3. Compare with the database:

   ```bash
   docker compose exec app php artisan tinker --execute="dump(App\Models\User::count(), App\Models\Couple::count(), App\Models\Review::count());"
   ```

   **Expect**: the figures match, and the page shows no names, emails or review content.

## 4. Separation

1. Enter the regular user's credentials at `/admin/login`. **Expect**: the generic failure message.
2. Enter the admin's credentials at `/login`. **Expect**: the generic failure message.
3. In a private window signed in only as the regular user, open `/admin`. **Expect**: redirected to
   `/admin/login`.
4. In a private window signed in only as the admin, open `/reviews`. **Expect**: redirected to
   `/login`.

## 5. Independent sessions

1. In one browser, sign in as the regular user at `/login` and as the admin at `/admin/login`.
2. Log out of the regular account, then reload `/admin`. **Expect**: still signed in as admin.
3. Sign in as the regular user again, log out of the admin area, then reload `/reviews`.
   **Expect**: still signed in as the regular user.

## 6. Throttling

Submit a wrong password at `/admin/login` six times within a minute. **Expect**: the sixth attempt
returns a 429 response.

## 7. Automated checks

```bash
docker compose exec app php artisan test --filter=Admin
docker compose exec app composer ci:check
docker compose exec app ./vendor/bin/pint --test
```

**Expect**: All pass.
