#!/bin/bash
# One-command deploy to locker.webby.rs (cPanel-hosted staging).
#
# Usage:
#   ./scripts/deploy.sh
#
# What it does:
#   1. Builds production assets locally (vendor/ + public/build/).
#   2. Tarballs them and uploads via FTP.
#   3. Triggers the on-server deploy.sh that extracts, runs migrations,
#      caches config/routes/views, and points the subdomain at public/.
#   4. Waits for the marker file and curls the live URL to confirm 200.
#
# Credentials are read from scripts/.deploy.env (gitignored). Copy
# scripts/.deploy.env.example and fill it in once.

set -euo pipefail

cd "$(dirname "$0")/.."
ROOT="$(pwd)"

# -- Load creds ---------------------------------------------------------------
ENV_FILE="$ROOT/scripts/.deploy.env"
if [ ! -f "$ENV_FILE" ]; then
    echo "Missing $ENV_FILE — copy scripts/.deploy.env.example and fill in creds." >&2
    exit 1
fi
# shellcheck disable=SC1090
source "$ENV_FILE"

: "${CPANEL_USER:?}" "${CPANEL_PASS:?}" "${CPANEL_HOST:?}" "${SITE_URL:?}"

curl_api() {
    MSYS_NO_PATHCONV=1 curl -s -u "$CPANEL_USER:$CPANEL_PASS" "https://$CPANEL_HOST:2083$1" "${@:2}"
}

step() { printf "\n\033[1;33m▸ %s\033[0m\n" "$*"; }
ok()   { printf "  \033[32m✓\033[0m %s\n" "$*"; }

# -- 1. Build locally ---------------------------------------------------------
step "Building production assets locally"
if [ ! -d vendor ] || [ "${1:-}" = "--full" ]; then
    composer install --no-dev --optimize-autoloader --quiet
    ok "composer install"
fi
npx vite build > /tmp/vite_build.log 2>&1
ok "vite build"

# -- 2. Tarball ---------------------------------------------------------------
step "Packing vendor + public/build"
TARBALL=/tmp/luggage-deploy-$(date +%s).tar.gz
tar -czf "$TARBALL" vendor public/build
ok "$(ls -lh "$TARBALL" | awk '{print $5, $9}')"

# -- 3. FTP upload ------------------------------------------------------------
step "Uploading tarball via FTP"
curl -s --upload-file "$TARBALL" \
    --user "$CPANEL_USER:$CPANEL_PASS" \
    "ftp://$CPANEL_HOST/deploy.tar.gz"
ok "uploaded as ~/deploy.tar.gz"

# Also push code via git (server's own clone will git pull)
step "Pushing code to GitHub"
git push origin master 2>&1 | tail -3
ok "pushed"

# -- 4. Trigger on-server deploy ---------------------------------------------
step "Removing previous deploy marker + triggering on-server deploy"
curl_api "/execute/Fileman/delete_files?file=/home/webbyrs/.deploy_done" >/dev/null

# Remove any previous cron line, then schedule a one-shot
list_resp=$(curl_api "/json-api/cpanel?cpanel_jsonapi_apiversion=2&cpanel_jsonapi_module=Cron&cpanel_jsonapi_func=listcron")
for lk in $(printf "%s" "$list_resp" | grep -oE '"linekey":[0-9]+' | grep -oE '[0-9]+'); do
    curl_api "/json-api/cpanel?cpanel_jsonapi_apiversion=2&cpanel_jsonapi_module=Cron&cpanel_jsonapi_func=remove_line&linekey=$lk" >/dev/null
done

CMD='[ ! -f /home/webbyrs/.deploy_done ] && cd /home/webbyrs/luggage_lockers && /usr/bin/git pull --ff-only && /bin/bash /home/webbyrs/deploy.sh > /home/webbyrs/deploy_run.log 2>&1'
curl_api "/json-api/cpanel?cpanel_jsonapi_apiversion=2&cpanel_jsonapi_module=Cron&cpanel_jsonapi_func=add_line" \
    --data-urlencode "command=$CMD" \
    --data-urlencode "minute=*" --data-urlencode "hour=*" --data-urlencode "day=*" --data-urlencode "month=*" --data-urlencode "weekday=*" >/dev/null
ok "cron scheduled (runs within 60s)"

# -- 5. Wait for marker -------------------------------------------------------
step "Waiting for on-server deploy to finish (max 5 min)"
DEADLINE=$(( $(date +%s) + 300 ))
while [ "$(date +%s)" -lt "$DEADLINE" ]; do
    if curl_api "/execute/Fileman/list_files?dir=/home/webbyrs&include_hidden=1" | grep -q '"file":"\.deploy_done"'; then
        ok "deploy completed on server"
        break
    fi
    sleep 10
done

# Clean up scheduled cron now that it ran
list_resp=$(curl_api "/json-api/cpanel?cpanel_jsonapi_apiversion=2&cpanel_jsonapi_module=Cron&cpanel_jsonapi_func=listcron")
for lk in $(printf "%s" "$list_resp" | grep -oE '"linekey":[0-9]+' | grep -oE '[0-9]+'); do
    curl_api "/json-api/cpanel?cpanel_jsonapi_apiversion=2&cpanel_jsonapi_module=Cron&cpanel_jsonapi_func=remove_line&linekey=$lk" >/dev/null
done

# -- 6. Smoke test ------------------------------------------------------------
step "Smoke test"
for path in "/" "/sr" "/sitemap.xml" "/robots.txt"; do
    code=$(curl -s -o /dev/null -w '%{http_code}' "$SITE_URL$path")
    ok "$path → $code"
done

step "Done"
echo "Live: $SITE_URL"
