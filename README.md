# Investment Intelligence

Screener azionario con ranking spiegabile, watchlist e monitoraggio portafoglio.
I punteggi sono calcolati su sei fattori quantitativi (qualità, valore, crescita, momentum, solidità finanziaria, rischio) e accompagnati da spiegazioni in linguaggio semplice.

> **Finalità informativa.** Questo progetto mostra analisi e ranking a scopo esclusivamente informativo.
> Non fornisce consulenza finanziaria personalizzata, non promette rendimenti e non sostituisce
> un consulente finanziario abilitato.

---

## Funzionalità

| Area | Cosa fa |
|---|---|
| **Ranking** | Elenco titoli ordinati per punteggio con fattori e spiegazione |
| **Dettaglio titolo** | Score breakdown, fondamentali, prezzi storici, spiegazione testuale |
| **Watchlist** | Salva titoli interessanti da monitorare |
| **Portafoglio** | Traccia posizioni, calcola esposizione settoriale e valutazione |
| **Scoring engine** | Calcola ranking su richiesta o in automatico via scheduler |

---

## Stack

| Layer | Tecnologia |
|---|---|
| Backend | Laravel 13, PHP 8.3 |
| Frontend | Vue 3, Inertia.js, Tailwind CSS, Vite |
| Database | MySQL 8 (dev/prod), SQLite in-memory (test) |
| Code/Cache | MySQL (`database` driver — Redis opzionale) |
| Mail | Mailpit (dev) |

---

## Setup locale (Docker — consigliato)

```bash
make up             # avvia tutti i container
make migrate-fresh  # migra e popola con dati demo
```

App: `http://localhost` · phpMyAdmin: `http://localhost:8080` · Mailpit: `http://localhost:8025`

Avvia il dev server completo (Laravel + Vite HMR + queue + pail):

```bash
docker compose exec app composer dev
```

Oppure singolarmente:

```bash
make npm-dev    # solo Vite HMR su :5173
make logs       # tail log applicazione
make shell      # sh nel container app
```

---

## Setup bare-metal (senza Docker)

Prerequisiti: PHP 8.3, Composer, Node.js 20+, MySQL 8.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Crea il database MySQL, poi configura `.env` (vedi sezione sotto), poi:

```bash
php artisan migrate --seed
npm install
npm run dev          # terminale 1 — Vite HMR
php artisan serve    # terminale 2 — Laravel su :8000
php artisan queue:work  # terminale 3 — worker code (opzionale)
```

---

## Configurazione .env (MySQL)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=investment_intelligence
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database   # nessun Redis richiesto
CACHE_STORE=file            # oppure database; Redis opzionale
```

Per abilitare Redis (opzionale, migliora le performance cache):

```env
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

---

## Database

```bash
make migrate            # esegui migrazioni pendenti
make migrate-fresh      # drop + re-migra + seed demo
make seed               # solo seed
```

Bare-metal:

```bash
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed
```

---

## Comandi Artisan personalizzati

### `scoring:run` — Calcola il ranking

```bash
php artisan scoring:run
php artisan scoring:run --universe=NASDAQ
php artisan scoring:run --universe=MIL --limit=50
php artisan scoring:run --dry-run          # calcola senza salvare
php artisan scoring:run --queue            # dispatcha come job in background
```

| Opzione | Default | Descrizione |
|---|---|---|
| `--universe` | `ALL` | Filtro mercato: `ALL`, `NASDAQ`, `NYSE`, `MIL`, … |
| `--model-version` | da config | Sovrascrive la versione del modello |
| `--limit` | — | Limita ai primi N titoli attivi |
| `--dry-run` | false | Calcola senza persistere sul DB |
| `--queue` | false | Dispatcha `RunScoringJob` sulla coda |

### Comandi import dati (non ancora implementati)

I seguenti comandi sono pianificati per l'integrazione con sorgenti dati esterne:

```bash
php artisan import:securities path/to/securities.csv
php artisan import:price-bars path/to/prices.csv --security=AAPL
php artisan import:fundamentals path/to/fundamentals.csv
```

Per ora i dati demo vengono popolati dai seeder (`DemoSecuritiesSeeder`).

---

## Queue worker

```bash
make queue-work
# oppure, bare-metal:
php artisan queue:work --tries=1 --timeout=660
```

Il driver predefinito è `database` — nessun Redis richiesto.
Per produzione si consiglia di avviare il worker tramite Supervisor.

---

## Scheduler (aggiornamenti automatici)

Lo scheduler esegue `scoring:run` ogni giorno alle 06:00 (configurabile).

Aggiungi questa riga al crontab del sistema:

```cron
# Docker:
* * * * * cd /path/to/project && docker compose exec -T app php artisan schedule:run >> /dev/null 2>&1

# Bare-metal:
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Configura lo scheduling via `.env`:

```env
SCORING_SCHEDULE_ENABLED=true    # false per disabilitare
SCORING_SCHEDULE_TIME=06:00      # orario HH:MM (24h)
SCORING_SCHEDULE_UNIVERSE=ALL    # filtro universe
```

Verifica il prossimo run pianificato:

```bash
make artisan cmd="schedule:list"
```

---

## Testing

I test usano SQLite in-memory — nessun setup DB necessario.

```bash
make test                              # suite completa
make test-filter filter=NormalizerTest # singolo test/classe
# bare-metal:
php artisan test
php artisan test --filter=ExplanationBuilderTest
```

---

## Build asset frontend

```bash
make npm-build   # build produzione
make npm-install # installa node_modules nel container
# bare-metal:
npm run build
```

---

## Misc

```bash
make artisan cmd="route:list"     # qualsiasi comando artisan
make composer cmd="require foo"   # qualsiasi comando composer
make cache-clear                  # php artisan optimize:clear
make tinker                       # Laravel REPL
```

---

## Permessi storage (bare-metal)

Se l'applicazione restituisce errori di scrittura:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## Troubleshooting

| Problema | Soluzione |
|---|---|
| `No application encryption key` | `php artisan key:generate` |
| Errore connessione Redis | Imposta `CACHE_STORE=file` nel `.env` |
| Pagine bianche / asset mancanti | `npm run build` oppure `make npm-build` |
| Cache vecchia | `php artisan optimize:clear` oppure `make cache-clear` |
| Queue non elabora job | Avvia `php artisan queue:work` o `make queue-work` |
| Scoring non produce risultati | Verifica che esistano titoli attivi: `php artisan tinker` → `App\Models\Security::where('is_active',true)->count()` |

---

## Disclaimer

Le informazioni fornite dalla piattaforma hanno esclusivamente scopo informativo e non costituiscono
consulenza finanziaria personalizzata. I dati algoritmici e i ranking non garantiscono rendimenti futuri
e non devono essere interpretati come raccomandazioni di acquisto o vendita di strumenti finanziari.
Consulta sempre un consulente finanziario abilitato prima di prendere decisioni di investimento.
