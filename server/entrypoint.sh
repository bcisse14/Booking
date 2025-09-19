#!/usr/bin/env bash
set -e

# Ensure cache/log directories exist and are writable by the web user (always)
mkdir -p /var/www/html/var/cache /var/www/html/var/log || true
chown -R www-data:www-data /var/www/html/var || true
chmod -R 0775 /var/www/html/var || true

# Diagnostic prints to help debug permission issues during startup
echo "[entrypoint] starting at $(date)" >&2 || true
echo "[entrypoint] whoami: $(whoami)" >&2 || true
echo "[entrypoint] id: $(id)" >&2 || true
echo "[entrypoint] /var/www/html permissions:" >&2 || true
ls -la /var/www/html >&2 || true
echo "[entrypoint] /var/www/html/var permissions:" >&2 || true
ls -la /var/www/html/var >&2 || true
echo "[entrypoint] detailed stat for cache dirs:" >&2 || true
stat -c "%n -> %U:%G mode=%a" /var/www/html/var || true
stat -c "%n -> %U:%G mode=%a" /var/www/html/var/cache || true
stat -c "%n -> %U:%G mode=%a" /var/www/html/var/cache/prod || true
echo "[entrypoint] www-data info:" >&2 || true
getent passwd www-data || true
id www-data || true

# TEMP: make cache world-writable to detect permission-related issues (diagnostic only)
echo "[entrypoint] making /var/www/html/var/cache world-writable (temporary)" >&2 || true
chmod -R 0777 /var/www/html/var/cache || true

# Runtime write test: try to create a small file in var/cache/prod as the web user
echo "[entrypoint] runtime write test: attempting to write as www-data into /var/www/html/var/cache/prod" >&2 || true
if command -v gosu >/dev/null 2>&1; then
  # Run a shell as www-data to attempt the write and show results
  gosu www-data bash -lc 'set -x; mkdir -p var/cache/prod && echo "write-test-$(date +%s)" > var/cache/prod/entrypoint_write_test.txt && echo "WROTE_OK" || echo "WRITE_FAILED"'
else
  echo "[entrypoint] gosu not found; attempting write as current user" >&2 || true
  mkdir -p /var/www/html/var/cache/prod || true
  if echo "write-test-$(date +%s)" > /var/www/html/var/cache/prod/entrypoint_write_test.txt 2>/dev/null; then
    echo "WROTE_OK"
  else
    echo "WRITE_FAILED"
  fi
fi

# Ensure Doctrine proxy directories exist and are writable
echo "[entrypoint] ensuring Doctrine proxy directories exist and are writable" >&2 || true
mkdir -p /var/www/html/var/cache/prod/doctrine/orm/Proxies || true
chown -R www-data:www-data /var/www/html/var/cache/prod/doctrine || true
chmod -R 0775 /var/www/html/var/cache/prod/doctrine || true
stat -c "%n -> %U:%G mode=%a" /var/www/html/var/cache/prod/doctrine/orm/Proxies || true

# Ensure Symfony cache pool directories exist (pools/system) so pool save() calls can create files
echo "[entrypoint] ensuring Symfony cache pools directory exists and is writable" >&2 || true
mkdir -p /var/www/html/var/cache/prod/pools/system || true
chown -R www-data:www-data /var/www/html/var/cache/prod/pools || true
chmod -R 0775 /var/www/html/var/cache/prod/pools || true
stat -c "%n -> %U:%G mode=%a" /var/www/html/var/cache/prod/pools/system || true

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
