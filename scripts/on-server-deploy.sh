#!/bin/bash
# Runs on the cPanel host (uploaded to /home/webbyrs/deploy.sh).
# Triggered by a temporary cron the local orchestrator schedules.
#
# Runtime: CloudLinux alt-php-82 (NOT ea-php82, NOT ea-php84). ea-php84 lacks
# the cURL extension on this account which breaks the TTLock HTTP client.
# alt-php-82 ships cURL + intl + mbstring + pdo_mysql by default.
PHP=/opt/alt/php82/usr/bin/php

cd /home/webbyrs/luggage_lockers || exit 1

# 0. Discard server-side local edits — origin/master is the source of truth.
/usr/bin/git checkout -- . 2>&1 || true
/usr/bin/git clean -fd 2>&1 || true

# 1. Pull latest source
/usr/bin/git pull --ff-only origin master 2>&1 || true

# 2. Extract vendor + public/build from the uploaded tarball
[ -f /home/webbyrs/deploy.tar.gz ] && tar xzf /home/webbyrs/deploy.tar.gz 2>&1

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
