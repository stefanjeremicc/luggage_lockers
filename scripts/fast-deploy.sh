#!/bin/bash
# Fast asset deploy: build → single tarball upload → PHP-side extract.
# Replaces the slow "594 individual FTP PUTs" loop with ONE upload + one extract.
#
# Usage: ./scripts/fast-deploy.sh [--no-build]
set -euo pipefail
cd "$(dirname "$0")/.."
source scripts/.deploy.env
U="$CPANEL_USER:$CPANEL_PASS"; H="$CPANEL_HOST"
RES="--resolve belgradeluggagelocker.com:443:185.119.89.153"
SITE="https://belgradeluggagelocker.com"

if [ "${1:-}" != "--no-build" ]; then
    echo "▸ vite build…"
    npx vite build > /tmp/vite_build.log 2>&1 && echo "  ✓ build"
fi

echo "▸ packing public/build…"
tar -czf /tmp/ll-build.tar.gz -C public build
echo "  ✓ $(du -h /tmp/ll-build.tar.gz | cut -f1)"

echo "▸ uploading tarball (1 file)…"
curl -s --upload-file /tmp/ll-build.tar.gz --user "$U" "ftp://$H/luggage_lockers/ll-build.tar.gz"
echo "  ✓ uploaded"

echo "▸ extracting + cache-busting on server…"
TOKEN=$(openssl rand -hex 8); R="ext_${TOKEN}.php"
cat > "/tmp/$R" <<PHPEOF
<?php
if ((\$_GET['t'] ?? '') !== '$TOKEN') { http_response_code(403); exit('no'); }
@unlink(__FILE__);
header('Content-Type: text/plain');
\$tar = '/home/webbyrs/luggage_lockers/ll-build.tar.gz';
if (!is_file(\$tar)) { echo "no tarball\n"; exit; }
try {
    \$p = new PharData(\$tar);
    \$p->extractTo('/home/webbyrs/luggage_lockers/public', null, true); // overwrite
    @unlink(\$tar);
    if (function_exists('opcache_reset')) opcache_reset();
    echo "extracted+opcache reset\n";
} catch (\Throwable \$e) { echo "ERR " . \$e->getMessage() . "\n"; }
PHPEOF
curl -s --upload-file "/tmp/$R" --user "$U" "ftp://$H/luggage_lockers/public/$R" >/dev/null
curl -s $RES "$SITE/$R?t=$TOKEN"

echo "▸ live admin asset:"
curl -s $RES "$SITE/admin" | grep -oE 'main-[A-Za-z0-9_-]+\.js' | head -1
echo "done."
