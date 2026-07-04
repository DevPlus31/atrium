#!/bin/sh
# Production entrypoint. Shared by the app, horizon and scheduler services.
#
# Env toggles (set per compose service):
#   WAIT_FOR_DB    (default: true)  wait until the database accepts connections
#   RUN_MIGRATIONS (default: true)  php artisan migrate --force (app service only;
#                                   horizon/scheduler set this to false)
#   RUN_OPTIMIZE   (default: true)  rebuild config/route/view/event caches
set -e

cd /app

# The storage/app tree may be a freshly-created named volume — make sure the
# directory skeleton Laravel expects is present.
mkdir -p \
    storage/app/public \
    storage/app/private \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

if [ "${WAIT_FOR_DB:-true}" = "true" ]; then
    echo "entrypoint: waiting for database..."
    tries=0
    until php artisan db:show >/dev/null 2>&1; do
        tries=$((tries + 1))
        if [ "$tries" -ge 30 ]; then
            echo "entrypoint: database not reachable after ${tries} attempts, giving up." >&2
            exit 1
        fi
        sleep 2
    done
    echo "entrypoint: database is up."
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force

    # Added by a parallel workstream — run it once it exists, no-op until then.
    if php artisan list --raw | grep -q admin:sync-permissions; then
        php artisan admin:sync-permissions
    fi
fi

if [ "${RUN_OPTIMIZE:-true}" = "true" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
fi

exec "$@"
