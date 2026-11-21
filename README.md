# 🎙️ Studio Booking

A studio session booking platform built with **Laravel**. Clients browse studios, pick an available time slot, and pay online. Admins manage studios, operating hours, photos, and bookings from a calendar-based dashboard.

> ## ⚠️ Project status: work in progress
>
> - **Not deployed yet.** There is no live demo; the app currently runs locally only.
> - **Not finished.** The core booking, payment, and admin features are implemented, but the project is still being developed and tested.
> - **The UI has not been designed yet.** The interface uses default Laravel Breeze / Tailwind styling with functional layouts only. A proper visual design pass is planned.
>
> Expect rough edges, missing polish, and changes to the code and structure.

The project focuses on solving real booking problems: **preventing double bookings**, **handling dynamic time slots**, and **processing payments reliably**.

## Current status

| Area | Status |
|---|---|
| Authentication (register / login / profile) | ✅ Done (Laravel Breeze) |
| Studio catalog, detail page, photo carousel | ✅ Functional, unstyled beyond defaults |
| Slot picker and conflict-free booking | ✅ Done |
| Client dashboard and booking history | ✅ Functional |
| Admin: studio CRUD, hours, photo upload | ✅ Functional |
| Admin: booking management and calendar | ✅ Functional |
| Midtrans payment (sandbox) | 🧪 Implemented, still being tested |
| Confirmation email with `.ics` attachment | 🧪 Implemented, still being tested |
| Reschedule (24 hours before the session) | 🧪 Implemented, still being tested |
| UI / visual design | 🚧 Not started |
| Automated tests | 🚧 Not started |
| Deployment | 🚧 Not started |

## Features

### Client
- **Studio catalog** with an auto-sliding photo carousel showing each studio's facilities
- **Interactive slot picker**: choose a date and duration, and unavailable hours are disabled in real time
- **Online payment** via Midtrans Snap (sandbox mode)
- **Booking history** with status badges (pending / confirmed / cancelled)
- **Reschedule** a booking up to 24 hours before the session (once per booking)
- **Email confirmation** with an `.ics` calendar file attached, plus a download link on the booking page
- Personal **dashboard** with upcoming sessions and spending stats

### Admin
- **Dashboard** with today's agenda, bookings awaiting verification, and monthly revenue
- **Calendar view** (FullCalendar) of all bookings, filterable by studio, with one-click confirm/cancel
- **Studio management**: CRUD, per-day operating hours, and multi-photo upload with captions
- **Booking management**: filter by status, studio, or date; confirm or cancel bookings

## Engineering highlights

| Problem | Approach |
|---|---|
| Double booking | Overlap check `existing.start < new.end AND existing.end > new.start`, run inside a DB transaction with `lockForUpdate()` so concurrent requests can't both succeed |
| Slots held by unpaid bookings | `pending` bookings also block the slot; a scheduled command cancels unpaid ones after 30 minutes (matching the Midtrans payment expiry) |
| Price tampering | Duration and total price are always calculated on the backend; the frontend only shows an estimate |
| Payment reliability | Status is updated through a single idempotent method, fed by both the Midtrans webhook (signature and amount verified) and an on-demand status sync |
| Late payments | A payment arriving after cancellation re-confirms the booking only if the slot is still free; otherwise it is logged for manual refund |
| Reschedule rules | Enforced server-side inside the same locked transaction, so double clicks can't bypass the limit |
| Calendar sync | `.ics` files keep a stable `UID` and an incrementing `SEQUENCE`, so rescheduling updates the existing calendar event instead of duplicating it |

## Tech stack

- **Backend:** Laravel 12, PHP 8.2+
- **Auth & UI:** Laravel Breeze (Blade), Tailwind CSS, Alpine.js
- **Calendar:** FullCalendar 6
- **Payments:** Midtrans Snap (sandbox)
- **Email:** Laravel Mail (Markdown mailable with ICS attachment)
- **Database:** any database supported by Laravel (MySQL, PostgreSQL, SQLite)

## Running locally

### Requirements
- PHP 8.2+ and Composer
- Node.js 18+ and npm
- A database (MySQL, PostgreSQL, or SQLite)
- A [Midtrans sandbox](https://dashboard.sandbox.midtrans.com) account

### Installation

```bash
git clone https://github.com/YOUR_USERNAME/studio-booking.git
cd studio-booking

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Configure your database and the variables below in `.env`, then:

```bash
php artisan migrate --seed
php artisan storage:link

# in two terminals
npm run dev
php artisan serve
```

Open <http://127.0.0.1:8000>.

To run the scheduler locally (auto-cancels unpaid bookings):

```bash
php artisan schedule:work
```

### Environment variables

| Variable | Description |
|---|---|
| `APP_TIMEZONE` | Application timezone, e.g. `Asia/Jakarta`. Affects "today" in the dashboard and past-slot checks |
| `DB_*` | Database connection |
| `MIDTRANS_SERVER_KEY` | Midtrans sandbox server key (needs Snap write and Core API read permissions) |
| `MIDTRANS_CLIENT_KEY` | Midtrans sandbox client key |
| `MIDTRANS_IS_PRODUCTION` | Keep `false` for sandbox |
| `MAIL_*` | SMTP settings (e.g. a [Mailtrap](https://mailtrap.io) sandbox inbox) |

Never commit your `.env` file or any Midtrans/SMTP credentials.

### Demo accounts (local seeder)

Created by `php artisan migrate --seed` for local development only:

| Role | Email | Password |
|---|---|---|
| Client | `user@demo.com` | `password123` |
| Admin | `admin@demo.com` | `password123` |

### Testing payments (sandbox)

Use the Midtrans test card:

- Card number: `4811 1111 1111 1114`
- Expiry: any future date
- CVV: `123`
- OTP: `112`

For other payment methods, use the [Midtrans simulator](https://simulator.sandbox.midtrans.com).

To receive webhooks locally, expose your app with a tunnel (ngrok or cloudflared) and set the **Payment Notification URL** in the Midtrans dashboard to `https://<your-domain>/midtrans/notification`. The booking page can also sync the payment status on demand.

## Business rules

- A booking stays `pending` for **30 minutes**; unpaid bookings are then cancelled and the slot is released.
- Both `pending` and `confirmed` bookings block a time slot.
- Clients can **reschedule once**, at least **24 hours** before the session. Duration and price stay the same.
- Clients can cancel only unpaid bookings. Refunds for paid bookings are handled by an admin.

## Project structure

```
app/
├── Http/Controllers/
│   ├── Admin/              # Studio CRUD, photo management, booking management, calendar
│   ├── BookingController   # Client booking flow, reschedule, cancel, .ics download
│   ├── PaymentController   # Snap token, status sync, Midtrans webhook
│   └── DashboardController # Client / admin dashboards
├── Mail/BookingConfirmed   # Confirmation email with .ics attachment
├── Models/                 # User, Studio, Schedule, Booking, StudioPhoto
├── Services/
│   ├── BookingService      # Conflict detection, pricing, reschedule logic
│   └── PaymentService      # Midtrans integration and status handling
└── Support/IcsGenerator    # iCalendar file generation
```

## Roadmap

- [ ] UI / visual design pass (currently default Breeze + Tailwind styling)
- [ ] Deployment (planned: Docker on a container host with a managed PostgreSQL database)
- [ ] Persistent cloud storage for uploaded studio photos
- [ ] Automated feature tests for the booking and payment flows
- [ ] Refund flow for paid bookings
- [ ] Reminder emails before a session

## License

_No license has been chosen yet._
