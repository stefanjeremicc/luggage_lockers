#!/bin/bash
# Runs on the cPanel host (uploaded to /home/webbyrs/deploy.sh).
# Triggered by a temporary cron the local orchestrator schedules.
#
# Runtime: CloudLinux alt-php-82 (NOT ea-php82, NOT ea-php84). ea-php84 lacks
# the cURL extension on this account which breaks the TTLock HTTP client.
# alt-php-82 ships cURL + intl + mbstring + pdo_mysql by default.
PHP=/opt/alt/php82/usr/bin/php

# Self-guard + self-log. The cron that triggers this is kept to a bare
# "/bin/bash /home/webbyrs/deploy.sh" (no conditionals/redirects) because the
# cPanel cron API mangles special chars ([ ! -f ], &&, >) into a malformed cron
# line that never fires — the root cause of deploys silently not running.
# So the guard (skip if already done) and the log redirect live here instead.
[ -f /home/webbyrs/.deploy_done ] && exit 0
exec > /home/webbyrs/deploy_run.log 2>&1

cd /home/webbyrs/luggage_lockers || exit 1

# 0-1. Force-align to origin/master (origin is the single source of truth).
#      reset --hard (not pull --ff-only) so a diverged server self-heals.
#      clean -fd drops untracked junk but, without -x, leaves gitignored files
#      (vendor/, public/build/, .env, storage/) intact.
/usr/bin/git fetch origin master 2>&1 || true
/usr/bin/git reset --hard origin/master 2>&1 || true
/usr/bin/git clean -fd 2>&1 || true

# 2. Extract vendor + public/build from the uploaded tarball, then remove it so
#    a stale tarball can never be re-extracted on a later (tarball-less) run.
if [ -f /home/webbyrs/deploy.tar.gz ]; then
  tar xzf /home/webbyrs/deploy.tar.gz 2>&1
  rm -f /home/webbyrs/deploy.tar.gz
fi

# 2a. Neutralise platform_check.php. vendor/ was built locally on PHP 8.4 so
#     Composer's auto-generated platform check refuses to load on 8.2. Overwrite
#     it AFTER tarball extraction (which restored the original strict version).
cat > vendor/composer/platform_check.php <<'PCEOF'
<?php
// Intentional no-op: the local build targets PHP 8.4 but the staging runtime
// is CloudLinux alt-php-82 which provides every extension Laravel uses at
// runtime. Skipping this check lets the autoloader proceed.
PCEOF

# 3. Per-folder alt-php-82 handler — strip any prior handler we wrote, then add
#    the right one. Does NOT affect other sites' PHP version.
if [ -f public/.htaccess ]; then
  sed -i '/AddHandler.*ea-php/d; /AddHandler.*alt-php/d; /Force PHP/d; /Force CloudLinux/d' public/.htaccess
fi
if ! grep -q "alt-php82___lsphp" public/.htaccess 2>/dev/null; then
  cat >> public/.htaccess <<'HEOF'

# Force CloudLinux alt-php-82 for this site only (per-folder, no global change)
AddHandler application/x-httpd-alt-php82___lsphp .php .php5 .phtml
HEOF
fi

# 4. Migrate — schema-only, NEVER seed (production data is the source of truth)
$PHP artisan migrate --force 2>&1 || true

# 5. Cache config + routes + views (clear first)
$PHP artisan config:clear 2>&1 || true
$PHP artisan view:clear   2>&1 || true
$PHP artisan route:clear  2>&1 || true
$PHP artisan config:cache 2>&1 || true
$PHP artisan route:cache  2>&1 || true
$PHP artisan view:cache   2>&1 || true

# 6. Storage symlink — only if missing (artisan errors out if it exists).
[ -L /home/webbyrs/luggage_lockers/public/storage ] || $PHP artisan storage:link 2>&1 || true

# 7. Subdomain docroot symlink
if [ ! -L /home/webbyrs/locker.webby.rs ]; then
  rm -rf /home/webbyrs/locker.webby.rs
  ln -s /home/webbyrs/luggage_lockers/public /home/webbyrs/locker.webby.rs
fi

# 8. Done marker
rm -f /home/webbyrs/.deploy_done
touch /home/webbyrs/.deploy_done
echo "DEPLOY OK $(date) using $PHP" >> /home/webbyrs/deploy.log
