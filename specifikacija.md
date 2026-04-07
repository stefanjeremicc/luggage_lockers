# Belgrade Luggage Locker — Specifikacija v2.1

> Ovo je izvorni dokument specifikacije za Belgrade Luggage Locker projekat.
> Claude Code čita ovaj fajl direktno i implementira sprint po sprint.
> Fajl živi u root-u repozitorijuma: `docs/specifikacija-v2.1.md`

---

## 1. PREGLED PROJEKTA

**Naziv:** Belgrade Luggage Locker (BLL)
**Tip:** Web aplikacija za rezervaciju pametnih lockera za čuvanje prtljaga
**Klijent:** Postojeći biznis sa 2 lokacije u Beogradu, plan za skaliranje
**Domen:** belgradeluggagelocker.com (zamena postojećeg Shopify sajta)
**Primarni jezik:** Engleski (srpski kao sekundarni — i18n od starta)
**Brend:** Zadržava se postojeći identitet, vizuelno se unapređuje

---

## 2. TECH STACK

### 2.1 Definitivne tehnologije

| Sloj | Tehnologija | Napomena |
|------|------------|---------|
| Public frontend (SEO) | Laravel Blade + Alpine.js + Tailwind CSS | SSR — svaka stranica potpuno renderovana na serveru |
| PWA layer | Workbox (Service Worker) + manifest.json | Cache, offline, install prompt |
| Admin panel | Vue 3 SPA (Vite, zaseban entry point) | Dashboard, CRUD, real-time status |
| Backend / API | Laravel 11+ (PHP 8.3+) | Sanctum auth, REST API, Queue, Scheduler |
| Baza | MySQL 8 | Relacijska, JSON kolone za fleksibilnost |
| Cache / Queue / Sessions | Redis | Queue driver za TTLock sync jobs |
| IoT integracija | TTLock Cloud API (EU endpoint: euopen.ttlock.com) | Apstraktni LockService layer |
| Notifikacije | Email (SMTP/Mailgun) + WhatsApp Business API (Meta Cloud) | Templated poruke |
| Hosting | VPS (Hetzner) + Nginx + SSL + HTTP/2 | Forge ili Ploi za deploy |
| Vite | Multi-entry: public (Alpine) + admin (Vue 3) | Zasebni build-ovi |

### 2.2 Struktura projekta

```
belgrade-luggage-locker/
├── app/
│   ├── Http/Controllers/
│   │   ├── Public/              # Blade stranice (SEO)
│   │   │   ├── HomeController.php
│   │   │   ├── LocationController.php
│   │   │   ├── BookingController.php
│   │   │   ├── BlogController.php
│   │   │   ├── PageController.php
│   │   │   └── FaqController.php
│   │   └── Api/                 # REST API (JSON)
│   │       ├── AvailabilityController.php
│   │       ├── BookingApiController.php
│   │       ├── PricingController.php
│   │       ├── Admin/
│   │       │   ├── DashboardController.php
│   │       │   ├── BookingManagementController.php
│   │       │   ├── LockerController.php
│   │       │   ├── LocationManagementController.php
│   │       │   ├── PricingRuleController.php
│   │       │   ├── UserController.php
│   │       │   ├── BlogManagementController.php
│   │       │   ├── FaqManagementController.php
│   │       │   ├── NotificationTemplateController.php
│   │       │   └── SettingsController.php
│   │       └── Auth/
│   │           ├── LoginController.php
│   │           ├── OAuthController.php  # Google, Microsoft
│   │           └── GuestController.php
│   ├── Models/
│   │   ├── Location.php
│   │   ├── Locker.php
│   │   ├── Booking.php
│   │   ├── PricingRule.php
│   │   ├── Customer.php
│   │   ├── User.php               # Admin users
│   │   ├── NotificationLog.php
│   │   ├── TtlockSyncLog.php
│   │   ├── Page.php
│   │   ├── BlogPost.php
│   │   ├── Faq.php
│   │   └── Setting.php
│   ├── Services/
│   │   ├── Lock/
│   │   │   ├── LockServiceInterface.php
│   │   │   └── TTLockService.php
│   │   ├── Payment/
│   │   │   ├── PaymentServiceInterface.php
│   │   │   ├── CashPaymentService.php
│   │   │   └── StripePaymentService.php     # STUB — buduće
│   │   ├── Booking/
│   │   │   ├── BookingService.php
│   │   │   ├── AvailabilityService.php
│   │   │   └── PricingService.php
│   │   └── Notification/
│   │       ├── NotificationServiceInterface.php
│   │       ├── EmailNotificationService.php
│   │       └── WhatsAppNotificationService.php
│   ├── Jobs/
│   │   ├── SyncLockerStatus.php          # Svaka 2 min
│   │   ├── SyncAccessCodes.php           # Svake 3 min
│   │   ├── SyncGateways.php              # Svakih 5 min
│   │   ├── CreateTTLockAccessCode.php
│   │   ├── DeleteTTLockAccessCode.php
│   │   ├── SendBookingConfirmation.php
│   │   ├── SendExpiryReminder.php        # 30 min pre isteka
│   │   └── HandleExpiredBookings.php     # Svaki minut
│   └── Enums/
│       ├── BookingStatus.php             # pending, confirmed, active, completed, cancelled, expired
│       ├── PaymentStatus.php             # pending, paid, refunded
│       ├── PaymentMethod.php             # cash, stripe (buduće)
│       ├── LockerSize.php                # standard, large
│       ├── LockerStatus.php              # available, occupied, maintenance, offline
│       └── UserRole.php                  # super_admin, admin
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── public.blade.php          # Javni sajt layout
│   │   │   └── booking.blade.php         # Booking page layout (može biti drugačiji)
│   │   ├── public/
│   │   │   ├── home.blade.php
│   │   │   ├── locations/
│   │   │   │   ├── index.blade.php       # Lista lokacija
│   │   │   │   └── show.blade.php        # Pojedinačna lokacija
│   │   │   ├── booking/
│   │   │   │   ├── index.blade.php       # 3-step booking flow
│   │   │   │   └── confirmation.blade.php
│   │   │   ├── blog/
│   │   │   │   ├── index.blade.php
│   │   │   │   └── show.blade.php
│   │   │   ├── pages/
│   │   │   │   ├── about.blade.php
│   │   │   │   ├── faq.blade.php
│   │   │   │   ├── contact.blade.php
│   │   │   │   ├── terms.blade.php
│   │   │   │   └── privacy.blade.php
│   │   │   └── partials/
│   │   │       ├── header.blade.php
│   │   │       ├── footer.blade.php
│   │   │       ├── seo-meta.blade.php
│   │   │       ├── schema-markup.blade.php
│   │   │       └── cookie-consent.blade.php
│   │   └── emails/
│   │       ├── booking-confirmed.blade.php
│   │       ├── booking-reminder.blade.php
│   │       ├── booking-expired.blade.php
│   │       └── booking-cancelled.blade.php
│   ├── js/
│   │   ├── public/                       # Alpine.js komponente
│   │   │   ├── app.js                    # Alpine init + global
│   │   │   ├── booking-flow.js           # Stepper logika
│   │   │   ├── availability-checker.js   # API pozivi za dostupnost
│   │   │   ├── pricing-calculator.js     # Live cena
│   │   │   └── service-worker-register.js
│   │   └── admin/                        # Vue 3 SPA
│   │       ├── main.js
│   │       ├── router/index.js
│   │       ├── stores/                   # Pinia
│   │       ├── views/
│   │       ├── components/
│   │       └── composables/
│   ├── css/
│   │   ├── public.css                    # Tailwind + custom
│   │   └── admin.css
│   └── lang/
│       ├── en/
│       └── sr/
├── public/
│   ├── manifest.json                     # PWA manifest
│   ├── sw.js                             # Service Worker (Workbox generated)
│   └── icons/                            # PWA ikone 192x192, 512x512
├── routes/
│   ├── web.php                           # Blade rute (public)
│   ├── api.php                           # REST API rute
│   └── admin.php                         # Admin SPA catch-all
├── database/
│   └── migrations/
├── docs/
│   └── specifikacija-v2.1.md             # OVAJ FAJL
└── vite.config.js                        # Multi-entry: public + admin
```

---

## 3. BAZA PODATAKA

### 3.1 Tabela: locations

```sql
CREATE TABLE locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    address VARCHAR(500) NOT NULL,
    city VARCHAR(100) NOT NULL DEFAULT 'Belgrade',
    lat DECIMAL(10, 8) NOT NULL,
    lng DECIMAL(11, 8) NOT NULL,
    description TEXT NULL,
    description_sr TEXT NULL,
    opening_time TIME NULL,                    -- NULL = 24/7
    closing_time TIME NULL,                    -- NULL = 24/7
    is_24h BOOLEAN NOT NULL DEFAULT TRUE,
    timezone VARCHAR(50) NOT NULL DEFAULT 'Europe/Belgrade',
    phone VARCHAR(50) NULL,
    whatsapp VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    google_maps_url VARCHAR(500) NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(500) NULL,
    og_image VARCHAR(500) NULL,
    settings JSON NULL,                        -- Slobodno za custom konfiguraciju
    sort_order INT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### 3.2 Tabela: lockers

```sql
CREATE TABLE lockers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id BIGINT UNSIGNED NOT NULL,
    ttlock_lock_id BIGINT NULL,                -- TTLock numeric ID
    uuid CHAR(36) NOT NULL UNIQUE,             -- Interni UUID
    number VARCHAR(20) NOT NULL,               -- Ljudski čitljiv broj: "A01", "B03"
    size ENUM('standard', 'large') NOT NULL,
    status ENUM('available', 'occupied', 'maintenance', 'offline') NOT NULL DEFAULT 'available',
    battery_level INT NULL,                    -- 0-100, iz TTLock sync-a
    is_online BOOLEAN NOT NULL DEFAULT TRUE,   -- Da li je TTLock dostupan
    dimensions_cm JSON NULL,                   -- {"width": 40, "height": 50, "depth": 60}
    sort_order INT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_synced_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,
    INDEX idx_location_size_active (location_id, size, is_active),
    INDEX idx_ttlock (ttlock_lock_id)
);
```

### 3.3 Tabela: customers

```sql
CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NULL,
    country_code VARCHAR(5) NULL,              -- ISO: RS, US, DE...
    oauth_provider VARCHAR(20) NULL,           -- google, microsoft, NULL za guest
    oauth_id VARCHAR(255) NULL,
    locale VARCHAR(5) NOT NULL DEFAULT 'en',
    whatsapp_opt_in BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    UNIQUE INDEX idx_email (email),
    INDEX idx_oauth (oauth_provider, oauth_id)
);
```

### 3.4 Tabela: bookings

```sql
CREATE TABLE bookings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,             -- Za public URL-ove: /booking/{uuid}/confirmation
    customer_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    locker_id BIGINT UNSIGNED NULL,            -- NULL dok se ne dodeli fizički locker
    locker_size ENUM('standard', 'large') NOT NULL,
    locker_qty INT NOT NULL DEFAULT 1,
    check_in DATETIME NOT NULL,
    check_out DATETIME NOT NULL,
    duration_label VARCHAR(20) NOT NULL,       -- '6h', '24h', '2_days', '1_week', '1_month'
    pin_code VARCHAR(255) NULL,                -- HASHED. Plain text se šalje samo u notifikaciji.
    pin_code_plain VARCHAR(10) NULL,           -- Temporary: čisti se posle slanja notifikacije. Ili se čuva encrypted.
    ttlock_keyboard_pwd_id BIGINT NULL,        -- TTLock ID pristupnog koda
    price_eur DECIMAL(8, 2) NOT NULL,
    price_rsd DECIMAL(10, 2) NULL,
    service_fee_eur DECIMAL(8, 2) NOT NULL DEFAULT 0.00,
    total_eur DECIMAL(8, 2) NOT NULL,
    booking_status ENUM('pending', 'confirmed', 'active', 'completed', 'cancelled', 'expired') NOT NULL DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'refunded') NOT NULL DEFAULT 'pending',
    payment_method ENUM('cash', 'stripe') NOT NULL DEFAULT 'cash',
    paid_at TIMESTAMP NULL,                    -- Kad admin označi keš kao primljen
    cancelled_at TIMESTAMP NULL,
    cancel_reason VARCHAR(500) NULL,
    notes TEXT NULL,                            -- Admin beleške
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (location_id) REFERENCES locations(id),
    FOREIGN KEY (locker_id) REFERENCES lockers(id) ON DELETE SET NULL,
    INDEX idx_status (booking_status),
    INDEX idx_location_checkin (location_id, check_in, check_out),
    INDEX idx_customer (customer_id),
    INDEX idx_uuid (uuid)
);
```

**VAŽNO:** Kad je `locker_qty > 1`, kreira se VIŠE booking redova (jedan po lockeru) sa istim `group_uuid` poljem. Alternativa: booking je "header" a lockeri su u pivot tabeli. Izaberi pristup 2 — dodaj:

### 3.5 Tabela: booking_lockers (pivot)

```sql
CREATE TABLE booking_lockers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED NOT NULL,
    locker_id BIGINT UNSIGNED NOT NULL,
    pin_code_encrypted VARCHAR(255) NULL,      -- Encrypted PIN za ovaj konkretan locker
    ttlock_keyboard_pwd_id BIGINT NULL,
    assigned_at TIMESTAMP NULL,

    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (locker_id) REFERENCES lockers(id),
    UNIQUE INDEX idx_booking_locker (booking_id, locker_id)
);
```

Tada `bookings` tabela nema `locker_id`, `pin_code`, `ttlock_keyboard_pwd_id` — to je u pivotu. `bookings.locker_qty` ostaje kao info polje.

### 3.6 Tabela: pricing_rules

```sql
CREATE TABLE pricing_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id BIGINT UNSIGNED NULL,          -- NULL = globalno pravilo (default)
    locker_size ENUM('standard', 'large') NOT NULL,
    duration_key VARCHAR(20) NOT NULL,         -- '6h', '24h', '2_days', '3_days', '4_days', '5_days', '1_week', '2_weeks', '1_month'
    price_eur DECIMAL(8, 2) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_loc_size_dur (location_id, locker_size, duration_key)
);
```

**Logika:** Ako postoji pricing_rule za konkretnu lokaciju — koristi se. Ako ne — koristi se globalno pravilo (location_id = NULL).

### 3.7 Defaultni cenovnik (seed)

| duration_key | Standard EUR | Large EUR |
|---|---|---|
| 6h | 5.00 | 10.00 |
| 24h | 10.00 | 15.00 |
| 2_days | 18.00 | 27.00 |
| 3_days | 25.00 | 38.00 |
| 4_days | 30.00 | 45.00 |
| 5_days | 35.00 | 52.00 |
| 1_week | 50.00 | 75.00 |
| 2_weeks | 85.00 | 130.00 |
| 1_month | 150.00 | 230.00 |

### 3.8 Tabela: users (admin)

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin') NOT NULL DEFAULT 'admin',
    location_ids JSON NULL,                    -- Admin: kojima lokacijama ima pristup. Super admin: NULL (sve).
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### 3.9 Tabela: notification_log

```sql
CREATE TABLE notification_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED NOT NULL,
    channel ENUM('email', 'whatsapp') NOT NULL,
    template VARCHAR(100) NOT NULL,            -- 'booking_confirmed', 'expiry_reminder', 'expired', 'cancelled'
    recipient VARCHAR(255) NOT NULL,
    payload JSON NULL,
    status ENUM('queued', 'sent', 'failed') NOT NULL DEFAULT 'queued',
    error_message TEXT NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,

    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);
```

### 3.10 Tabela: ttlock_api_log

```sql
CREATE TABLE ttlock_api_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    request_params JSON NULL,
    response_body JSON NULL,
    response_code INT NULL,
    errcode INT NULL,                          -- TTLock errcode (0 = success)
    duration_ms INT NULL,
    related_type VARCHAR(50) NULL,             -- 'booking', 'locker', 'gateway'
    related_id BIGINT NULL,
    created_at TIMESTAMP NULL,

    INDEX idx_endpoint (endpoint),
    INDEX idx_errcode (errcode),
    INDEX idx_related (related_type, related_id)
);
```

### 3.11 Tabela: pages (CMS)

```sql
CREATE TABLE pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(255) NOT NULL,
    locale VARCHAR(5) NOT NULL DEFAULT 'en',
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(500) NULL,
    og_image VARCHAR(500) NULL,
    is_published BOOLEAN NOT NULL DEFAULT FALSE,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    UNIQUE INDEX idx_slug_locale (slug, locale)
);
```

### 3.12 Tabela: blog_posts

```sql
CREATE TABLE blog_posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(255) NOT NULL,
    locale VARCHAR(5) NOT NULL DEFAULT 'en',
    title VARCHAR(255) NOT NULL,
    excerpt TEXT NULL,
    content LONGTEXT NULL,
    featured_image VARCHAR(500) NULL,
    category VARCHAR(100) NULL,
    tags JSON NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(500) NULL,
    author_name VARCHAR(255) NULL,
    is_published BOOLEAN NOT NULL DEFAULT FALSE,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    UNIQUE INDEX idx_slug_locale (slug, locale),
    INDEX idx_category_locale (category, locale),
    INDEX idx_published (is_published, published_at)
);
```

### 3.13 Tabela: faqs

```sql
CREATE TABLE faqs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    locale VARCHAR(5) NOT NULL DEFAULT 'en',
    question VARCHAR(500) NOT NULL,
    answer TEXT NOT NULL,
    category VARCHAR(100) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_locale_active (locale, is_active, sort_order)
);
```

### 3.14 Tabela: settings

```sql
CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key VARCHAR(100) NOT NULL UNIQUE,
    value TEXT NULL,
    type VARCHAR(20) NOT NULL DEFAULT 'string',  -- string, int, float, bool, json
    group VARCHAR(50) NULL,                       -- 'general', 'ttlock', 'notifications', 'pricing'
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

**Default settings seed:**

| key | value | group |
|---|---|---|
| eur_rsd_rate | 117.00 | pricing |
| service_fee_eur | 0.00 | pricing |
| ttlock_client_id | (env) | ttlock |
| ttlock_client_secret | (env) | ttlock |
| ttlock_username | (env) | ttlock |
| whatsapp_api_token | (env) | notifications |
| whatsapp_phone_id | (env) | notifications |
| booking_tolerance_minutes | 20 | general |
| expiry_reminder_minutes | 30 | general |
| default_locale | en | general |
| site_name | Belgrade Luggage Locker | general |
| contact_phone | +381 65 332 2319 | general |
| contact_email | info@belgradeluggagelocker.com | general |

---

## 4. ROUTING

### 4.1 Public rute (Blade SSR)

```php
// Locale prefix — en je default, sr je eksplicitan
Route::group(['prefix' => '{locale?}', 'middleware' => 'set-locale'], function () {

    // Homepage
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // Lokacije
    Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
    Route::get('/locations/{slug}', [LocationController::class, 'show'])->name('locations.show');

    // Booking
    Route::get('/locations/{slug}/book', [BookingController::class, 'index'])->name('booking.index');
    Route::get('/booking/{uuid}/confirmation', [BookingController::class, 'confirmation'])->name('booking.confirmation');
    Route::get('/booking/{uuid}/cancel', [BookingController::class, 'cancelForm'])->name('booking.cancel');

    // Blog
    Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

    // Statičke stranice
    Route::get('/faq', [FaqController::class, 'index'])->name('faq');
    Route::get('/about', [PageController::class, 'about'])->name('about');
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
    Route::get('/terms', [PageController::class, 'terms'])->name('terms');
    Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');

    // SEO programatičke stranice
    Route::get('/near/{slug}', [PageController::class, 'nearAttraction'])->name('near');
    Route::get('/{slug}', [PageController::class, 'dynamicPage'])->name('page.dynamic');
});
```

### 4.2 API rute

```php
Route::prefix('api')->group(function () {

    // Public API (bez auth)
    Route::get('/locations/{id}/availability', [AvailabilityController::class, 'check']);
    Route::get('/locations/{id}/pricing', [PricingController::class, 'calculate']);
    Route::post('/bookings', [BookingApiController::class, 'store']);
    Route::post('/bookings/{uuid}/cancel', [BookingApiController::class, 'cancel']);

    // Auth
    Route::post('/auth/guest', [GuestController::class, 'register']);
    Route::post('/auth/google', [OAuthController::class, 'google']);
    Route::post('/auth/microsoft', [OAuthController::class, 'microsoft']);

    // Admin API (Sanctum auth)
    Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);

        Route::apiResource('bookings', BookingManagementController::class);
        Route::post('/bookings/{id}/mark-paid', [BookingManagementController::class, 'markPaid']);
        Route::post('/bookings/{id}/extend', [BookingManagementController::class, 'extend']);
        Route::get('/bookings/export', [BookingManagementController::class, 'export']);

        Route::apiResource('lockers', LockerController::class);
        Route::post('/lockers/{id}/remote-unlock', [LockerController::class, 'remoteUnlock']);

        Route::apiResource('locations', LocationManagementController::class);
        Route::apiResource('pricing-rules', PricingRuleController::class);
        Route::apiResource('blog-posts', BlogManagementController::class);
        Route::apiResource('faqs', FaqManagementController::class);
        Route::apiResource('notification-templates', NotificationTemplateController::class);

        Route::get('/settings', [SettingsController::class, 'index']);
        Route::put('/settings', [SettingsController::class, 'update']);

        // Super admin only
        Route::middleware('role:super_admin')->group(function () {
            Route::apiResource('users', UserController::class);
        });
    });
});
```

### 4.3 Admin SPA catch-all

```php
// routes/admin.php
Route::get('/admin/{any?}', function () {
    return view('admin.spa');
})->where('any', '.*')->middleware(['auth:sanctum', 'role:admin']);
```

---

## 5. BOOKING FLOW — KOMPLETNA LOGIKA

### 5.1 Pregled toka

```
Homepage → Izbor lokacije → /locations/{slug}/book
  │
  ├─ KORAK 1: Date/Time + Locker Type + Quantity + Duration
  │    └─ Real-time pricing + availability
  │    └─ "Continue" button
  │
  ├─ KORAK 2: Identifikacija (Google/Microsoft OAuth ili Guest forma)
  │    └─ "Continue to confirmation"
  │
  ├─ KORAK 3: Rezime + "Book" button
  │    └─ POST /api/bookings → TTLock PIN → Email + WhatsApp
  │
  └─ CONFIRMATION: /booking/{uuid}/confirmation
       └─ PIN kod + detalji + PWA save + directions
```

Ceo flow je **na jednoj Blade stranici** (`booking/index.blade.php`). Alpine.js kontroliše stepper — koraci se prikazuju/sakrivaju bez page reload-a. URL se NE menja po koracima (nema hash routing-a).

### 5.2 Korak 1: Date, Time, Locker, Duration

**Layout:** Jedna kolona na mobilnom, dve na desktop-u (levo parametri, desno Order Summary).

**A) Datum dolaska**

Tri pill dugmeta u redu:
- `Today` — preselectovano ako je trenutno vreme pre closing_time lokacije. Disabled ako je posle.
- `Tomorrow`
- `Choose date` — klik otvara inline flatpickr kalendar ispod pill-ova. Min date = danas, max date = danas + 30 dana.

Alpine.js data: `selectedDate: 'today'` → resolve u ISO datum pri API pozivu.

**B) Vreme dolaska**

Select dropdown. Opcije se generišu na osnovu:
- Radnog vremena lokacije za izabrani dan
- 15-minutni intervali
- Ako je "Today" — prvi slot = najbliži budući 15-min interval + 20 min (tolerancija dolaska)
- Ako lokacija radi 24/7 — svi slotovi od 00:00 do 23:45

Alpine.js: `selectedTime: null` — korisnik MORA eksplicitno da izabere.

**C) Tip lockera**

Dve kartice, horizontalno:

```
┌─────────────────────┐  ┌─────────────────────┐
│  🎒 STANDARD        │  │  🧳 LARGE            │
│                     │  │                     │
│  Up to 1 carry-on   │  │  Up to 2 suitcases   │
│  40×35×55 cm        │  │  60×45×75 cm         │
│                     │  │                     │
│  from €5            │  │  from €10            │
│  ● 5 available      │  │  ● 2 available       │
└─────────────────────┘  └─────────────────────┘
```

Badge dostupnosti:
- Zelena tačka + "{n} available" ako n > 3
- Narandžasta tačka + "{n} left!" ako n <= 3
- Crvena + "Sold out" + kartica disabled ako n = 0

Dostupnost se čita sa: `GET /api/locations/{id}/availability?date=...&time=...&duration=...`

**D) Broj lockera**

Stepper sa +/− dugmadima. Default: 1. Min: 1. Max: broj dostupnih lockera izabranog tipa.
Ako korisnik pokuša da poveća iznad max → inline error: "Only {n} lockers available for this time slot."

**E) Trajanje**

Horizontalni pill-ovi (sa scroll na mobile):
```
[ 6h ] [ 24h ] [ 2 days ] [ 3 days ] [ 4 days ] [ 5 days ] [ 1 week ] [ 2 weeks ] [ 1 month ]
```

Svaki pill prikazuje cenu za 1 locker: `6h — €5`. Aktivni pill ima accent boju.

**F) Order Summary (desna kolona / sticky footer)**

```
📍 City Center — Kapetan Mišina 2A

📅 Check-in:  04 Apr 2026, 10:00 AM
📅 Check-out: 04 Apr 2026, 04:00 PM

🔒 2 × Standard Locker (6 hours)

   Subtotal          €10.00
   Service fee        €0.00
   ──────────────────────────
   TOTAL             €10.00
                     (≈1,170 RSD)

   💰 Pay cash on arrival

   [ Continue → ]
```

"Continue" dugme disabled dok svi parametri nisu popunjeni. Na klik → prikaži Korak 2.

**G) Availability API endpoint**

```
GET /api/locations/{id}/availability?date=2026-04-04&time=10:00&duration=6h

Response:
{
  "standard": { "total": 8, "booked": 3, "available": 5 },
  "large": { "total": 4, "booked": 2, "available": 2 },
  "check_out_time": "2026-04-04T16:00:00",
  "location_open": true
}
```

**Backend logika za dostupnost:**

```php
// AvailabilityService::check($locationId, $date, $time, $duration)

$checkIn = Carbon::parse("$date $time");
$checkOut = $this->calculateCheckOut($checkIn, $duration);

$bookedStandard = Booking::where('location_id', $locationId)
    ->where('locker_size', 'standard')
    ->whereIn('booking_status', ['confirmed', 'active'])
    ->where('check_in', '<', $checkOut)
    ->where('check_out', '>', $checkIn)
    ->sum('locker_qty');

$totalStandard = Locker::where('location_id', $locationId)
    ->where('size', 'standard')
    ->where('is_active', true)
    ->count();

$availableStandard = $totalStandard - $bookedStandard;
```

**H) Pricing API endpoint**

```
GET /api/locations/{id}/pricing?size=standard&duration=6h&qty=2

Response:
{
  "unit_price_eur": 5.00,
  "qty": 2,
  "subtotal_eur": 10.00,
  "service_fee_eur": 0.00,
  "total_eur": 10.00,
  "total_rsd": 1170.00,
  "duration_label": "6 hours"
}
```

**I) Frontend reactivity (Alpine.js)**

Svaka promena bilo kog parametra (datum, vreme, tip, količina, trajanje) triggeruje:
1. Debounced API poziv na `/availability` (300ms delay)
2. API poziv na `/pricing`
3. Update Order Summary

```javascript
// booking-flow.js — Alpine component
Alpine.data('bookingFlow', () => ({
    step: 1,
    date: 'today',
    time: null,
    lockerSize: null,
    qty: 1,
    duration: null,
    availability: { standard: { available: 0 }, large: { available: 0 } },
    pricing: null,
    loading: false,

    get canContinue() {
        return this.date && this.time && this.lockerSize && this.duration && this.qty > 0;
    },

    async fetchAvailability() {
        // Debounced, poziva /api/locations/{id}/availability
    },

    async fetchPricing() {
        // Poziva /api/locations/{id}/pricing
    },

    // Watchers na svaku promenu parametra → fetchAvailability + fetchPricing
}));
```

### 5.3 Korak 2: Identifikacija

**Layout:** Centrirana forma sa max-width 500px.

**Na vrhu — OAuth dugmad:**

```
[ 🔵 Continue with Google ]
[ 🟦 Continue with Microsoft ]

─────── or continue as guest ───────
```

**Guest forma:**

| Polje | Tip | Obavezno | Validacija |
|---|---|---|---|
| Full name | text | DA | min 2 char |
| Email | email | DA | valid email format |
| Phone number | tel + country prefix | DA | min 8 cifara |
| Country | select dropdown | DA | ISO country list |
| Agree to Terms | checkbox | DA | must be checked |
| WhatsApp updates | checkbox | NE | default unchecked |

Country dropdown ima auto-detect na osnovu IP-a (geo lookup) sa opcijom da korisnik promeni.

**OAuth flow:**

1. Korisnik klikne "Continue with Google"
2. Redirect na Google OAuth consent screen
3. Google vraća name, email (phone nije garantovan)
4. Backend kreira/pronalazi Customer record
5. Ako phone nedostaje — prikazuje se SAMO phone polje sa porukom "We need your phone number for booking updates"
6. Alpine.js: `customer: { id, name, email, phone }` — popunjeno iz API odgovora

**API endpoint za guest registraciju:**

```
POST /api/auth/guest
Body: { full_name, email, phone, country_code, whatsapp_opt_in }

Response:
{ customer_id: 123, token: "..." }  // Sanctum token za booking
```

Na "Continue to confirmation" → prikaži Korak 3.

### 5.4 Korak 3: Confirm & Book

**Layout:** Dve kolone. Levo: booking rezime kartica. Desno: payment info + CTA.

**Booking rezime kartica:**

```
┌─────────────────────────────────────────────┐
│  YOUR BOOKING                               │
│                                             │
│  📍 Luggage Storage          📌 Address      │
│     City Center                 Kapetan      │
│                                 Mišina 2A    │
│  🕐 Check-in              🕐 Check-out      │
│     04 Apr 10:00 AM           04 Apr 4:00 PM│
│                                             │
│  🔒 Lockers               👤 Customer       │
│     2 × Standard              John Doe      │
│                               john@email.com│
│                                             │
│  💰 Payment                                 │
│     Cash on arrival                         │
└─────────────────────────────────────────────┘
```

**Payment sekcija:**

```
💰 Payment: Cash on arrival
   Pay €10.00 (or ≈1,170 RSD) when you arrive.

   ℹ️ Exact change appreciated. We accept EUR and RSD.
```

**CTA dugme:**

```
┌─────────────────────────────────────┐
│           ✓  BOOK                   │
└─────────────────────────────────────┘
   By booking, you agree to our Terms.
```

Dugme text je **"Book"** — NE "Pay Now", jer nema online plaćanja.

**Na klik "Book" — šta se dešava:**

```
1. Frontend disabluje dugme, prikazuje spinner
2. POST /api/bookings sa svim podacima
3. Backend (unutar DB transakcije):
   a. SELECT ... FOR UPDATE na lockere te lokacije i tipa
   b. Proveri dostupnost JOŠ JEDNOM (anti-race-condition)
   c. Ako nema dostupnih → 409 Conflict → frontend prikazuje error modal
   d. Ako ima:
      - Dodeli slobodne lockere (locker_qty komada) iz pool-a
      - Kreira booking (status: confirmed)
      - Kreira booking_lockers zapise
      - Za svaki locker: dispatch CreateTTLockAccessCode job
      - Dispatch SendBookingConfirmation job
      - Return 201 + booking UUID
4. Frontend → redirect na /booking/{uuid}/confirmation
```

**POST /api/bookings payload:**

```json
{
  "location_id": 1,
  "customer_token": "sanctum-token",
  "locker_size": "standard",
  "locker_qty": 2,
  "date": "2026-04-04",
  "time": "10:00",
  "duration": "6h",
  "payment_method": "cash"
}
```

**Response 201:**
```json
{
  "booking_uuid": "a1b2c3d4-...",
  "redirect_url": "/booking/a1b2c3d4-.../confirmation"
}
```

**Response 409 (overbooking):**
```json
{
  "error": "not_available",
  "message": "Sorry, the selected lockers are no longer available.",
  "available": { "standard": 1, "large": 2 }
}
```

### 5.5 Confirmation stranica

Blade stranica: `/booking/{uuid}/confirmation`

**Layout:** Tamna pozadina. Centrirana bela/svetla kartica.

```
✅ Booking Confirmed!

┌─────────────────────────────────────────────┐
│                                             │
│         Your PIN Code                       │
│                                             │
│         4  7  2  9  1  8                    │
│         (monospace, 48px, spaced)            │
│                                             │
│  Locker: A03, A07                           │
│  Location: City Center                      │
│  Address: Kapetan Mišina 2A, Belgrade       │
│                                             │
│  Check-in:  04 Apr 2026, 10:00 AM           │
│  Check-out: 04 Apr 2026, 04:00 PM          │
│                                             │
│  Total: €10.00 — Pay cash on arrival        │
│                                             │
│  [ 📍 Get Directions ]  [ 📱 Save to Phone ]│
│                                             │
│  📧 Confirmation sent to john@email.com     │
│  📱 WhatsApp sent to +381 65 ...            │
│                                             │
│  Need to cancel? Click here                 │
│                                             │
└─────────────────────────────────────────────┘
```

PWA: Ova stranica se kešira u Service Worker-u. Korisnik može da je otvori offline.

---

## 6. TTLock INTEGRACIJA

### 6.1 LockServiceInterface

```php
interface LockServiceInterface
{
    public function createTimedAccessCode(int $lockId, string $code, Carbon $start, Carbon $end): array;
    public function deleteAccessCode(int $lockId, int $keyboardPwdId): bool;
    public function updateAccessCodeTime(int $lockId, int $keyboardPwdId, Carbon $newEnd): bool;
    public function remoteLock(int $lockId): bool;
    public function remoteUnlock(int $lockId): bool;
    public function getLockDetail(int $lockId): array;
    public function getLockList(): array;
    public function getGatewayList(): array;
    public function getUnlockRecords(int $lockId, Carbon $start, Carbon $end): array;
}
```

### 6.2 TTLock autentifikacija

```
EU endpoint: https://euapi.ttlock.com
Token: POST /oauth2/token (password = MD5 hash)
Svaki request: accessToken + date (13-digit ms timestamp) kao URL params
Error handling: HTTP 200 uvek — proveri errcode u response body (0 = OK)
```

Credentials u `.env`:
```
TTLOCK_CLIENT_ID=
TTLOCK_CLIENT_SECRET=
TTLOCK_USERNAME=
TTLOCK_PASSWORD=
TTLOCK_BASE_URL=https://euapi.ttlock.com
```

### 6.3 Pristupni kodovi — booking lifecycle

| Događaj | TTLock operacija | Detalji |
|---|---|---|
| Booking potvrđen | `keyboardPwd/add` (timed) | 4-8 cifreni PIN, važi od check_in do check_out |
| Booking otkazan | `keyboardPwd/delete` | Odmah deaktiviraj PIN |
| Booking istekao | `keyboardPwd/delete` | Scheduler job detektuje istek |
| Produženje (admin) | `keyboardPwd/change` | Promeni end time na novi check_out |
| Emergency unlock (admin) | `lock/unlock` | Remote otključavanje, audit log |

### 6.4 Sync jobs (Laravel Scheduler)

```php
// app/Console/Kernel.php
$schedule->job(new SyncLockerStatus)->everyTwoMinutes();
$schedule->job(new SyncAccessCodes)->everyThreeMinutes();
$schedule->job(new SyncGateways)->everyFiveMinutes();
$schedule->job(new HandleExpiredBookings)->everyMinute();
```

### 6.5 PIN kod generisanje

```php
// BookingService::generateUniquePin($lockId)
do {
    $pin = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $exists = BookingLocker::where('locker_id', $lockId)
        ->whereHas('booking', fn($q) => $q->whereIn('booking_status', ['confirmed', 'active']))
        ->where('pin_code_encrypted', encrypt($pin))
        ->exists();
} while ($exists);
return $pin;
```

PIN mora biti unikatan po lockeru među aktivnim bookingima.

---

## 7. NOTIFIKACIJE

### 7.1 Email — Booking Confirmed

Subject: `Your Belgrade Luggage Locker Booking — PIN: {pin}`

Sadržaj:
- PIN kod (veliki, bold)
- Broj lockera
- Lokacija + adresa + Google Maps link
- Check-in / Check-out vreme
- Cena + payment info
- Uputstvo (5 koraka)
- Cancel link
- Kontakt info

### 7.2 WhatsApp — Booking Confirmed

Template poruka (mora biti pre-odobrena od Meta):

```
🔐 Your locker is booked!

PIN: {{pin}}
Locker: {{locker_numbers}}
Location: {{location_name}}
Address: {{address}}
Check-in: {{check_in}}
Check-out: {{check_out}}
Total: €{{total}} — Pay cash on arrival

📍 Directions: {{maps_url}}

Need help? Reply to this message.
```

### 7.3 Expiry Reminder (30 min pre)

Email + WhatsApp:
```
⏰ Your locker time ends at {{check_out}}.
If you need more time, contact us: {{phone}}
```

### 7.4 Expired

Email:
```
Your booking has expired. Please pick up your belongings.
Contact us if you need assistance: {{phone}}
```

---

## 8. ADMIN PANEL (Vue 3 SPA)

### 8.1 Dashboard

- Real-time locker grid po lokaciji (zelena/crvena/siva)
- Današnje rezervacije (count + lista)
- Prihod: danas / nedelja / mesec
- Lockeri sa prekoračenim vremenom (alert)
- Gateway status (online/offline)
- Baterija upozorenja (ispod 20%)

### 8.2 Rezervacije

- Filterable/sortable tabela: datum, lokacija, status, veličina, payment status
- Akcije: view, mark as paid, extend, cancel
- Export CSV/Excel

### 8.3 Lockeri

- Lista po lokaciji sa real-time statusom
- CRUD: dodavanje/uklanjanje, mapiranje TTLock ID-ja
- Remote unlock dugme (sa potvrdom)
- Baterija i poslednji sync

### 8.4 Lokacije

- CRUD sa svim poljima iz tabele
- Pricing override po lokaciji
- Povezivanje sa TTLock nalogom

### 8.5 CMS

- Blog posts CRUD (WYSIWYG editor — TipTap ili Quill)
- FAQ CRUD sa drag-and-drop sort-om
- Pages CRUD
- SEO polja na svemu (meta title, description, OG image)

### 8.6 Settings

- Globalni cenovnik
- EUR/RSD kurs
- Notifikacioni templejti
- Politika otkazivanja

---

## 9. SEO

### 9.1 Tehnički SEO

- Server-side rendering (Blade) — komplet HTML renderovan na serveru
- Semantički HTML5: h1/h2/h3 hijerarhija, semantic sections
- JSON-LD Schema markup na svim stranicama:
  - Homepage: `LocalBusiness`, `WebSite`
  - Lokacije: `Place`, `LocalBusiness`
  - FAQ: `FAQPage`
  - Blog: `Article`, `BlogPosting`
  - Breadcrumbs: `BreadcrumbList`
- Meta tagovi konfigurabilni iz admin panela
- Canonical URL-ovi
- hreflang tagovi (en ↔ sr)
- Sitemap.xml (auto-generated)
- robots.txt
- Core Web Vitals: lazy loading, preconnect, minified assets, HTTP/2

### 9.2 URL struktura

| Stranica | EN URL | SR URL |
|---|---|---|
| Homepage | `/` | `/sr` |
| Lokacije | `/locations` | `/sr/lokacije` |
| Lokacija | `/locations/city-center` | `/sr/lokacije/centar-grada` |
| Booking | `/locations/city-center/book` | `/sr/lokacije/centar-grada/rezervacija` |
| Blog | `/blog` | `/sr/blog` |
| Blog post | `/blog/belgrade-travel-guide` | `/sr/blog/vodic-kroz-beograd` |
| FAQ | `/faq` | `/sr/cesta-pitanja` |
| Near | `/near/kalemegdan-fortress` | `/sr/blizu/kalemegdanska-tvrdjava` |

---

## 10. PWA

### 10.1 manifest.json

```json
{
  "name": "Belgrade Luggage Locker",
  "short_name": "BLL Lockers",
  "description": "24/7 luggage storage in Belgrade",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#0A0A0A",
  "theme_color": "#F59E0B",
  "icons": [
    { "src": "/icons/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/icons/icon-512.png", "sizes": "512x512", "type": "image/png" }
  ],
  "lang": "en"
}
```

### 10.2 Service Worker strategija (Workbox)

| Resurs | Strategija |
|---|---|
| App Shell (HTML, CSS, JS) | Cache First, update u pozadini |
| Slike, fontovi, ikone | Cache First (duži TTL) |
| API: booking detalji | Network First, fallback na cache |
| API: locker dostupnost | Network Only |
| Blog / statičke stranice | Stale While Revalidate |

### 10.3 Offline

- Korisnik može da vidi aktivan booking + PIN offline
- PIN se kešira u encrypted localStorage po bookingu
- Statičke stranice (FAQ, How It Works) keširane
- Ne može: kreirati booking, videti dostupnost, otkazati

---

## 11. VIZUELNI PRAVAC

**Tema:** Minimalistički, tamni, premium

| Element | Vrednost |
|---|---|
| Pozadina | #0A0A0A (body), #111111 (subtle) |
| Kartice/sekcije | #1A1A1A sa borderom #2A2A2A |
| Accent (CTA, selection) | #F59E0B (amber/gold) |
| Accent hover | #D97706 |
| Tekst primarni | #FFFFFF |
| Tekst sekundarni | #A0A0A0 |
| Success | #10B981 |
| Error | #EF4444 |
| Warning | #F59E0B |
| Font | Inter (headings + body) |
| Border radius | 12px (kartice), 8px (inputi), 999px (pill) |
| Spacing | 24px padding kartice, 16px gap, generous whitespace |

**Mobile:** Full-width koraci. Sticky Order Summary footer sa collapse/expand chevron. Bottom CTA bar fiksiran.

---

## 12. FAZE RAZVOJA — SPRINT PLAN

### Faza 1 — MVP Core (Sprint 0.1–1.5)

**Sprint 0.1: Scaffold**
- Laravel fresh install, Vite multi-entry config
- Tailwind + Alpine setup za public
- Vue 3 + Pinia + Vue Router za admin
- MySQL migracije za SVE tabele
- Seeders: lokacije (2), lockeri, pricing rules, admin user, settings
- Basic Blade layout (public.blade.php) sa header/footer
- Sanctum config

**Sprint 0.2: Public stranice — statičke**
- Homepage (hero, how it works, lokacije kartice, FAQ, blog preview)
- Lokacija show stranica
- FAQ stranica
- Contact, About, Terms, Privacy stranice
- SEO meta + schema markup na svim stranicama
- i18n setup (EN + SR skeleton)
- Responsive na svim stranicama

**Sprint 1.0: Booking flow — frontend**
- Booking stranica (`/locations/{slug}/book`)
- Korak 1: datum, vreme, tip, količina, trajanje — Alpine.js stepper
- Order Summary sa live pricing
- API pozivi: availability + pricing
- Korak 2: Guest forma + OAuth dugmad (UI only, backend u sledećem sprintu)
- Korak 3: Confirm + Book UI
- Mobile responsive + sticky footer

**Sprint 1.1: Booking flow — backend**
- AvailabilityService sa overlap logikom
- PricingService sa duration_key lookup-om
- BookingService: kreiranje, transakciono zaključavanje, locker dodela
- POST /api/bookings endpoint
- Guest auth: POST /api/auth/guest
- Confirmation stranica (Blade)
- Cancellation flow

**Sprint 1.2: TTLock integracija**
- TTLockService implementacija (OAuth2, token refresh)
- createTimedAccessCode, deleteAccessCode, updateAccessCodeTime
- getLockDetail, getLockList, getGatewayList
- Locker sync job (SyncLockerStatus)
- Access code sync job (SyncAccessCodes)
- Gateway sync job (SyncGateways)
- CreateTTLockAccessCode job (na booking potvrdu)
- DeleteTTLockAccessCode job (na cancel/expire)
- ttlock_api_log zapisivanje

**Sprint 1.3: Notifikacije**
- Email: booking confirmed, expiry reminder, expired, cancelled
- Blade email templejti (responsive HTML email)
- WhatsApp Business API integracija
- WhatsApp template poruke
- SendBookingConfirmation job
- SendExpiryReminder job (scheduler: 30 min pre isteka)
- HandleExpiredBookings job (scheduler: svaki minut)
- NotificationLog zapisivanje

**Sprint 1.4: Admin panel — core**
- Vue 3 SPA shell (layout, sidebar, router)
- Auth: login stranica, Sanctum cookie auth
- Dashboard: live locker grid, danas stats, alerts
- Rezervacije: lista, filter, view, mark paid, cancel
- Lockeri: lista po lokaciji, status, remote unlock

**Sprint 1.5: Admin panel — management**
- Lokacije CRUD
- Pricing rules CRUD
- Settings stranica
- User management (super admin only)
- Export CSV

### Faza 2 — Content & SEO (Sprint 2.0–2.2)

**Sprint 2.0:** Blog CRUD, blog public stranice, categories/tags
**Sprint 2.1:** FAQ CRUD, Pages CRUD, near/{slug} programatičke stranice
**Sprint 2.2:** Full srpski prevod, Google Search Console, Analytics, sitemap.xml

### Faza 3 — PWA + OAuth (Sprint 3.0–3.1)

**Sprint 3.0:** manifest.json, Service Worker (Workbox), offline booking view, install prompt
**Sprint 3.1:** Google OAuth, Microsoft OAuth, customer account stranice

### Faza 4 — Online plaćanje (Sprint 4.0–4.1)

**Sprint 4.0:** StripePaymentService, payment form u booking flow-u, webhook handling
**Sprint 4.1:** Refund logika, cancellation sa penalima, self-service produženje

---

## 13. EDGE CASES I PRAVILA

1. **Overbooking prevention:** Transakciono DB zaključavanje (SELECT FOR UPDATE) pri kreiranju bookinga.
2. **Stale availability:** Frontend revalidira dostupnost na serveru pri svakom "Continue" kliku.
3. **TTLock API fail:** Booking se kreira u statusu `confirmed` ali bez PIN-a. Background retry job pokušava 5x sa exponential backoff. Admin alert ako ne uspe.
4. **Dupli booking (isti korisnik):** Dozvoljeno — korisnik može da bukira više lockera u različitim terminima.
5. **Korisnik kasni:** 20 min tolerancija je informativna — PIN važi od check_in do check_out bez obzira.
6. **PIN kolizija:** PIN mora biti unikatan po lockeru među aktivnim bookingima. Generiši random + proveri.
7. **Obrisani PIN ne može se ponovo kreirati sa istim start/end:** Uvek generiši novi PIN pri ponovnom bukiraanju istog slota.
8. **Gateway offline:** Locker se prikazuje kao "offline" u admin panelu. Booking je i dalje moguć (offline PIN fallback). Admin dobija alert.
9. **Baterija niska:** Alert u admin panelu kad locker baterija < 20%.
10. **Timezone:** Sve u `Europe/Belgrade`. Čuva se u UTC u bazi, prikazuje se u lokalnom timezone-u.
