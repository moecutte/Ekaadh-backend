# Ekaadh — Bluehost deploy guide

Ekaadh is a Laravel (Blade + Livewire) + Flutter e-ticketing app. Bluehost **shared hosting** has no Redis — use database drivers for session, cache, and queue.

## Requirements

- PHP 8.2+ with extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`
- MySQL 8 / MariaDB
- Composer (local build, then upload — or SSH Composer if available)
- Cron access

## 1. Prepare the release locally

```bash
cd Ekaadh-backend
composer install --no-dev --optimize-autoloader
php artisan config:clear
# Do NOT commit .env
```

Upload the project (FTP/SFTP or Git) **excluding** `.env`, `node_modules`, and local `storage/logs`.

## 2. Document root

Point the domain (or subdomain) document root to:

```text
/path/to/Ekaadh-backend/public
```

Keep `vendor/`, `.env`, `storage/`, and `app/` **outside** the public web root (standard Laravel layout).

## 3. Production `.env`

Copy `.env.example` → `.env` on the server and set:

```env
APP_NAME=Ekaadh
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_KEY=base64:...   # php artisan key:generate --show  (once)

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
CACHE_STORE=database

PAYMENT_GATEWAY=mock
# When Zaad/eDahab credentials arrive:
# PAYMENT_GATEWAY=zaad   (or edahab)
# ZAAD_ENABLED=true
# ZAAD_MERCHANT_ID=...
# ZAAD_API_KEY=...

TICKET_QR_SECRET=long-random-string-here
CORS_ALLOWED_ORIGINS=https://your-domain.com
SANCTUM_TOKEN_EXPIRATION=43200

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=tickets@your-domain.com
MAIL_FROM_NAME=Ekaadh
```

Generate key if empty:

```bash
php artisan key:generate
```

> Rotating `APP_KEY` or `TICKET_QR_SECRET` invalidates existing ticket QR signatures — issue new tickets or keep the old secret.

## 4. Database & caches

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`db:seed` creates admin/demo users, settings, private categories (Aroos / Meher / Xaflad / Casho), built-in invitation designs, and sample public events. Change seeded passwords immediately on production.

Ensure `storage/` and `bootstrap/cache/` are writable by the web user (`755`/`775` as required by Bluehost).

## 5. Cron (queue + schedule)

In cPanel → Cron Jobs, every minute:

```bash
cd /home/USER/path/to/Ekaadh-backend && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

`routes/console.php` runs `queue:work --stop-when-empty` each minute so ticket emails / jobs process without a long-lived daemon.

## 6. SSL

Enable HTTPS in Bluehost and force HTTPS (cPanel “Force HTTPS Redirect” or `.htaccess` in `public/`). The app trusts proxies for TLS termination.

## 7. Flutter app

Point the mobile API base URL at production:

```bash
flutter build apk --dart-define=API_BASE_URL=https://your-domain.com/api/v1
```

Android emulator local default remains `http://10.0.2.2:8000/api/v1`.

## 8. Post-deploy smoke checklist

- [ ] `GET /up` health OK  
- [ ] Home / browse / event detail load  
- [ ] Checkout mock payment succeeds (local `force_fail` is **disabled** in production)  
- [ ] Confirmation + `/t/{code}` ticket QR  
- [ ] My Tickets with **phone + order number**  
- [ ] Organizer login + dashboard  
- [ ] Admin login + approvals  
- [ ] Staff API login + check-in (`POST /api/v1/staff/check-in`)  
- [ ] Rate limit: rapid login attempts return 429  

## 9. Payment gateways

| Mode | Env | Behavior |
|------|-----|----------|
| Mock (MVP) | `PAYMENT_GATEWAY=mock` | Instant success; `force_fail` only when `APP_ENV=local` + `APP_DEBUG=true` |
| Zaad stub | `PAYMENT_GATEWAY=zaad` + `ZAAD_ENABLED=true` + keys | Ready to plug merchant API |
| eDahab stub | `PAYMENT_GATEWAY=edahab` + `EDAHAB_ENABLED=true` + keys | Ready to plug merchant API |

Both web and Flutter use the same checkout API — swapping the gateway does not require an app rebuild.

## 10. Rollback

1. Restore previous release folder / Git tag  
2. Restore DB dump if migrations changed  
3. `php artisan config:cache`  
4. Re-run smoke checklist  

## Seed accounts (local / staging only)

| Role | Login | Password |
|------|-------|----------|
| Customer | `customer@ekaadh.com` / `+252612345678` | `password` |
| Admin | `admin@ekaadh.com` | `password` |
| Staff | `staff@ekaadh.com` | `password` |
| Organizer | `organizer@ekaadh.com` | `password` |

**Never** leave these passwords on production — change or disable after first deploy.
