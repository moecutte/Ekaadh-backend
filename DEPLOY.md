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
7. Raise upload limit (invitation graphics are often 2–10MB). Coolify’s proxy defaults to ~1MB and returns **413 Request Entity Too Large**.

   **Traefik (Coolify default):** Application → **Settings** → **Custom Traefik / Labels** (or the domain’s middlewares) add:

   ```text
   traefik.http.middlewares.ekaadh-upload.buffering.maxRequestBodyBytes=20971520
   traefik.http.middlewares.ekaadh-upload.buffering.memRequestBodyBytes=20971520
   ```

   Attach that middleware to the HTTPS router (Coolify UI: Domain → Middlewares → `ekaadh-upload`), or paste both labels so they apply to this service.

   **Nginx proxy:** in the server proxy config add `client_max_body_size 20m;`

   Also set PHP env on the app:

   ```env
   PHP_UPLOAD_MAX_FILESIZE=20M
   PHP_POST_MAX_SIZE=20M
   ```

   Redeploy after changing labels/env. Until then, compress the PNG under ~1MB and save again.

## 2. Environment variables

Set these in Coolify (do **not** commit `.env`):

```env
APP_NAME=Ekaadh
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR_DOMAIN
APP_KEY=                    # generate once (see below)
# Boot fails if production has APP_DEBUG, WAAFIPAY_MODE=sandbox, CORS *, OTP_FIXED_CODE, or empty TICKET_QR_SECRET.

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

PAYMENT_GATEWAY=waafipay
WAAFIPAY_MODE=live
ADMIN_EMAIL=you@YOUR_DOMAIN
ADMIN_PASSWORD=           # min 8 chars; used only on first seed
ADMIN_PHONE=+2526...
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

# Meta WhatsApp Cloud API (optional until templates approved)
WHATSAPP_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_API_VERSION=v21.0
WHATSAPP_TIMEOUT=20
WHATSAPP_TEMPLATE_TICKET=ekaadh_ticket_ready
WHATSAPP_TEMPLATE_INVITE=ekaadh_invitation
WHATSAPP_TEMPLATE_LANG=en

# Telesom Prepaid SMS Gateway (OTP + ticket/invitation SMS)
TELESOM_BASE_URL=https://sms.mytelesom.com
TELESOM_SENDER_ID=
TELESOM_USERNAME=
TELESOM_PASSWORD=
TELESOM_SECRET_KEY=
TELESOM_CLIENT_REF=
TELESOM_TIMEOUT=20
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
| Demo admin / organizer / customer / staff (`password`) | `DatabaseSeeder` |
| Extra admin (only if `ADMIN_EMAIL` + `ADMIN_PASSWORD` are set) | `DatabaseSeeder` |
| Organizer packages (Free / Pro / Enterprise) | `OrganizerPackageSeeder` |
| Public + private categories | `CategorySeeder` |
| Invitation designs (Aroos, Meher, Xaflad, Casho) | `InvitationDesignSeeder` |
| Public sample events + SagalJet events | `EventSeeder` |
| Platform settings (fees, packages hidden on front, etc.) | `DatabaseSeeder` |

**Do not re-run `db:seed` on every deploy.** Later releases should only run:

```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Local/dev seed accounts (never use these in production):

| Role | Login | Password |
|------|-------|----------|
| Admin | `admin@ekaadh.com` | `password` |
| Staff | `staff@ekaadh.com` | `password` |
| Organizer | `organizer@ekaadh.com` | `password` |
| Customer | `customer@ekaadh.com` / `+252632345678` | `password` |

## 4. Cron (queue + schedule)

In Coolify, add a scheduled command **every minute**:

```bash
php artisan schedule:run
```

`routes/console.php` runs `queue:work --stop-when-empty` each minute so ticket emails / jobs process without a long-lived worker.

## 5. Flutter app

```bash
flutter build apk --dart-define=API_BASE_URL=https://YOUR_DOMAIN/api/v1
```

## 6. Support chat (built-in)

Customer support uses the Laravel FAQ + inbox (no third-party chat).

After deploy / migrate:

```bash
php artisan db:seed --class=SupportFaqSeeder
```

- **Web:** floating support widget on customer pages (FAQ tab, then chat).
- **Mobile:** Profile → Support (same FAQ + chat via API).
- **Admin:** Support → Inbox (reply) and FAQs (manage questions).

## 7. Push notifications (FCM)

Priorities wired in backend:

| Type | When |
|------|------|
| `support_reply` | Admin replies in support chat |
| `event_reminder` | ~24h before event (`events:send-reminders` hourly) |
| `invitation_received` | Private invitation sent (guest with matching app account) |
| `private_event_paid` | Host paid for private invitation capacity |
| `invite_send_failed` | SMS delivery failed for an invitation (notifies host) |

1. Create a Firebase project → enable Cloud Messaging → download a **service account** JSON.
2. Place it on the server (e.g. `storage/app/firebase-credentials.json`) — do not commit it.
3. Coolify env:

```env
FCM_ENABLED=true
FCM_PROJECT_ID=your-firebase-project-id
FCM_CREDENTIALS=/app/storage/app/firebase-credentials.json
```

4. Flutter: add `google-services.json` / `GoogleService-Info.plist`, then build with Firebase options (see `Ekaadh-mobile/docs/PUSH_SETUP.md`).
5. App registers device tokens via `POST /api/v1/auth/device-token` after login.

## 8. Post-deploy smoke checklist

- [ ] `GET /up` health OK
- [ ] Home / browse / event detail load
- [ ] Checkout WaafiPay (sandbox or live) succeeds
- [ ] Confirmation + `/t/{code}` ticket QR
- [ ] My Tickets with phone OTP
- [ ] Organizer login + dashboard
- [ ] Admin login + approvals + packages screen
- [ ] Staff API login + check-in
- [ ] Support widget FAQ + test message in admin inbox
- [ ] FCM: device token registered + test support reply push (when enabled)
- [ ] Production admin password is unique (not `password`)

## 9. Payment gateways

| Mode | Env | Behavior |
|------|-----|----------|
| Mock (MVP) | `PAYMENT_GATEWAY=mock` | Instant success |
| **WaafiPay (recommended live)** | `PAYMENT_GATEWAY=waafipay` + keys below | Charges Zaad / EVC / Sahal wallets via [Purchase API](https://docs.waafipay.com/purchase-api) |
| Zaad stub | `PAYMENT_GATEWAY=zaad` + `ZAAD_ENABLED=true` + keys | Direct Telesom API when available |
| eDahab stub | `PAYMENT_GATEWAY=edahab` + `EDAHAB_ENABLED=true` + keys | Direct Somtel API when available |

### WaafiPay setup

1. Set purchase credentials from WaafiPay merchant onboarding:

```env
PAYMENT_GATEWAY=waafipay
WAAFIPAY_MODE=sandbox

WAAFIPAY_SANDBOX_URL=https://sandbox.waafipay.com/asm
WAAFIPAY_SANDBOX_MERCHANT_UID=
WAAFIPAY_SANDBOX_API_USER_ID=
WAAFIPAY_SANDBOX_API_KEY=

WAAFIPAY_LIVE_URL=https://api.waafipay.net/asm
WAAFIPAY_LIVE_MERCHANT_UID=
WAAFIPAY_LIVE_API_USER_ID=
WAAFIPAY_LIVE_API_KEY=
WAAFIPAY_CURRENCY=USD
```

Keep both credential sets in `.env`. Switch with **`WAAFIPAY_MODE=sandbox`** or **`WAAFIPAY_MODE=live`** — do not edit PHP. After changing `.env`, run `php artisan config:clear` if config is cached.

Sandbox **does not accept live merchant keys** (`params.description`: Authentication failed). Fill `WAAFIPAY_SANDBOX_MERCHANT_UID` / `API_USER_ID` / `API_KEY` from the WaafiPay sandbox dashboard.

Test wallets ([quickstart](https://docs.waafipay.com/quickstart)) work **only** in sandbox. PIN is `1212`. On checkout enter the local part after `+252` (WaafiPay `accountNo` is sent as `252611111111`, no `+`):

| Wallet | Checkout number | PIN |
|--------|-----------------|-----|
| EVCPlus (Hormuud) | `611111111` | 1212 |
| ZAAD (Telesom) | `631111111` | 1212 |
| SAHAL (Golis) | `901111111` | 1212 |

Checkout still lets the buyer pick **WaafiPay** or **eDahab**; WaafiPay routes `MWALLET_ACCOUNT` from the phone number. Immediate `APPROVED` issues tickets in the same request. Timeouts and any non-approved response fail the order so the buyer can retry.

## 10. WhatsApp Cloud API

Outbound ticket and invitation delivery uses Meta Graph templates (no webhook in this phase).

1. In Meta Business Manager, create **Utility** templates whose bodies match parameter order:

| Env | Suggested name | Body |
|-----|----------------|------|
| `WHATSAPP_TEMPLATE_TICKET` | `ekaadh_ticket_ready` | `Your Ekaadh tickets for {{1}} ({{2}}) are ready. Open {{3}} to view them.` |
| `WHATSAPP_TEMPLATE_INVITE` | `ekaadh_invitation` | `Ekaadh: Hi {{1}}, you're invited to {{2}}. {{3}} ticket(s). Open {{4}} to view your invitation.` |

2. After approval, set `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, and the matching template name(s). Ticket send needs the ticket template; invite send needs the invite template. Sending stays **skipped** until token, phone number ID, and that template are set.
3. Paid public checkout → ticket template. Private invite send/resend → invite template. Capacity purchase does not send WhatsApp.
4. Webhook verify / delivery receipts are not implemented yet; sync API errors only update invitation `whatsapp_status` to `sent` / `failed` / `skipped`.

## 11. Telesom Prepaid SMS Gateway

OTP verification, ticket SMS, and invitation SMS use Telesom (`sms.mytelesom.com`).

1. Put the SenderID, username, password, HMAC secret, and registered `client_ref` from Telesom into Coolify env (`TELESOM_*` above).
2. Signature: `X-Auth-Key = Base64(HMAC-SHA256(SenderID + Timestamp + Username + Password))` with `TELESOM_SECRET_KEY` as the HMAC key (falls back to `TELESOM_PASSWORD` if the secret is empty). Timestamp is today's date in `Africa/Mogadishu` (`YYYY-MM-DD`).
3. This account is **standard prepaid SMS** until Telesom enables OTP prepaid. Confirmation codes currently go out on `/smsapi/v1/messages` as a normal text. Switch to `/smsotpapi/v1/messages` after the OTP product is active.
4. Leave `OTP_FIXED_CODE` empty in production so random 6-digit codes go out as SMS. Local/staging can keep a fixed code to skip live SMS.
5. Smoke test from the Coolify terminal:

```bash
php artisan telesom:test 063XXXXXXX
php artisan telesom:test 063XXXXXXX --otp
```

## Bluehost / shared hosting (legacy)

Shared hosting without Redis: use database drivers for session, cache, and queue (same as above). Point the document root at `public/`. Use cPanel cron every minute for `php artisan schedule:run`. Prefer Coolify/Nixpacks for new production hosts.
