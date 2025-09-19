FROM php:8.2-fpm AS build

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev libonig-dev curl zlib1g-dev \
    && docker-php-ext-install pdo pdo_pgsql zip mbstring opcache

# Get composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install symfony CLI so composer auto-scripts (symfony-cmd) are available
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony || true

# Copy only the server/ directory into the build context so composer.json is available at /app
# This Dockerfile is executed with repository root as the build context.
COPY ./server/ .

# Create non-root user and give ownership to avoid composer disabling plugins when run as root
RUN useradd -m appuser && chown -R appuser:appuser /app

ENV COMPOSER_ALLOW_SUPERUSER=1

USER appuser

# Install PHP dependencies (as non-root user inside the image that has php and composer available)
# Run composer in production mode and skip running scripts during the build so runtime actions
# (cache:clear, migrations, etc.) which require runtime env vars are not executed during image build.
RUN APP_ENV=prod APP_DEBUG=0 composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

USER root

RUN mkdir -p var/cache var/log && chown -R www-data:www-data var || true

FROM php:8.2-fpm AS final

RUN apt-get update && apt-get install -y \
    libpq-dev libzip-dev libonig-dev zlib1g-dev \
    && docker-php-ext-install pdo pdo_pgsql zip mbstring opcache

# Install gosu for dropping privileges when starting the server
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends ca-certificates wget gnupg2; \
    wget -O /usr/local/bin/gosu "https://github.com/tianon/gosu/releases/download/1.14/gosu-amd64"; \
    chmod +x /usr/local/bin/gosu

WORKDIR /var/www/html

# Copy application files and installed vendor from build stage
COPY --from=build /app /var/www/html

# Copy entrypoint that will run runtime tasks (cache warmup) when env vars are available
# When build context is repository root the entrypoint lives under server/entrypoint.sh
COPY ./server/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

RUN chown -R www-data:www-data /var/www/html || true

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
