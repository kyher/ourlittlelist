# Quickstart: Validating Usernames & Partner Search

## Prerequisites

The Sail stack is running (`./vendor/bin/sail up -d`) and the front-end assets are built or
`sail npm run dev` is running.

```bash
sail artisan migrate          # applies add_username_to_users_table
```

## Automated checks

```bash
sail artisan test --filter=Username
sail artisan test --filter=PartnerSearch
sail composer ci:check        # full suite + Pint, matches CI (SQLite)
```

The expected test files are:
- `tests/Feature/Auth/RegistrationTest.php`: registering with a username; duplicate username in a
  different case is rejected; invalid format and reserved names are rejected.
- `tests/Feature/Auth/ChooseUsernameTest.php`: a user without a username is redirected from
  `/dashboard` to `/username`, can set one, and is then let through; logout still works.
- `tests/Feature/Settings/ProfileUpdateTest.php`: changing the username; a taken username is
  rejected and the old value is kept.
- `tests/Feature/PartnerSearchTest.php`: exact match in any case; partial term returns nothing;
  searching your own username returns nothing; the email is never in the rendered output; guests
  are redirected; the 31st search within a minute is rate-limited.

## Manual walkthrough (SC-001)

1. Register **Harry** at `/register` with username `harry`.
2. Sign out, then register **Kyle** with username `Kyle`. Check that it is shown as `kyle`.
3. As Kyle, open **Find your partner** in the sidebar (`/partner/search`). Search `HARRY`.
   **Expected**: a card showing `@harry` and "Harry", with no email.
4. Search `har`. **Expected**: "No user found with that username."
5. Search `kyle`. **Expected**: "No user found with that username."
6. Go to `/settings/profile` and try to change the username to `harry`. **Expected**: "That
   username is taken." Change it to `kyle_h` and confirm that searching `kyle_h` as Harry finds
   Kyle.

## Legacy user check (SC-004)

```bash
sail artisan tinker --execute="App\Models\User::first()->forceFill(['username' => null])->save();"
```

Sign in as that user. Every app page should redirect to `/username` until a username is chosen.
