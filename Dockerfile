# Production image for Render (or any Docker host). Database and product
# images live in Supabase; this container only runs Laravel + built assets.

# 1) Front-end assets (Vite + Tailwind)
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# 2) Runtime: PHP 8.2 + Apache
FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libjpeg62-turbo-dev libpng-dev libwebp-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql gd zip opcache \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY ops/docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY ops/docker/apache-site.conf /etc/apache2/sites-available/000-default.conf
COPY ops/docker/servername.conf /etc/apache2/conf-available/servername.conf
RUN a2enmod rewrite headers && a2enconf servername

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && php artisan package:discover --ansi \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x ops/docker/entrypoint.sh

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=8080

EXPOSE 8080
ENTRYPOINT ["ops/docker/entrypoint.sh"]
CMD ["apache2-foreground"]
