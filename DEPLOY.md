# Ekaadh — production deploy (Coolify + Nixpacks)

Ekaadh is a Laravel (Blade + Livewire) + Flutter e-ticketing app. This guide targets **Coolify on Hetzner** with the **Nixpacks** build pack.

GitHub: `https://github.com/moecutte/Ekaadh-backend.git`

## Requirements

- Coolify on a Hetzner VPS
- Domain DNS A/AAAA pointing at the server
- MySQL 8 / MariaDB (Coolify database resource)
- PHP 8.3+ via Nixpacks (extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`)

## 1. Coolify application

1. New **Application** → connect `moecutte/Ekaadh-backend`.
2. Build pack: **Nixpacks** (leave Dockerfile unused).
3. Base directory: `/` (repo root is the Laravel app).
4. Attach a **MySQL** resource and link it (or paste DB env vars manually).
5. Persistent storage for uploads: mount a volume on `storage/app` (and keep `storage/logs` writable).
6. Set the domain + enable HTTPS (Let’s Encrypt).

## 2. Environment variables

Set these in Coolify (do **not** commit `.env`):

```env
APP_NAME=Ekaadh
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR_DOMAIN
APP_KEY=                    # generate once (see below)

DB_CONNECTION=mysql
DB_HOST=...                 # Coolify MySQL hostname
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=local

PAYMENT_GATEWAY=mock
DEFAULT_COMMISSION_RATE=10
SERVICE_FEE=1
TICKET_QR_SECRET=           # long random string; keep stable after tickets exist
CORS_ALLOWED_ORIGINS=https://YOUR_DOMAIN
SANCTUM_TOKEN_EXPIRATION=43200

MAIL_MAILER=log
# MAIL_MAILER=smtp
# MAIL_HOST=...
# MAIL_PORT=587
# MAIL_USERNAME=...
# MAIL_PASSWORD=...
# MAIL_FROM_ADDRESS=tickets@YOUR_DOMAIN
# MAIL_FROM_NAME=Ekaadh
```

Generate `APP_KEY` once (local or Coolify terminal):

```bash
php artisan key:generate --show
```

Paste the value into Coolify env. Rotating `APP_KEY` or `TICKET_QR_SECRET` invalidates existing ticket QR signatures.

## 3. First deploy — migrate + seed (once)

After the first successful Nixpacks deploy, open the Coolify **terminal** for the app (or a one-shot post-deploy command) and run:

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Or use the helper script (first deploy only — it includes seed):

```bash
bash scripts/prepare-production.sh
```

`db:seed` creates:

| Data | Source |
|------|--------|
| Admin / customer / staff users | `DatabaseSeeder` |
| Organizer packages (Free / Pro / Enterprise) | `OrganizerPackageSeeder` |
| Public + private categories | `CategorySeeder` |
| Invitation designs | `InvitationDesignSeeder` |
| Approved organizer + sample published events | `EventSeeder` |
| Platform settings (fees, packages hidden on front, etc.) | `DatabaseSeeder` |

**Do not re-run `db:seed` on every deploy.** Later releases should only run:

```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Seed accounts (change immediately on production)

| Role | Login | Password |
|------|-------|----------|
| Admin | `admin@ekaadh.com` | `password` |
| Staff | `staff@ekaadh.com` | `password` |
| Organizer | `organizer@ekaadh.com` | `password` |
| Customer | `customer@ekaadh.com` / `+252612345678` | `password` |

## 4. Cron (queue + schedule)

In Coolify, add a scheduled command **every minute**:

```bash
php artisan schedule:run
```

`routes/console.php` runs `queue:work --stop-when-empty` each minute so ticket emails / jobs process without a long-lived worker.

## 5. Flutter app

Point the mobile API at production:

```bash
flutter build apk --dart-define=API_BASE_URL=https://YOUR_DOMAIN/api/v1
```

## 6. Post-deploy smoke checklist

- [ ] `GET /up` health OK
- [ ] Home / browse / event detail load (seeded events + cover images under `public/images/events`)
- [ ] Checkout mock payment succeeds
- [ ] Confirmation + `/t/{code}` ticket QR
- [ ] My Tickets with phone + order number
- [ ] Organizer login + dashboard
- [ ] Admin login + approvals + packages screen
- [ ] Staff API login + check-in
- [ ] Seeded passwords changed

## 7. Payment gateways

| Mode | Env | Behavior |
|------|-----|----------|
| Mock (MVP) | `PAYMENT_GATEWAY=mock` | Instant success |
| Zaad stub | `PAYMENT_GATEWAY=zaad` + `ZAAD_ENABLED=true` + keys | Ready when merchant API is plugged in |
| eDahab stub | `PAYMENT_GATEWAY=edahab` + `EDAHAB_ENABLED=true` + keys | Ready when merchant API is plugged in |

## Bluehost / shared hosting (legacy)

Shared hosting without Redis: use database drivers for session, cache, and queue (same as above). Point the document root at `public/`. Use cPanel cron every minute for `php artisan schedule:run`. Prefer Coolify/Nixpacks for new production hosts.
