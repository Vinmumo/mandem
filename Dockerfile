FROM node:22-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js ./
COPY public ./public
RUN npm run build

FROM composer:2 AS dependencies
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --optimize-autoloader

FROM php:8.4-cli-alpine
RUN apk add --no-cache libpq-dev curl && docker-php-ext-install pdo_pgsql
WORKDIR /var/www/html
COPY . .
COPY --from=dependencies /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build
RUN chmod -R 775 storage bootstrap/cache
CMD php artisan package:discover --ansi && php artisan migrate --force && php artisan optimize && (php artisan schedule:work &) && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
