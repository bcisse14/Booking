#!/usr/bin/env bash
set -e

# If DATABASE_URL is present then run cache warmup so that runtime-only operations succeed
if [ -n "$DATABASE_URL" ]; then
  echo "Database url found, warming up cache and running migrations if requested..."
  # Ensure cache/log directories exist and are writable by the web user
  mkdir -p /var/www/html/var/cache /var/www/html/var/log || true
  chown -R www-data:www-data /var/www/html/var || true
  chmod -R 0775 /var/www/html/var || true
  cd /var/www/html
  # Run cache warmup in prod environment
  APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup || true
  # Optionally run migrations if MIGRATE_ON_START is set
  if [ "$MIGRATE_ON_START" = "1" ]; then
    php bin/console doctrine:migrations:migrate --no-interaction || true
  fi
else
  echo "DATABASE_URL not set, skipping cache warmup and migrations."
fi

# Start the built-in server as www-data
exec gosu www-data php -S 0.0.0.0:8000 -t public
