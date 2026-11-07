# Our Little List

A private shared space for couples. Two people connect their accounts and both review the
things they experience together, such as films, series, books, food and restaurants, so they
can compare notes and keep a list of what they've loved (or not).

Built with Laravel 13 and the official Livewire starter kit (Livewire 4, Flux UI, Fortify),
and run entirely in Docker with Docker Compose.

## Prerequisites

- Docker with Docker Compose v2

That's it. You don't need PHP, Composer or Node on your machine: everything runs in containers.

## Getting started

```bash
docker compose up
```

The first run builds the app image and takes a few minutes. Each start then:

- installs PHP and Node dependencies (fast when nothing has changed),
- creates `.env` from `.env.example` and generates an app key if they're missing,
- runs any pending migrations,
- serves the app at <http://localhost> with the Vite dev server (hot reload) alongside it.

Add `-d` to run in the background. Because dependencies and migrations are applied on every
start, pulling new changes only needs a `docker compose up` (or `docker compose restart app vite`).

## Everyday commands

Commands run inside the `app` container with `docker compose exec app …`. A shell alias saves
typing, e.g. `alias dc='docker compose exec app'`.

| What | Command |
|------|---------|
| Start the stack | `docker compose up -d` |
| Follow the logs | `docker compose logs -f app vite` |
| Stop the stack | `docker compose stop` (data is kept) |
| Run the full CI check (Pint, Larastan, tests) | `docker compose exec app composer ci:check` |
| Run the test suite | `docker compose exec app php artisan test` |
| Fix code style | `docker compose exec app composer lint` |
| Run an Artisan command | `docker compose exec app php artisan <command>` |
| Rebuild the database from scratch (**deletes all data**) | `docker compose exec app php artisan migrate:fresh` |
| Build front-end assets once (without the dev server) | `docker compose exec vite npm run build` |
| Open a shell in the app container | `docker compose exec app bash` |
| Rebuild the app image (after changing `docker/app/`) | `docker compose build` |
| Remove containers **and** the database volume | `docker compose down -v` |

### Viewing emails

In development, no real emails are sent. Everything the app sends (e.g. password reset links) is
captured by Mailpit at <http://localhost:8025>.

## Changing ports

If a port is already taken on your machine, override it in `.env` (no committed files need to
change), then run `docker compose up -d` again:

| Variable | Default | Used for |
|----------|---------|----------|
| `APP_PORT` | `80` | The web app. Also update `APP_URL`, e.g. `http://localhost:8080` |
| `VITE_PORT` | `5173` | Vite dev server (hot reload) |
| `FORWARD_DB_PORT` | `3306` | MySQL, from the host (e.g. for a DB GUI) |
| `FORWARD_MAILPIT_PORT` | `1025` | Mailpit SMTP |
| `FORWARD_MAILPIT_DASHBOARD_PORT` | `8025` | Mailpit web UI |

## Couples

A user who isn't in a couple uses **Find your partner** (`/partner/search`) to look up their
partner by exact username and send a request. The other person accepts or rejects it on the same
page. Once they accept, both people see **Your partner** (`/couple`) instead. Either partner can
end the couple there. Ending a couple **permanently deletes the couple's reviews** for both of
them. Couple-owned tables must reference `couples.id` with `cascadeOnDelete` so this keeps working.

## How it's put together

- `compose.yaml` defines four services: `app` (`php artisan serve`), `vite` (`npm run dev`),
  `mysql` and `mailpit`. `app` and `vite` share one image and bind-mount the project.
- `docker/app/Dockerfile` is the toolchain image: PHP 8.4 with the needed extensions, Composer
  and Node 24. It runs as a user matching UID/GID 1000 so files it writes are owned by you. If
  your host user has a different ID, build with `UID=$(id -u) GID=$(id -g) docker compose build`.
- `docker/app/entrypoint.sh` does the install/setup steps listed above before each service starts.

## Troubleshooting

- **The page has no styling or shows a Vite manifest error.** The `vite` service isn't running.
  Check `docker compose logs vite`, or build the assets once with
  `docker compose exec vite npm run build`.
- **The app doesn't respond right after `docker compose up`.** It waits for MySQL to be healthy,
  then installs dependencies and migrates. Watch `docker compose logs -f app` until it prints
  "Server running".
- **`docker compose up` fails with "port is already allocated".** See [Changing ports](#changing-ports).
- **Permission errors writing to `storage/` or `vendor/`.** Your host UID/GID isn't 1000; rebuild
  as described in [How it's put together](#how-its-put-together).

## Project process

Features are specified and planned with [Spec Kit](https://github.com/github/spec-kit):
see `specs/` for feature specs and `.specify/memory/constitution.md` for the project principles.
