# Stage 1: Frontend build
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# Stage 2: PHP application
FROM php:8.4-fpm-alpine

# 시스템 의존성
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    icu-dev \
    linux-headers \
    $PHPIZE_DEPS

# PHP 확장
RUN docker-php-ext-install \
    pdo_pgsql \
    zip \
    intl \
    pcntl \
    sockets \
    opcache

# MongoDB 확장
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Redis 확장
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 의존성 먼저 (캐시 활용)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# 소스 복사
COPY . .
RUN composer dump-autoload --optimize

# Frontend 빌드 결과 복사
COPY --from=frontend /app/public/build ./public/build

# 퍼미션
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# PHP 설정
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini

EXPOSE 9000
CMD ["php-fpm"]
