#!/bin/sh
set -e

# Check if $UID and $GID are set, else fallback to default (1000:1000)
USER_ID=${UID:-1000}
GROUP_ID=${GID:-1000}

# Fix file ownership and permissions using the passed UID and GID
echo "Fixing file permissions with UID=${USER_ID} and GID=${GROUP_ID}..."
# chown -R ${USER_ID}:${GROUP_ID} /var/www || echo "Some files could not be changed"
# find /var/www -mindepth 1 \
#   -not -path "/var/www/.git*" \
#   -not -path "/var/www/node_modules*" \
#   -exec chown -R ${USER_ID}:${GROUP_ID} {} +


# Clear configurations to avoid caching issues in development
echo "Clearing configurations..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
# php artisan app:write-build
# Run the default command (e.g., php-fpm or bash)
exec "$@"