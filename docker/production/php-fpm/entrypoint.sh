#!/bin/sh
set -eu

cd /var/www

# Ensure writable runtime dirs
mkdir -p bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Initialize storage directory if empty
if [ -z "$(ls -A /var/www/storage 2>/dev/null)" ]; then
  echo "Initializing storage directory..."
  cp -R /var/www/storage-init/. /var/www/storage 2>/dev/null || true
  chown -R www-data:www-data /var/www/storage || true
fi

# Remove storage-init directory (if it exists)
rm -rf /var/www/storage-init 2>/dev/null || true


# Helper to run artisan only if available
run_artisan() {
  if [ -f artisan ]; then
    php artisan "$@"
  fi
}

# (Optional) Wait for DB socket/port if DB_HOST is provided
if [ -n "${DB_HOST:-}" ]; then
  echo "Waiting for database ${DB_HOST}:${DB_PORT:-3306}..."
  i=0
  until php -r '
    $h=getenv("DB_HOST")?: "db";
    $p=getenv("DB_PORT")?: "3306";
    $t=@fsockopen($h,(int)$p,$e,$s,1.0);
    if($t){fclose($t); exit(0);} exit(1);
  '; do
    i=$((i+1))
    [ $i -ge 60 ] && echo "DB wait timeout, continuing..." && break
    sleep 1
  done
fi

# Laravel prep
run_artisan storage:link || true

# Always clear old caches first to ensure fresh env is read
run_artisan optimize:clear || true

# Run migrations only for non-local environments
APP_ENV_EFF="${APP_ENV:-production}"
if [ "$APP_ENV_EFF" != "local" ]; then
  run_artisan migrate --force || true
fi

# Rebuild caches using current runtime env
run_artisan config:cache || true
run_artisan route:cache || true
run_artisan view:cache  || true
run_artisan event:cache || true

# Write build metadata (uses config('app.timezone'))
# run_artisan app:write-build || true

# Hand off to the final process (e.g., php-fpm)
exec "$@"