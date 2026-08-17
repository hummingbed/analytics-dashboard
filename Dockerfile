FROM node:24-alpine AS frontend

WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm ci && npm run build

FROM php:8.2-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip librdkafka-dev libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && pecl install rdkafka \
    && docker-php-ext-enable rdkafka \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-interaction --prefer-dist --optimize-autoloader
COPY --from=frontend /app/public/build ./public/build

EXPOSE 8000 8080
