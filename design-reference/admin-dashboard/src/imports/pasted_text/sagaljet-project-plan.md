# Sagaljet E-Ticketing Platform — Project Plan

**Client:** Sagaljet (Advertisement Company, Somaliland)
**Goal:** Online events & e-ticketing platform, multi-organizer marketplace, Zaad & eDahab payments
**Stack:** Laravel + MySQL
**Team size:** 2-4 people
**MVP Target:** 1-2 weeks

---

## 1. Project Overview

Sagaljet will run a website where:
- Visitors browse posted events (concerts, conferences, shows, etc.)
- Visitors buy e-tickets and pay online via **Zaad** and **eDahab**
- Independent **organizers** can register, get approved, and post their own events
- Sagaljet earns a **% commission on every ticket sold** by other organizers
- Sagaljet admins manage the whole platform (users, organizers, events, payments, payouts)

---

## 2. Team Roles (2-4 people)

| Role | Responsibility |
|---|---|
| **Backend Lead (Laravel)** | Auth, database, APIs, payment integration, admin panel logic |
| **Frontend Dev (Blade/Livewire or Vue + Laravel)** | Public site, organizer dashboard, checkout flow, styling |
| **Full-stack / QA** | Ticket/order logic, testing, bug fixing, deployment |
| **Flutter Dev** (dual-hat with one of the above) | Native mobile app (customer browse/buy + staff QR scanner), consuming the same Laravel API |
| **PM / Designer** (can be doubled up with another role if only 3 people) | UI/UX, content, coordinating with Zaad/eDahab, client updates |

If team is only 3 people: Backend Lead + Frontend Dev + Full-stack/QA, with one of them also handling light design/PM duties.

> **Reality check on timeline:** Building a full web platform (with payments, organizer marketplace, admin panel) **and** a native Flutter app **in the same 1-2 weeks**, with the Flutter work handled by someone already on the 3-4 person team, is very tight. It's achievable if the app's scope stays limited to customer browsing/buying + staff QR scanner (as scoped below) and the API is built API-first so both web and app consume the same endpoints from day one. If the team hits capacity issues mid-sprint, the recommended cut is: **launch web on time, ship the Flutter app 3-5 days later** rather than delaying the whole platform.

---

## 3. Figma Design Phase

**Scope:** Full UI/UX design in Figma covering all three surfaces before/alongside build:
1. **Public web app** — homepage, event listing, event detail, checkout, order confirmation, "My Tickets"
2. **Organizer & Admin dashboard (web backend)** — organizer registration, organizer dashboard (create event, sales, earnings), admin panel (approve organizers/events, orders, payouts, commission settings)
3. **Flutter mobile app** — browse/event detail, checkout, "My Tickets" with QR, staff QR scanner screens

**Approach:** Design runs **in parallel** with Days 1-3 of the build. To keep development unblocked, designs should be finished screen-by-screen in priority order, so BE/FE/MOB can start building against each screen as soon as it's ready rather than waiting for the entire Figma file to be complete:

| Priority | Screens | Needed by |
|---|---|---|
| 1 (Day 1) | Homepage, event listing, event detail | Day 1-2, so FE/MOB can start immediately |
| 2 (Day 1-2) | Checkout, order confirmation, ticket/QR display | Day 3, before checkout build begins |
| 3 (Day 2) | Organizer registration + organizer dashboard | Day 4, before organizer features begin |
| 4 (Day 2-3) | Admin panel (approvals, orders, payouts, commission settings) | Day 9, when admin panel work starts |
| 5 (Day 3) | Mobile-specific: "My Tickets" screen, staff QR scanner (Admit/Already Used/Invalid states) | Day 8-9, when those app screens are built |

**Deliverables:**
- A shared design system in Figma (colors, typography, buttons, form fields, spacing) applied consistently across web, dashboard, and app
- Clickable/organized Figma frames per screen above, handed to FE/MOB as they're completed
- Sagaljet branding (logo, color scheme) reflected throughout — confirm this with Sagaljet before finalizing Priority 1 screens, since it affects everything downstream

---

## 4. Tech Stack

- **Backend:** Laravel 11 (PHP 8.3)
- **Database:** MySQL 8
- **Frontend (web):** Laravel Blade + Livewire (fastest for a 1-2 week MVP) — or Vue/Inertia if team is more comfortable with Vue
- **Mobile app:** Flutter (Android + iOS from one codebase), consuming the **same Laravel backend** via a REST API
- **API auth for mobile:** Laravel Sanctum (token-based auth for the Flutter app, separate from the web's session-based auth)
- **Auth:** Laravel Breeze/Jetstream (roles: Admin, Organizer, Customer)
- **Payments:** Zaad API + eDahab API (via Laravel HTTP client, webhook callbacks)
- **Queue/Jobs:** Laravel Queue (for sending SMS/email confirmations, payment status polling)
- **Notifications:** SMS (local gateway) + Email for ticket delivery
- **Hosting:** VPS (e.g., DigitalOcean/Linode) or local Somaliland hosting provider with good uptime; Nginx + PHP-FPM + MySQL
- **PDF Tickets:** `barryvdh/laravel-dompdf` or `simple-qrcode` for QR-coded e-tickets
- **File storage:** Local disk for MVP (event images), move to S3-compatible storage later if needed

---

## 5. Database Schema (Core Tables)

**users** — id, name, email, phone, password, role (admin/organizer/customer), status

**organizer_profiles** — user_id, business_name, business_phone, commission_rate, approval_status (pending/approved/rejected), documents (optional), approved_by, approved_at

**events** — id, organizer_id, title, slug, description, category, venue, address, event_date, event_time, cover_image, status (draft/pending_review/published/completed/cancelled)

**ticket_types** — id, event_id, name (e.g. VIP, Regular), price, quantity_available, quantity_sold, max_per_order

**orders** — id, user_id (nullable if guest checkout), event_id, order_number, total_amount, commission_amount, status (pending/paid/failed/refunded), payment_method (zaad/edahab), payment_reference

**order_items** — id, order_id, ticket_type_id, quantity, unit_price, subtotal

**tickets** — id, order_item_id, ticket_code (unique/QR), status (valid/used/cancelled), checked_in_at

**payments** — id, order_id, provider (zaad/edahab), transaction_id, phone_number, amount, status (initiated/success/failed), raw_response, created_at

**payouts** — id, organizer_id, period_start, period_end, gross_sales, commission_deducted, net_payout, status (pending/paid)

**settings** — key, value (e.g. default_commission_rate, platform_name)

---

## 6. MVP Feature Scope (Week 1-2)

### Public site (Customers)
1. Homepage — list of upcoming events (search/filter by category, date, location)
2. Event detail page — description, date, venue, ticket types & prices, images
3. Ticket purchase flow — select ticket type & quantity → checkout → pay via Zaad or eDahab
4. Payment confirmation — order status page, e-ticket with QR code sent via SMS/email
5. Guest checkout (no login required to buy) + optional account for order history

### Organizer side
1. Organizer registration (business name, phone, email)
2. Admin approval step before organizer can post events
3. Organizer dashboard — create/edit event, add ticket types & prices, view sales, view earnings after commission
4. Organizer sees pending payout balance

### Admin panel
1. Approve/reject organizer applications
2. Approve/reject/feature events (moderation before going live — optional toggle)
3. View all orders, payments, and platform-wide sales
4. Set commission rate (global default + per-organizer override)
5. Manage payouts to organizers (mark as paid, record manually first — automation later)

### Payments
1. Zaad checkout integration (STK-style push or redirect, per Zaad's API docs)
2. eDahab checkout integration
3. Webhook/callback handling to confirm payment and auto-generate ticket
4. **Sandbox/mock payment mode** — since Zaad/eDahab API access is still pending approval, build the payment layer behind an interface so we can develop and test with a mock provider now, and swap in real credentials the moment they arrive (no code rewrite needed).

---

## 7. Phase 2 (Post-MVP, after launch)

- Automated payout scheduling to organizers
- SMS OTP verification for accounts
- Event categories/tags & advanced search
- Native check-in mobile app (MVP uses a browser-based scanner; a dedicated offline-capable app can come later)
- Discount codes / early-bird pricing
- Analytics dashboard for organizers (sales over time, conversion)
- Refund/cancellation workflow
- Multi-language (Somali/English/Arabic)
- Reviews/ratings for events

---

## 8. How the Ticket Works (Format, Delivery, Check-in)

### Ticket format
- **One ticket per person** — if someone buys 3 tickets, the system generates **3 separate tickets**, each with its own unique QR code and ticket number (matches the `tickets` table design in section 4, where each `order_item` can produce multiple `tickets` rows).
- Each ticket is rendered as a **PDF or image** containing:
  - Event name, date, time, venue
  - Ticket type (e.g. VIP, Regular) and price paid
  - Buyer name/phone, order number
  - A unique **QR code** — encodes a signed/unique ticket token (not just a plain sequential number, to prevent guessing/forgery)
  - Ticket status watermark once used (optional, shown when re-viewed after check-in)

### Delivery (after successful payment)
1. **WhatsApp** — PDF/image with the QR code sent directly to the buyer's WhatsApp number
2. **Email** — same PDF attached to a confirmation email
3. **SMS** — short message with a secure link to a "View My Ticket" web page (in case WhatsApp/email delivery is delayed or the buyer prefers viewing it online)
4. All tickets also remain accessible anytime on the buyer's **"My Tickets"** page if they created an account

**Note on WhatsApp delivery:** Sending PDFs/images via WhatsApp requires the **WhatsApp Business Cloud API** (via Meta directly or a provider like Twilio/360dialog/Gupshup). This has its own approval process, similar to Zaad/eDahab. Recommended approach:
- Apply for WhatsApp Business API access in parallel, starting immediately
- Build ticket delivery the same way as payments — behind a simple `TicketDeliveryService` with channels (`whatsapp`, `email`, `sms`) so WhatsApp can be toggled on the moment approval comes through
- Until WhatsApp is approved, **email + SMS link** delivery is fully sufficient to launch on schedule — nothing blocks the MVP launch

### Check-in at the entrance (QR scanning)
- A lightweight **web-based scanner page** (works in any phone browser's camera, no separate app needed — using a library like `html5-qrcode`) that event staff open on their phone
- Staff scans a guest's QR code → system checks the ticket's status:
  - ✅ Valid & unused → marks as **used**, shows green "Admit" screen with ticket type/name
  - ❌ Already used → red "Already Checked In" warning (prevents duplicate entry/ticket sharing)
  - ❌ Invalid/forged code → red "Invalid Ticket" warning
- Works offline-tolerant where possible, but since most check-ins will have phone data/wifi at the venue, a simple online scanner is enough for MVP
- This scanner is now included in the **MVP** (moved up from Phase 2, since it's essential for any live event) — see updated daily plan below

---

## 9. Flutter Mobile App

**Scope (as decided):** Customer-facing app (browse events + buy tickets) + Staff QR Scanner. The organizer dashboard and admin panel stay **web-only** for now (organizers/admins can still use the web app from their phone browser — it'll be responsive).

### Why this works with one shared backend
The Laravel app becomes **API-first**: every action the web app does (list events, view event, checkout, initiate payment, view my tickets, scan/check-in) is exposed as a JSON REST API endpoint. Both the Blade/Livewire web frontend *and* the Flutter app call the same endpoints — no duplicate business logic, no duplicate payment integration, no duplicate commission math. This is why it's realistic to add Flutter without doubling total work.

### Flutter app features (MVP)
1. Browse events (list + search/filter), event detail screen
2. Select ticket type & quantity, checkout (Zaad/eDahab payment, same mock-gateway strategy as web)
3. View "My Tickets" — list of purchased tickets with QR codes, downloadable/shareable
4. Push/local notification on successful payment (optional nice-to-have, else rely on email/SMS/WhatsApp)
5. **Staff QR Scanner mode** — a role-restricted screen (staff login) using the phone's camera to scan tickets, calling the same check-in API used by the web scanner, with Admit / Already Used / Invalid feedback

### Distribution
- **Android:** APK/AAB uploaded to Google Play Store (or shared as a direct APK download initially to skip store review delay)
- **iOS:** requires an Apple Developer account; App Store review can take a few days — if the 1-2 week deadline is firm, consider launching **Android first** (majority of the local market) and submitting iOS in parallel, going live a few days later once Apple approves

---

## 9B. Payment Integration Notes (Zaad & eDahab)

Since API access is **pending approval**, here's how the team should proceed without losing time:

1. **Build a `PaymentGatewayInterface`** in Laravel with a common contract: `initiate()`, `verifyStatus()`, `handleCallback()`.
2. Build a **MockGateway** implementing this interface — simulates success/failure so checkout flow, ticket generation, and order status can be fully built and tested now.
3. Once Zaad/eDahab credentials arrive, implement `ZaadGateway` and `EdahabGateway` classes using the same interface — this is usually a 1-2 day task since the checkout UI and order logic are already done.
4. Get the exact API docs from Zaad (Telesom) and eDahab (Somtel/Dahabshiil) as early as possible — request sandbox/test credentials in parallel with production approval, if available.
5. Confirm with both providers: request flow (push/USSD prompt vs redirect), webhook/callback URL requirements, and settlement/payout timing — this affects UX copy on the checkout page.

---

## 10. 14-Day Daily Plan (MVP Sprint — Web + Flutter App)

**Assumption:** Team of 3-4 — Backend Lead (BE), Frontend Dev (FE), Full-stack/QA (QA), PM/Designer (PM), plus **Mobile (MOB)** tasks handled by whichever team member is doing double-duty as the Flutter dev. The backend is built **API-first** so BE's work serves both FE (web) and MOB (app) at the same time. **Figma design (DES)** runs in parallel during Days 1-3, feeding screens to FE/MOB as each is finished (see Section 3 for the priority order).

### Week 1 — Foundation & Core Build

**Day 1 (Mon)**
- PM/DES: Design system in Figma (colors, typography, components) + Priority 1 screens: homepage, event listing, event detail — confirm branding with Sagaljet same day so nothing downstream is blocked
- BE: Laravel project setup, Git repo, database design finalized, migrations for users/events/tickets
- FE: Set up frontend structure (Blade/Livewire components), install Tailwind, apply design tokens as soon as DES delivers them
- MOB: Set up Flutter project, app navigation shell, apply the same branding/colors as web

**Day 2 (Tue)**
- PM/DES: Priority 2 screens (checkout, order confirmation, ticket/QR display) + Priority 3 screens (organizer registration & dashboard)
- BE: Auth system (Breeze/Jetstream for web) + Sanctum token auth (for mobile API)
- FE: Homepage layout with event listing, now built against the finished Figma screens
- MOB: Login/register screens wired to the Sanctum auth API

**Day 3 (Wed)**
- PM/DES: Priority 4 screens (admin panel — approvals, orders, payouts, commission settings) + Priority 5 mobile-specific screens ("My Tickets", staff QR scanner states)
- BE: Event CRUD + **JSON API endpoints** for events (list, detail) so both web and app can consume them
- FE: Event detail page UI, ticket type selection UI, built from the completed Figma designs
- MOB: Event list & event detail screens, connected to the live events API
- QA: Start writing test cases for core flows (register, create event, checkout)

**Day 4 (Thu)**
- BE: Organizer registration + admin approval workflow
- FE: Organizer registration form + dashboard shell (sidebar, nav)
- MOB: Ticket type selection & quantity/cart screen in the app
- PM: Draft commission rate policy, get sign-off from Sagaljet on default %

**Day 5 (Fri)**
- BE: Orders & order_items logic, cart/checkout backend logic + checkout API endpoint for mobile
- FE: Checkout page UI (ticket qty selector, order summary, payment method choice)
- MOB: Checkout screen in the app, calling the same checkout API
- QA: Test event creation + organizer approval end-to-end

**Day 6 (Sat)**
- BE: Build `PaymentGatewayInterface` + `MockGateway`, wire into both web and mobile checkout APIs
- FE: Order confirmation page + e-ticket display (QR placeholder)
- MOB: Payment flow integration (mock gateway) + order confirmation screen in-app
- QA: Test full guest checkout with mock payment (pending → paid) on both web and app

**Day 7 (Sun)**
- Team: Buffer/catch-up day, fix bugs from week 1, internal demo/review with PM
- PM: Collect feedback, adjust backlog for week 2

### Week 2 — Payments, Admin, Polish, Launch Prep

**Day 8 (Mon)**
- BE: Generate one QR-coded PDF/image ticket **per person**, unique signed token per ticket
- BE: Build `TicketDeliveryService` — send via Email (PDF attached) + SMS (secure "View My Ticket" link) now; WhatsApp channel stubbed in, to activate once WhatsApp Business API is approved
- BE: "My Tickets" API endpoint (returns a user's tickets + QR data)
- FE: Organizer dashboard — event list, sales summary, earnings after commission
- MOB: "My Tickets" screen — list purchased tickets, display QR codes, share/download option
- QA: Test ticket generation (3 tickets for a qty-3 order) and delivery via email/SMS

**Day 9 (Tue)**
- BE: Admin panel — approve organizers, approve events, view all orders/payments
- BE: Ticket check-in API — validate QR token, mark ticket as used, block duplicate scans
- FE: Admin panel UI (Filament or custom Blade admin)
- MOB: Staff QR Scanner screen — staff login, camera-based scan calling the check-in API
- PM: Follow up with Zaad/eDahab (and WhatsApp Business API) on approval status

**Day 10 (Wed)**
- BE: Commission calculation logic (per order, per organizer), payout record structure
- FE: Browser-based QR scanner page for event staff (camera scan → Admit/Already Used/Invalid screen)
- FE: Admin payout screen (mark payouts as paid)
- MOB: Finish scanner UX (Admit/Already Used/Invalid screens), test on a real phone camera
- QA: Test commission math + check-in flow on both web scanner and app scanner (valid, duplicate, invalid QR)

**Day 11 (Thu)**
- BE: **If Zaad/eDahab credentials arrive** → implement real gateway classes and swap from Mock (serves web + app automatically since both use the same API). If not yet available → harden mock gateway, prepare integration branch ready to plug in.
- FE: Payment method selection UI polish, error/failure states, retry flow
- MOB: Payment error/retry states in-app, general UI polish and bug fixes
- QA: Test payment failure/retry scenarios on both platforms

**Day 12 (Fri)**
- BE: Security pass — validation, rate limiting on checkout/API, protect admin routes, .env secrets check, Sanctum token expiry rules
- FE: Mobile responsiveness pass (most Somaliland users will be on phones)
- MOB: Build release APK/AAB for Android, test on multiple real Android devices; start iOS build if an Apple Developer account is ready
- QA: Full regression test across all user roles (customer, organizer, admin) on web + app

**Day 13 (Sat)**
- Team: Load a handful of real events from Sagaljet + a test organizer account, real content pass
- PM: Prepare launch checklist, confirm domain/hosting/SSL
- MOB: Submit Android app to Google Play (or prepare direct APK download link to skip review wait); submit iOS to App Store if ready (review can take a few extra days)
- QA: Final bug bash on web + app

**Day 14 (Sun)**
- Team: Deploy web app to production server, smoke test live site, go live
- MOB: Final smoke test on mobile against production API; launch Android (Play Store or direct APK); iOS follows once Apple approves
- PM: Handover doc + short training for Sagaljet admin team on using the admin panel
- Buffer: Reserve part of the day for last-minute fixes post-launch

> **Note:** If Zaad/eDahab approval is delayed past Day 11, the platform (web **and** app) can still launch on schedule using the Mock Gateway in a "testing mode" banner, or with manual payment confirmation (customer pays via Zaad/eDahab to Sagaljet's number, sends screenshot, admin manually marks order as paid) as a stopgap — then flip to full API integration the moment credentials are approved, with zero rebuild needed on either platform.
>
> **Note on iOS:** Apple's App Store review (typically 1-3 days, sometimes longer) is outside the team's control. If it's not approved by Day 14, launch web + Android on schedule and let iOS follow a few days later — this is normal and expected, not a project delay.

---

## 11. Cost Estimate (Infrastructure & Third-Party Services Only)

**Scope note:** This covers **tools, hosting, and API/service costs** only — not developer salaries, since the team is internal. Prices are approximate (USD) and vary by provider; get final quotes before committing. Sagaljet should budget for these as ongoing operating costs, not just one-time setup.

### One-time / setup costs

| Item | Estimated Cost (USD) | Notes |
|---|---|---|
| Domain name (.com or .so) | $10 – $30/year | e.g. sagaljet.com |
| SSL certificate | $0 | Free via Let's Encrypt |
| Google Play Developer account | $25 (one-time) | Required to publish the Android app |
| Apple Developer Program | $99/year | Required to publish the iOS app |
| Zaad merchant/API setup fee | Varies (ask Telesom) | Some providers charge a one-time integration/registration fee |
| eDahab merchant/API setup fee | Varies (ask eDahab/Somtel) | Some providers charge a one-time integration/registration fee |
| **Estimated one-time total** | **~$135 – $250+** | Excludes any merchant setup fees, which need direct confirmation |

### Recurring / monthly costs

| Item | Estimated Cost (USD/month) | Notes |
|---|---|---|
| VPS Hosting (web + database) | $15 – $40/month | e.g. DigitalOcean/Linode droplet, 2-4GB RAM tier is enough for MVP |
| Backup storage | $2 – $5/month | Automated DB/file backups |
| SMS gateway (ticket links, notifications) | Pay-per-SMS, roughly $0.01 – $0.05/SMS | Cost scales with ticket volume; get local gateway pricing |
| WhatsApp Business API (via Twilio/360dialog/Gupshup) | ~$0 – $50/month + per-message fees | Many providers charge per conversation/message; often has a free tier for low volume |
| Email sending service (e.g. Mailgun/SendGrid) | $0 – $15/month | Free tiers usually cover a few thousand emails/month, enough for MVP |
| Zaad transaction fee | % per transaction (ask Telesom) | Typically deducted per payment, not a flat monthly fee |
| eDahab transaction fee | % per transaction (ask eDahab) | Typically deducted per payment, not a flat monthly fee |
| Firebase (push notifications, if used) | $0 | Free tier is sufficient for MVP volume |
| **Estimated monthly total (excluding transaction fees)** | **~$20 – $110/month** | Transaction fees are additional and scale with sales volume |

### Costs to confirm directly with providers (variable, can't estimate without their pricing sheet)
- **Zaad (Telesom) per-transaction commission %** — ask for their merchant/API pricing sheet
- **eDahab per-transaction commission %** — ask for their merchant/API pricing sheet
- **WhatsApp Business API exact per-message pricing** — depends on message volume and provider chosen

### What Sagaljet should budget for, in short
1. **~$150–$250 one-time** to get domain, app store accounts, and SSL sorted
2. **~$20–$110/month** in recurring hosting, SMS, email, and WhatsApp costs (scales up with usage)
3. **A per-transaction % fee** to Zaad and eDahab on every ticket sold — this is deducted automatically, not paid upfront, but should be factored into how commission/pricing is set so the platform stays profitable
4. **$99/year** for the Apple Developer account (Android/Google Play is a one-time $25 by comparison)

---

## 12. Risks & Mitigations

| Risk | Mitigation |
|---|---|
| Zaad/eDahab API approval delayed | Mock gateway + manual payment fallback, interface-based design for instant swap-in |
| Small team, tight 1-2 week timeline | Strict MVP scope (no phase 2 features), daily standups, cut anything not in section 5 |
| Payment webhook reliability (network issues common locally) | Poll payment status as backup to webhooks, don't rely on webhook alone |
| Organizer fraud (fake events) | Manual admin approval required before any organizer can publish |
| Server downtime during high-demand ticket sales | Queue ticket processing, avoid overselling via DB-level quantity locks |

---

## 13. Immediate Action Items

1. Assign the 3-4 team members to roles above
2. Confirm hosting provider and domain name
3. Confirm default commission % with Sagaljet
4. Keep pushing Zaad/eDahab for sandbox credentials in parallel with the build
5. Kick off Day 1 tasks tomorrow
