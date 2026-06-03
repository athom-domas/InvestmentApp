# Investment Intelligence

Stock screener and portfolio tracker. Laravel 13 + Inertia.js + Vue 3 + Tailwind CSS, running in Docker.

---

## Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13, PHP 8.3 |
| Frontend | Vue 3, Inertia.js, Tailwind CSS |
| Database | MySQL 8 (dev/prod), SQLite in-memory (tests) |
| Queue / Cache | MySQL (`database` driver — no Redis required) |
| Mail | Mailpit (dev) |

---

## Setup

```bash
make up            # start all containers
make migrate       # run migrations
make seed          # seed demo data
```

App: `http://localhost` · phpMyAdmin: `http://localhost:8080` · Mailpit: `http://localhost:8025`

---

## Development

```bash
docker compose exec app composer dev   # Laravel + Vite + queue + pail in one
make npm-dev                            # Vite HMR only
make logs                               # tail app logs
make shell                              # sh into the app container
```

---

## Testing

Tests use SQLite in-memory — no setup needed.

```bash
make test                              # full suite
make test-filter filter=NormalizerTest # single test/class
```

---

## Scoring Engine

The scoring engine computes factor-based rankings for all active securities.

### Run manually (synchronous)

```bash
make artisan cmd="scoring:run"
make artisan cmd="scoring:run --universe=NASDAQ"
make artisan cmd="scoring:run --limit=50 --dry-run"
```

| Option | Default | Description |
|---|---|---|
| `--universe` | `ALL` | Exchange filter: `ALL`, `NASDAQ`, `NYSE`, `MIL`, … |
| `--model-version` | config | Override the model version string |
| `--limit` | — | Score only the first N active securities |
| `--dry-run` | false | Compute without persisting to DB |
| `--queue` | false | Dispatch as background job (see below) |

### Run via queue

```bash
make artisan cmd="scoring:run --queue"
make artisan cmd="scoring:run --queue --universe=NYSE"
```

Dispatches `RunScoringJob` to the queue. A worker must be running to process it.

### Queue worker

```bash
make queue-work
# or, inside the container:
docker compose exec app php artisan queue:work --tries=1 --timeout=660
```

`QUEUE_CONNECTION=database` by default — no Redis required.

### Scheduled automatic runs

The scheduler runs `scoring:run` daily via Laravel's task scheduler.

**Default schedule:** every day at 06:00.

Configure via `.env`:

```env
SCORING_SCHEDULE_ENABLED=true     # set to false to disable
SCORING_SCHEDULE_TIME=06:00       # HH:MM (24h)
SCORING_SCHEDULE_UNIVERSE=ALL     # universe filter
```

Add a system cron entry that calls the Laravel scheduler every minute:

```cron
# From the host (Docker):
* * * * * cd /path/to/project && docker compose exec -T app php artisan schedule:run >> /dev/null 2>&1

# Or from inside the container:
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

`withoutOverlapping()` skips a new run if the previous one is still in progress.
`runInBackground()` returns the scheduler immediately without waiting for the command to finish.

---

## Database

```bash
make migrate            # run pending migrations
make migrate-fresh      # drop all + re-migrate + seed
make seed               # seed only
```

---

## Assets

```bash
make npm-build          # production Vite build
make npm-install        # install node_modules inside container
```

---

## Misc

```bash
make artisan cmd="route:list"     # any artisan command
make composer cmd="require foo"   # any composer command
make cache-clear                  # php artisan optimize:clear
make tinker                       # Laravel REPL
```
