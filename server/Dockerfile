FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev libonig-dev curl zlib1g-dev \
    && docker-php-ext-install pdo pdo_pgsql zip mbstring opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for better caching
COPY composer.json composer.lock ./
# Allow composer scripts to run as root inside the container (needed for CI/container builds)
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install symfony CLI so composer auto-scripts (symfony-cmd) are available
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony || true

RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy app
COPY . .

RUN mkdir -p var/cache var/log && chown -R www-data:www-data var || true

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
