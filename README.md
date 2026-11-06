# Our Little List

A private shared space for couples. Two people connect their accounts and both review the
things they experience together, such as films, series, books, food and restaurants, so they
can compare notes and keep a list of what they've loved (or not).

Built with Laravel 13 and the official Livewire starter kit (Livewire 4, Flux UI, Fortify),
and run entirely in Docker via [Laravel Sail](https://laravel.com/docs/sail).

## Prerequisites

- Docker with Docker Compose v2

That's it. You don't need PHP, Composer or Node on your machine: everything runs in containers.

## First-time setup

```bash
# 1. Install PHP dependencies using a throwaway container (creates vendor/, which includes Sail)
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

# 2. Create your local environment file
cp .env.example .env

# 3. Start the containers (app, MySQL, Mailpit)
./vendor/bin/sail up -d

# 4. Generate the app key and create the database schema
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate

# 5. Install and build front-end assets
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

Open <http://localhost> (or the port you set in `APP_PORT`).

> **Tip:** add `alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'` to your shell
> profile so you can type `sail …` instead of `./vendor/bin/sail …`. The rest of this README uses
> the short form.

## Everyday commands

| What | Command |
|------|---------|
| Start the stack | `sail up -d` |
| Stop the stack | `sail stop` (data is kept) |
| Front-end dev server with hot reload | `sail npm run dev` |
| Build front-end assets once | `sail npm run build` |
| Run the test suite | `sail artisan test` |
| Check code style | `sail bin pint --test` |
| Fix code style | `sail bin pint` |
| Run new migrations | `sail artisan migrate` |
| Rebuild the database from scratch (**deletes all data**) | `sail artisan migrate:fresh` |
| Open a shell in the app container | `sail shell` |
| Remove containers **and** the database volume | `sail down -v` |

### Viewing emails

In development, no real emails are sent. Everything the app sends (e.g. password reset links) is
captured by Mailpit at <http://localhost:8025>.

## Changing ports

If a port is already taken on your machine, override it in `.env` (no committed files need to
change), then run `sail up -d` again:

| Variable | Default | Used for |
|----------|---------|----------|
| `APP_PORT` | `80` | The web app. Also update `APP_URL`, e.g. `http://localhost:8080` |
| `VITE_PORT` | `5173` | Vite dev server (hot reload) |
| `FORWARD_DB_PORT` | `3306` | MySQL, from the host (e.g. for a DB GUI) |
| `FORWARD_MAILPIT_PORT` | `1025` | Mailpit SMTP |
| `FORWARD_MAILPIT_DASHBOARD_PORT` | `8025` | Mailpit web UI |

## Troubleshooting

- **The page has no styling or shows a Vite manifest error.** Assets haven't been built. Run
  `sail npm run build`, or keep `sail npm run dev` running.
- **"Connection refused" or database errors right after `sail up`.** MySQL is still starting.
  Wait until `sail ps` shows the `mysql` service as `healthy`, then retry.
- **`sail up` fails with "port is already allocated".** See [Changing ports](#changing-ports).

## Project process

Features are specified and planned with [Spec Kit](https://github.com/github/spec-kit):
see `specs/` for feature specs and `.specify/memory/constitution.md` for the project principles.
