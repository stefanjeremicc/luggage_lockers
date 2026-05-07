# Deploy scripts

Two-file system: `deploy.sh` runs locally; the on-server `deploy.sh` (in
`/home/webbyrs/deploy.sh` on the cPanel host) does the install/migrate steps.

## One-command deploy from local

```bash
./scripts/deploy.sh           # incremental — re-uses local vendor/
./scripts/deploy.sh --full    # forces composer install fresh
```

What it does:

1. Build vendor (if missing) and run `npx vite build` locally.
2. Tar `vendor/` + `public/build/` and FTP it to the server as `~/deploy.tar.gz`.
3. `git push origin master` so the server's git clone can pull source code.
4. Schedule the on-server deploy script (one-shot via cron) — which extracts
   the tarball, runs `git pull`, generates APP_KEY if missing, runs
   `migrate --seed --force`, caches config/routes/views, and ensures the
   subdomain symlink is in place.
5. Polls every 10s until the on-server marker `~/.deploy_done` shows up
   (max 5 min).
6. `curl` smoke test on `/`, `/sr`, `/sitemap.xml`, `/robots.txt`.

## Setup (once)

Copy `scripts/.deploy.env.example` to `scripts/.deploy.env` and fill in the
cPanel + site values. The file is gitignored.

```ini
CPANEL_USER=webbyrs
CPANEL_PASS='your-cpanel-password'
CPANEL_HOST=webby.rs
SITE_URL=https://locker.webby.rs
```

## How the on-server script works

`/home/webbyrs/deploy.sh` (uploaded once during initial setup) does:

- Uses `/opt/cpanel/ea-php84/root/usr/bin/php` (PHP 8.4 per-folder via
  `.htaccess`'s `AddHandler` line — does **not** change other sites' PHP
  version).
- Extracts `~/deploy.tar.gz` over the existing repo (`vendor/`, `public/build/`).
- Writes `.env` if not already present.
- Generates `APP_KEY` if blank.
- Runs `php artisan migrate --seed --force` (no-ops if migrations already ran).
- Caches `config`, `routes`, `views`.
- Replaces `/home/webbyrs/locker.webby.rs/` with a symlink to
  `/home/webbyrs/luggage_lockers/public/`.
- Touches `~/.deploy_done` so the local script can detect completion.

Outputs to `~/deploy_run.log` for inspection if anything fails.

## What does NOT change

- Other sites on the same cPanel (`gpc.me`, `sodavoda.com`, `podijum.rs`,
  etc.) keep their existing PHP version and document roots.
- `webbyrs_lockers` MySQL database is the only DB touched.
- No global cron, no global PHP change, no system package install.

## Troubleshooting

- **Smoke test returns 500** — read `~/deploy_run.log` and `storage/logs/laravel.log`
  via cPanel File Manager.
- **Migrations fail** — connect to `webbyrs_lockers` via phpMyAdmin and check
  for partial state. `php artisan migrate:status` (via a one-off cron) lists
  applied vs pending.
- **Site shows directory listing again** — symlink got replaced. Re-run
  `./scripts/deploy.sh`; the on-server script restores it.
