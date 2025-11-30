#!/bin/sh


log() {
  echo "[entrypoint] $1"
}

mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

DB_CONN="sqlite"

if [ ! -f "/var/www/html/database/database.sqlite" ]; then
  log "Creating sqlite database file at database/database.sqlite"
  mkdir -p /var/www/html/database 2>/dev/null || true
  : > /var/www/html/database/database.sqlite
  chmod 666 /var/www/html/database/database.sqlite 2>/dev/null || true
fi

RETRIES=${MIGRATE_RETRIES:-12}
SLEEP=${MIGRATE_SLEEP_SECONDS:-5}
COUNT=0
MIGRATED=0

log "DB connection: $DB_CONN"

while [ $COUNT -lt $RETRIES ]; do
  COUNT=$((COUNT + 1))
  log "Running migrations (attempt $COUNT/$RETRIES)"
  php /var/www/html/artisan migrate --force --no-interaction
  EXIT_CODE=$?
  if [ $EXIT_CODE -eq 0 ]; then
    MIGRATED=1
    log "Migrations executed successfully."
    break
  fi
  log "Migrations failed (exit=$EXIT_CODE). Waiting $SLEEP seconds before retry..."
  sleep $SLEEP
done

if [ $MIGRATED -eq 0 ]; then
  log "Skipping migrations after $RETRIES attempts. Database may be unavailable."
fi

INTERVAL=${MIGRATE_INTERVAL_SECONDS:-600}
if [ "$INTERVAL" -gt 0 ] 2>/dev/null; then
  log "Starting background migration loop every $INTERVAL seconds"
  (
    while true; do
      php /var/www/html/artisan migrate --force --no-interaction >/dev/null 2>&1
      sleep "$INTERVAL"
    done
  ) &
fi

exec "$@"