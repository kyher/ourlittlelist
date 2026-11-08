#!/usr/bin/env bash
# Brings a checkout up to date before the container's command runs, so a fresh
# clone (or a pull with new dependencies or migrations) works with `docker compose up`.
#
#   ROLE=app   PHP dependencies, .env, app key, migrations
#   ROLE=vite  Node dependencies
#
# With no ROLE (e.g. `docker compose run --rm app bash`), the command runs straight away.
set -euo pipefail

case "${ROLE:-}" in
    app)
        [ -f .env ] || cp .env.example .env
        composer install --no-interaction --no-progress
        grep -qE '^APP_KEY=.+' .env || php artisan key:generate --no-interaction
        php artisan migrate --force --no-interaction
        ;;
    vite)
        npm install --no-audit --no-fund
        ;;
esac

exec "$@"
