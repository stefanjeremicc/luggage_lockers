# Deploy Guide — Belgrade Luggage Locker

How to put this Laravel + Vue app on a fresh server / staging subdomain.

## 1. Prereqs on the server

- PHP 8.2+ with extensions: `pdo_mysql`, `mbstring`, `xml`, `bcmath`, `gd` (or `imagick`), `intl`, `zip`, `curl`, `fileinfo`, `openssl`
- MySQL 8 (or MariaDB 10.6+)
- Composer 2.x
- Node 20+ and npm 10+
- A web server (Nginx or Apache) configured to serve from `public/`

## 2. First-time deploy

```bash
git clone <repo> /var/www/luggage_lockers
cd /var/www/luggage_lockers

cp .env.example .env
# Edit .env (see "Required env vars" below) before continuing.

composer install --no-dev --optimize-autoloader
npm ci
npm run build                 # builds production assets to public/build/

php artisan key:generate      # only if APP_KEY in .env is empty
php artisan storage:link      # public/storage -> storage/app/public
php artisan migrate --seed    # creates schema + seeds admin, locations, lockers, pricing, settings, pages

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Make sure the web user (www-data, nginx, apache) owns these:
chown -R www-data:www-data storage bootstrap/cache public/storage
chmod -R 775 storage bootstrap/cache
```

After this you can log in at `https://your-subdomain.tld/admin` with the
username `admin` and the password you set in `ADMIN_INITIAL_PASSWORD` (or the
auto-generated one printed by the seeder if you left it blank).

## 3. Required env vars (staging subdomain)

Edit `.env`. The rest can stay at defaults for now.

```ini
APP_ENV=staging
APP_DEBUG=false                            # MUST be false — leaks stack traces otherwise
APP_URL=https://staging.your-domain.tld    # full URL of the subdomain
APP_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=luggage_lockers
DB_USERNAME=...
DB_PASSWORD=...

# Sanctum SPA cookie auth — must list the public host of the admin SPA.
# No scheme, no port, comma-separated for multiple hosts.
SANCTUM_STATEFUL_DOMAINS=staging.your-domain.tld
SESSION_DOMAIN=.your-domain.tld            # leading dot only if sharing with subdomains
SESSION_SECURE_COOKIE=true                 # required on HTTPS

# First-run admin login — set to a strong random string before seeding.
ADMIN_INITIAL_EMAIL=admin@your-domain.tld
ADMIN_INITIAL_PASSWORD=<long-random-string>

# Mail — set to log on staging until real SMTP is wired up.
MAIL_MAILER=log

# Notifications dev-mode — keep on until real customer comms are tested.
NOTIFICATIONS_DEV_MODE=true
DEV_EMAIL_TO=stefan.jeremic@outlook.com
DEV_WHATSAPP_TO=+381...                    # optional

# TTLock — required only if you want lockers to actually unlock.
TTLOCK_CLIENT_ID=...
TTLOCK_CLIENT_SECRET=...
TTLOCK_USERNAME=...
TTLOCK_PASSWORD=...
```

## 4. Web server

### Nginx (preferred)

```nginx
server {
    listen 443 ssl http2;
    server_name staging.your-domain.tld;
    root /var/www/luggage_lockers/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/staging.your-domain.tld/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/staging.your-domain.tld/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Laravel handles robots.txt dynamically; do not let nginx serve a stale static one.
    location = /robots.txt { try_files $uri /index.php?$query_string; }

    client_max_body_size 10M;             # match upload size limit
}
```

Then redirect HTTP → HTTPS in a separate `server` block.

### Apache

The shipped `public/.htaccess` is standard Laravel — works out of the box if
`mod_rewrite` is enabled. Point your VirtualHost `DocumentRoot` at `public/`.

## 5. Subsequent deploys

```bash
cd /var/www/luggage_lockers
git pull --rebase
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart            # if running queue workers
```

## 6. Notes & gotchas

- **`config:cache`** — never run while `APP_DEBUG=true`. After running it, the
  `.env` is no longer read at runtime. Run `php artisan config:clear` if you
  edit `.env` and want changes to take effect immediately.
- **Sessions / queue / cache stores** — all default to `database`, which means
  no Redis required. If you add Redis later, switch `SESSION_DRIVER`,
  `QUEUE_CONNECTION`, and `CACHE_STORE` together.
- **Scheduled tasks** — add this cron entry on the server:
  ```
  * * * * * cd /var/www/luggage_lockers && php artisan schedule:run >> /dev/null 2>&1
  ```
  This runs notification dispatch, locker sync, and other periodic jobs.
- **Queue worker** (only if `QUEUE_CONNECTION=database` or higher) — run via
  systemd or supervisor:
  ```
  php artisan queue:work --sleep=3 --tries=3 --max-time=3600
  ```
- **Hidden features** — Stripe, Cash, Google OAuth, Microsoft OAuth UI are
  intentionally hidden in admin but the backend scaffolding remains. Don't
  delete it; just leave the corresponding env vars empty.
- **Legacy URL redirects** — old Shopify paths (`/pages/*`, `/products/*`,
  `/collections/*`, `/blogs/*`) redirect to the homepage with HTTP 301. See
  `routes/web.php`.

## 7. Smoke test after deploy

```bash
curl -I https://staging.your-domain.tld/                  # 200
curl -I https://staging.your-domain.tld/sr                 # 200
curl -sI https://staging.your-domain.tld/admin             # 200 (returns SPA shell)
curl -s  https://staging.your-domain.tld/sitemap.xml | head
curl -s  https://staging.your-domain.tld/robots.txt        # Sitemap line should point at this host
curl -I https://staging.your-domain.tld/pages/anything     # 301 → /
```
