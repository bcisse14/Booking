#!/usr/bin/env bash
set -e

# Ensure cache/log directories exist and are writable by the web user (always)
mkdir -p /var/www/html/var/cache /var/www/html/var/log || true
chown -R www-data:www-data /var/www/html/var || true
chmod -R 0775 /var/www/html/var || true

# Basic runtime directory setup: create cache/logs and ensure ownership/permissions
mkdir -p /var/www/html/var/cache /var/www/html/var/log || true
chown -R www-data:www-data /var/www/html/var || true
chmod -R 0775 /var/www/html/var || true

# Runtime write test: try to create a small file in var/cache/prod as the web user
echo "[entrypoint] runtime write test (sanity)" >&2 || true
if command -v gosu >/dev/null 2>&1; then
  gosu www-data bash -lc 'mkdir -p var/cache/prod && echo "write-test-$(date +%s)" > var/cache/prod/entrypoint_write_test.txt || true'
else
  mkdir -p /var/www/html/var/cache/prod || true
  echo "write-test-$(date +%s)" > /var/www/html/var/cache/prod/entrypoint_write_test.txt 2>/dev/null || true
fi

# Ensure Doctrine proxy directories exist and are writable
mkdir -p /var/www/html/var/cache/prod/doctrine/orm/Proxies || true
chown -R www-data:www-data /var/www/html/var/cache/prod/doctrine || true
chmod -R 0775 /var/www/html/var/cache/prod/doctrine || true

# Ensure Symfony cache pool directories exist (pools/system) so pool save() calls can create files
mkdir -p /var/www/html/var/cache/prod/pools/system || true
chown -R www-data:www-data /var/www/html/var/cache/prod/pools || true
chmod -R 0775 /var/www/html/var/cache/prod/pools || true

# If DATABASE_URL is present then run cache warmup so that runtime-only operations succeed
if [ -n "$DATABASE_URL" ]; then
  echo "Database url found, warming up cache and running migrations if requested..."
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
