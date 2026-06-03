# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

Laravel 13 + Breeze + Inertia.js + Vue 3 + Tailwind CSS, running in Docker. Database: MySQL 8 (prod/dev), SQLite in-memory (tests). Cache/queues: Redis. Mail: Mailpit.

## Development

All commands run inside Docker via `make`. Start the stack first:

```bash
make up       # start all containers
make down     # stop
make logs     # tail app logs
make shell    # open sh inside app container
```

Run the full dev server (Laravel + Vite + queue + pail) inside the container:

```bash
docker compose exec app composer dev
```

Or individually:

```bash
make npm-dev  # Vite HMR on :5173
```

App is at `http://localhost` (nginx) or `http://localhost:8000` (artisan serve). phpMyAdmin at `:8080`, Mailpit at `:8025`.

## Testing

Tests use SQLite in-memory — no DB setup needed.

```bash
make test                        # run all tests
make test-filter filter=MyTest   # run a single test/class
```

## Database

```bash
make migrate          # run pending migrations
make migrate-fresh    # drop all + re-migrate + seed
make seed             # seed only
```

## Assets

```bash
make npm-build   # production Vite build
make npm-install # install node_modules inside container
```

## Misc

```bash
make artisan cmd="route:list"   # run any artisan command
make composer cmd="require foo"  # run any composer command
make cache-clear                 # php artisan optimize:clear
make tinker                      # Laravel REPL
```

## Architecture

- **Routes**: `routes/web.php` (app routes) + `routes/auth.php` (Breeze auth). All routes return `Inertia::render()` responses.
- **Frontend**: Vue 3 pages live in `resources/js/Pages/`. Inertia resolves page components by name. Ziggy provides named route helpers in JS (`route('name')`).
- **Entry point**: `resources/js/app.js` bootstraps Inertia + Vue + Ziggy. `resources/views/app.blade.php` is the single Blade shell.
- **Linting**: `./vendor/bin/pint` (PHP), Vite/ESLint not configured yet.
