FROM php:8.4-fpm-bookworm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    nginx \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install pdo_pgsql bcmath intl opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-app.conf

WORKDIR /var/www/html

COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
    && npm ci \
    && npm run build

RUN chmod +x docker/entrypoint.sh \
    && groupadd -g 1000 appuser \
    && useradd -u 1000 -g appuser -m appuser \
    && chown -R appuser:appuser storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

USER appuser

ENTRYPOINT ["sh", "docker/entrypoint.sh"]
