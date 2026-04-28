FROM php:8.3-fpm-alpine

# ── System dependencies ───────────────────────────────────────────────────
RUN apk add --no-cache \
    nginx \
    supervisor \
    nodejs \
    npm \
    curl \
    git \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    mysql-client

# ── PHP Extensions ────────────────────────────────────────────────────────
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    tokenizer \
    xml \
    ctype \
    bcmath \
    gd \
    zip \
    opcache \
    intl \
    pcntl

# ── Composer ─────────────────────────────────────────────────────────────
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# ── App files ─────────────────────────────────────────────────────────────
WORKDIR /var/www/html
COPY . .

# ── Install PHP dependencies (production only) ────────────────────────────
RUN composer install --no-dev --optimize-autoloader --no-interaction

# ── Nginx config ──────────────────────────────────────────────────────────
COPY docker/nginx.conf /etc/nginx/nginx.conf

# ── Supervisord config ────────────────────────────────────────────────────
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ── Entrypoint ────────────────────────────────────────────────────────────
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# ── Permissions ───────────────────────────────────────────────────────────
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
 && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
