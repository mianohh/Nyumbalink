# Nyumbalink

[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat&logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat&logo=laravel&logoColor=white)](https://laravel.com)
[![Livewire](https://img.shields.io/badge/Livewire-4-FB70A9?style=flat&logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](composer.json)

**Rental property management for the Kenyan market (KES).**

Nyumbalink helps property managers track **houses**, **tenants**, **rental agreements** and **payments**, and generate branded **PDF receipts**.

This repository's default branch is **`laravel`** — it is the official codebase, a full rewrite on **Laravel 13 + Livewire 4**. The original plain-PHP application is archived on the [`main`](../../tree/main) branch for historical reference only; do not open feature work against it.

---

## Tech stack

| Layer | Technology |
| --- | --- |
| Backend | Laravel 13 (PHP `^8.3`, 8.4 recommended) |
| UI | Livewire 4, Flux UI, Tailwind CSS 4 |
| Assets | Vite 8 via [vite-plus](https://viteplus.dev/guide) (`vp dev` / `vp build`) |
| Database | MySQL 8 / MariaDB 10.4+ (in-memory SQLite for tests) |
| Auth | Laravel Fortify — session login, rate limiting, optional 2FA and passkeys |
| Queue / cache / sessions | Database-backed (`QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `SESSION_DRIVER=database`) |
| PDF | TCPDF (`ReceiptPdfService`) |
| Charts | Chart.js |
| Mail (local) | Mailpit (SMTP `localhost:1025`, dashboard `http://localhost:8025`) |
| Dev environment | Docker Compose through [Laravel Sail](https://laravel.com/docs/sail) |
| Tests | Pest 5 |
| Style / static analysis | Laravel Pint (`laravel` preset), Larastan level 7 |

---

## Features (domain map)

- **Dashboard** — tenant/house counts, monthly & total revenue, revenue and house-status charts, recent payments, quick actions.
- **Houses** — CRUD, photo upload, search/filter/pagination, tenant assign & unassign (occupancy tracked automatically).
- **Tenants** — CRUD, optional house assignment on creation, detail view with payment history, agreements and a live monthly balance.
- **Rental agreements** — per-tenant agreements with auto-computed end dates; create and terminate from the tenant page.
- **Payments** — record payments (with a live balance preview), list with date-range/search filters, detail view, unique receipt numbers (`RCP-YYYYmmdd-XXX`).
- **PDF receipts** — branded TCPDF receipt with inline view and download.
- **Staff management** (admin-only) — administrators can add and remove staff accounts.
- **Auth** — session login with rate limiting, optional 2FA and passkeys, password reset. Public registration is disabled; staff accounts are created by admins.

---

## Requirements

- **Docker** (Engine + Compose v2) — the only host requirement; PHP, Composer and Node run inside the containers.
- To work *outside* Docker instead: PHP **8.3+** (8.4 recommended) with `mbstring`, `pdo_mysql`, `xml`, `curl`, `zip`, `gd`, `intl`, `bcmath`, `sqlite3` (for tests), Composer 2, Node.js 20+, MySQL 8 / MariaDB 10.4+.

---

## Getting started

Local development runs entirely in Docker via Laravel Sail's Compose stack — plain `docker compose` commands, no wrapper needed.

**Prerequisites:** Docker Engine running and your user in the `docker` group
(`sudo usermod -aG docker $USER`, then log out/in). Verify with `docker info`.

```bash
git clone https://github.com/mianohh/Nyumbalink.git
cd Nyumbalink

cp .env.example .env

# One-off bootstrap: install PHP dependencies so the compose build can find
# vendor/laravel/sail/runtimes/8.4 (no host PHP required)
docker run --rm -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

docker compose up -d                                      # build app image; start MySQL, Mailpit, queue worker
docker compose exec -u sail laravel.test composer setup   # key:generate, migrate, npm install, npm run build
docker compose exec -u sail laravel.test php artisan db:seed   # first administrator (ADMIN_* vars in .env)
docker compose exec -u sail laravel.test php artisan storage:link

docker compose exec -u sail laravel.test npm run dev      # second terminal — Vite dev server with HMR
```

The application is served at **http://localhost:8000** (`APP_PORT=8000`).

The seeder creates the first administrator from the `ADMIN_*` variables in `.env`:

| Field | Default |
| --- | --- |
| Email | `admin@nyumbalink.com` |
| Password | `password` |

Optional demo data:

```bash
docker compose exec -u sail laravel.test php artisan db:seed --class=DemoSeeder
```

### Ports

| Service | Host port | Notes |
| --- | --- | --- |
| Application | `8000` | `APP_PORT` |
| Vite dev server | `5173` | `VITE_PORT` |
| MySQL | `3307` | `FORWARD_DB_PORT` — avoids clashing with a local MySQL on 3306 |
| Mailpit SMTP / dashboard | `1025` / `8025` | captures outbound mail locally |

### Everyday commands

Run all commands from the project root. On the app container, always pass `-u sail`
so files created in the bind mount stay owned by your host user.

| Task | Command |
| --- | --- |
| Start containers | `docker compose up -d` |
| Rebuild the app image | `docker compose up -d --build` |
| Stop containers | `docker compose stop` |
| Stop and delete containers + DB volume | `docker compose down -v` |
| Container status | `docker compose ps` |
| Artisan | `docker compose exec -u sail laravel.test php artisan migrate` |
| Composer | `docker compose exec -u sail laravel.test composer install` |
| Tests (in-memory SQLite) | `docker compose exec -u sail laravel.test php artisan test` |
| Code style (auto-fix) | `docker compose exec -u sail laravel.test composer lint` |
| Static analysis (Larastan) | `docker compose exec -u sail laravel.test composer types:check` |
| Vite dev server (HMR) | `docker compose exec -u sail laravel.test npm run dev` |
| Build assets | `docker compose exec -u sail laravel.test npm run build` |
| App shell | `docker compose exec -u sail laravel.test bash` |
| MySQL shell | `docker compose exec mysql sh -c 'MYSQL_PWD=$MYSQL_PASSWORD mysql -u$MYSQL_USER $MYSQL_DATABASE'` |
| All logs | `docker compose logs -f` |
| Queue worker logs | `docker compose logs -f queue-worker` |
| Application logs | `docker compose exec -u sail laravel.test php artisan pail` |

Notes:

- `.env` uses `DB_HOST=mysql`, so PHP must run inside the containers — plain `php artisan` on the host cannot reach the database.
- The **queue worker** runs automatically in its own container (`queue-worker` service).
- House photos are served through the `storage` symlink created above.

### Importing an existing local database (optional)

```bash
mysqldump -u nyumbalink -p nyumbalink > /tmp/nyumbalink.sql
docker compose exec -T mysql sh -c 'MYSQL_PWD=$MYSQL_PASSWORD mysql -u$MYSQL_USER $MYSQL_DATABASE' < /tmp/nyumbalink.sql
```

Or start clean: `docker compose exec -u sail laravel.test php artisan migrate --seed`.

---

## Testing, style and static analysis

```bash
docker compose exec -u sail laravel.test php artisan test          # Pest feature suite (in-memory SQLite)
docker compose exec -u sail laravel.test composer lint             # Laravel Pint (auto-fix)
docker compose exec -u sail laravel.test composer lint:check       # Pint, check only
docker compose exec -u sail laravel.test composer types:check      # Larastan (level 7)
docker compose exec -u sail laravel.test composer test             # everything CI runs: Pint + Larastan + tests
```

- Tests use in-memory SQLite and never touch the Docker MySQL database, so they also run directly on the host if PHP 8.3+ is installed.
- CI (`.github/workflows/tests.yml`) runs `composer setup` and `composer ci:check` on every push and pull request.

---

## Project structure

```
app/
├── Actions/            # Fortify actions (login, registration, password, 2FA)
├── Concerns/           # Shared validation traits (password/profile rules)
├── Enums/              # HouseStatus, AgreementStatus, UserRole
├── Http/
│   ├── Controllers/    # PaymentReceiptController (PDF)
│   └── Middleware/     # EnsureUserIsAdmin
├── Livewire/           # Full-page components (Dashboard, Houses/, Tenants/, Payments/, Staff/, Settings/)
├── Models/             # House, Tenant, Payment, RentalAgreement, User
├── Policies/           # Role-aware authorization
├── Providers/          # AppServiceProvider, FortifyServiceProvider
├── Services/           # HouseOccupancyService, ReceiptPdfService
└── Support/            # format_currency / format_date helpers
config/                 # incl. nyumbalink.php (app-specific settings)
database/
├── migrations/         # houses, tenants, payments, rental_agreements, users.role, passkeys, 2FA
├── factories/
└── seeders/            # DatabaseSeeder (admin), DemoSeeder (sample data)
resources/views/
├── flux/               # Flux UI overrides
├── layouts/            # Sidebar + auth layouts (Flux)
└── livewire/           # Component views per module
routes/
├── web.php             # Application routes
└── settings.php        # Profile / security settings routes
tests/Feature/          # Pest suites (Auth, Dashboard, Houses, Tenants, Staff, Settings)
docker-compose.yml      # app, queue-worker, mysql, mailpit
```

---

## Notes for contributors

Behavioural invariants to preserve when changing code:

- **Deletes are admin-only** — enforced through policies, not UI checks.
- **Login rate limiting** (5/min) is wired through Fortify; keep it enabled.
- **Destructive actions use `DELETE`/POST** — never GET.
- **House occupancy is managed in one place** (`HouseOccupancyService`) — do not toggle occupancy inline in pages.
- **HTML is escaped at render time by Blade** — never store pre-escaped output.
- **Receipt numbers are unique** (`RCP-YYYYmmdd-XXX`) — generated in the payment flow.
- Route groups live in `routes/web.php` and `routes/settings.php`; admin-only areas go through the `EnsureUserIsAdmin` middleware.

---

## Contributing

1. Branch from `laravel` (the default branch): `git checkout -b feature/amazing-feature`.
2. Follow the existing code style — Pint is authoritative (`docker compose exec -u sail laravel.test composer lint`).
3. Add Pest feature tests for new behaviour.
4. Run the full check suite before opening a PR: `docker compose exec -u sail laravel.test composer test`.
5. Open a pull request against `laravel`.

---

## License

MIT (see `composer.json`).
