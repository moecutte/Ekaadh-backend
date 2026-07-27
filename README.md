# Ekaadh Backend

Laravel API + Blade web app for the **Ekaadh** event e-ticketing marketplace.

## Stack

- Laravel 13, Sanctum, Livewire
- MySQL — session / cache / queue = **database** (Bluehost-friendly, no Redis)
- Payments: `MockGateway` (MVP) with Zaad / eDahab stubs ready to wire

## Local setup

```bash
cp .env.example .env
# set DB_*, run: php artisan key:generate
composer install
php artisan migrate --seed
php artisan serve
```

App: [http://127.0.0.1:8000](http://127.0.0.1:8000)  
API: [http://127.0.0.1:8000/api/v1/health](http://127.0.0.1:8000/api/v1/health)

## Portals

| Surface | URL |
|---------|-----|
| Customer web | `/` |
| Organizer | `/organizer/login` |
| Admin | `/admin/login` |
| Staff check-in | Flutter app → Staff portal (API) |

## Deploy

See **[DEPLOY.md](DEPLOY.md)** for Bluehost shared hosting (document root, cron, SSL, env, smoke tests).

## Security notes (Milestone 8)

- Rate limits on auth, checkout, tickets, and staff check-in
- `force_fail` payment simulation only in local debug
- Ticket lookup requires phone **and** order number
- Sanctum tokens expire (default 30 days)
- Optional `TICKET_QR_SECRET` for QR HMAC
- Set `CORS_ALLOWED_ORIGINS` and `APP_DEBUG=false` in production
