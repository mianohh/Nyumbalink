# Nyumbalink

Nyumbalink is a rental property management system for the Kenyan market (KES). It helps property managers track **houses**, **tenants**, **rental agreements**, and **payments**, and generate branded **PDF receipts**.

Built with **Laravel 13**, **Livewire 4**, **Tailwind CSS 4**, and **Flux UI**, backed by MySQL/MariaDB.

> This is a full rewrite of the original plain-PHP application. The legacy codebase is preserved on the [`main`](../../tree/main) branch.

---

## Features

- **Dashboard** — tenant/house counts, monthly & total revenue, revenue and house-status charts, recent payments, quick actions.
- **Houses** — CRUD, photo upload, search/filter/pagination, tenant assign & unassign (occupancy tracked automatically).
- **Tenants** — CRUD, optional house assignment on creation, detail view with payment history, agreements and a live monthly balance.
- **Rental agreements** — per-tenant agreements with auto-computed end dates; create and terminate from the tenant page.
- **Payments** — record payments (with a live balance preview), list with date-range/search filters, detail view, unique receipt numbers (`RCP-YYYYmmdd-XXX`).
- **PDF receipts** — branded TCPDF receipt with inline view and download.
- **Staff management** (admin-only) — administrators can add and remove staff accounts.
- **Auth** — session login with rate limiting, optional 2FA and passkeys, password reset. Public registration is disabled; staff accounts are created by admins.

### Security / behaviour improvements over the legacy app

- Role-based authorization: **deletes are admin-only** (enforced by policies).
- Login rate limiting (5/min) is actually wired up.
- Destructive actions use `DELETE`/POST — never GET.
- House occupancy is managed in one place (`HouseOccupancyService`) instead of being toggled in six different pages.
- HTML is escaped by Blade at render time (the legacy app stored escaped entities in the database).

---

## Requirements

- PHP **8.3+** (8.4 recommended) with `mbstring`, `pdo_mysql`, `xml`, `curl`, `zip`, `gd`, `intl`, `bcmath`, `sqlite3` (for tests)
- Composer 2
- Node.js 20+ and npm
- MySQL 8 / MariaDB 10.4+

---

## Local installation

```bash
git clone https://github.com/mianohh/Nyumbalink.git
cd Nyumbalink
git checkout laravel

cp .env.example .env
composer install
php artisan key:generate

# Create a database, then point .env at it (DB_DATABASE / DB_USERNAME / DB_PASSWORD)
php artisan migrate --seed

npm install
npm run build

php artisan serve
```

The seeder creates the first administrator from the `ADMIN_*` variables in `.env`:

| Field | Default |
|---|---|
| Email | `admin@nyumbalink.test` |
| Password | `password` |

**Change these before deploying anywhere real.**

Optional demo data:

```bash
php artisan db:seed --class=DemoSeeder
```

Serve uploaded house photos in development:

```bash
php artisan storage:link
```

---

## Testing & code style

```bash
php artisan test        # Pest feature suite (uses in-memory SQLite)
composer run lint       # Laravel Pint (auto-fix)
composer run lint:check # Pint, check only
```

---

## Project structure

```
app/
├── Enums/            # HouseStatus, AgreementStatus, UserRole
├── Http/
│   ├── Controllers/  # PaymentReceiptController (PDF)
│   └── Middleware/   # EnsureUserIsAdmin
├── Livewire/         # Full-page components (Dashboard, Houses/, Tenants/, Payments/, Staff/)
├── Models/           # House, Tenant, Payment, RentalAgreement, User
├── Policies/         # Role-aware authorization
├── Services/         # HouseOccupancyService, ReceiptPdfService
└── Support/          # format_currency / format_date helpers
database/
├── migrations/       # houses, tenants, payments, rental_agreements, users.role
├── factories/
└── seeders/          # DatabaseSeeder (admin), DemoSeeder (sample data)
resources/views/
├── layouts/          # Sidebar + auth layouts (Flux)
└── livewire/         # Component views per module
routes/web.php        # All application routes
```

### Legacy → Laravel route mapping

| Legacy URL | New URL |
|---|---|
| `modules/dashboard/index.php` | `/dashboard` |
| `modules/houses/index.php` | `/houses` |
| `modules/houses/create.php` | `/houses/create` |
| `modules/houses/view.php?id=3` | `/houses/3` |
| `modules/houses/edit.php?id=3` | `/houses/3/edit` |
| `modules/tenants/...` | `/tenants`, `/tenants/create`, `/tenants/{id}`, `/tenants/{id}/edit` |
| `modules/tenants/agreements.php?tenant_id=3` | Agreements live on `/tenants/3` |
| `modules/payments/index.php` | `/payments` |
| `modules/payments/create.php` | `/payments/create` |
| `modules/payments/view.php?id=1` | `/payments/1` |
| `modules/payments/receipt.php?id=1` | `/payments/1/receipt` (`?download=1` to download) |
| `modules/auth/register.php` | `/staff` + `/staff/create` (admin-only) |

---

## Deployment (Apache, shared hosting / subdirectory)

The document root must be the `public/` directory. Laravel ships a production-ready `public/.htaccess`; no root-level rewrite file is needed.

1. Push the `laravel` branch and pull it on the server (or upload everything **except** `node_modules/` and `.git/`).
2. Build assets locally (`npm run build`) and upload `public/build/` — shared hosts often lack Node.
3. On the server:

   ```bash
   composer install --no-dev --optimize-autoloader
   cp .env.example .env            # then edit it
   php artisan key:generate
   php artisan migrate --force
   php artisan storage:link
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```

4. Ensure `storage/` and `bootstrap/cache/` are writable by the web user.
5. **Subdirectory installs** (e.g. `https://example.com/nyumbalink`): set

   ```ini
   APP_URL=https://example.com/nyumbalink
   ```

   in `.env`. Asset and route URLs follow `APP_URL` automatically.
6. The server must run **PHP 8.3 or newer**. Point cPanel/panel PHP version accordingly.

### Production checklist

- [ ] Set a strong `ADMIN_PASSWORD` (or change it after first login)
- [ ] `APP_DEBUG=false` and `APP_ENV=production`
- [ ] HTTPS enabled
- [ ] Regular database backups

---

## License

MIT (see `composer.json`). The original project README referenced a separate commercial "premium" edition; this repository contains only the MIT community version.
