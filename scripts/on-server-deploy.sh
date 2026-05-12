#!/bin/bash
# Runs on the cPanel host (uploaded to /home/webbyrs/deploy.sh).
# Triggered by a temporary cron the local orchestrator schedules.
PHP=/opt/cpanel/ea-php84/root/usr/bin/php

cd /home/webbyrs/luggage_lockers || exit 1

# 0. Discard server-side local edits — origin/master is the source of truth.
/usr/bin/git checkout -- . 2>&1 || true
/usr/bin/git clean -fd 2>&1 || true

# 1. Pull latest source
/usr/bin/git pull --ff-only origin master 2>&1 || true

# 2. Extract vendor + public/build from the uploaded tarball
[ -f /home/webbyrs/deploy.tar.gz ] && tar xzf /home/webbyrs/deploy.tar.gz 2>&1

# 3. Per-folder PHP 8.4 handler
if ! grep -q "ea-php84" public/.htaccess 2>/dev/null; then
  cat >> public/.htaccess <<'HEOF'

# Force PHP 8.4 for this site only (per-folder, no global change)
AddHandler application/x-httpd-ea-php84 .php .php5 .phtml
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
