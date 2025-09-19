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

# Copy entire project for composer scripts that may rely on repository files (eg. bin/console)
COPY . .

# Create non-root user and give ownership to avoid composer disabling plugins when run as root
RUN useradd -m appuser && chown -R appuser:appuser /app

ENV COMPOSER_ALLOW_SUPERUSER=1

USER appuser

# Install PHP dependencies (as non-root user inside the image that has php and composer available)
RUN composer install --no-dev --optimize-autoloader --no-interaction

USER root

RUN mkdir -p var/cache var/log && chown -R www-data:www-data var || true

FROM php:8.2-fpm AS final

RUN apt-get update && apt-get install -y \
    libpq-dev libzip-dev libonig-dev zlib1g-dev \
    && docker-php-ext-install pdo pdo_pgsql zip mbstring opcache

WORKDIR /var/www/html

# Copy application files and installed vendor from build stage
COPY --from=build /app /var/www/html

RUN chown -R www-data:www-data /var/www/html || true

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
