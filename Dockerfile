# syntax=docker/dockerfile:1
# LigtasAlertWEB — Render custom-Docker deployment
# Laravel 13.37 requires PHP ^8.3; PHP 8.4 is in range (audit I-1).
FROM php:8.4-fpm-alpine

# --- Environment defaults. The Render dashboard overrides these; never
# commit secrets here. APP_DEBUG must stay false in production.
ENV APP_ENV=production \
    APP_DEBUG=false \
    PATH="/root/.composer/vendor/bin:${PATH}"

# --- System packages ---------------------------------------------------------
# libxml/libpng/gzip: build PHP extensions. nginx: reverse proxy + static
# assets. curl/rsync: health checks and asset sync. git: composer dist fallbacks.
RUN apk add --no-cache \
        nginx \
        curl \
        rsync \
        git \
        libxml2 \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
        zlib-dev \
        freetype-dev \
        libjpeg-turbo-dev \
    && rm -rf /var/cache/apk/*

# --- PHP extensions ----------------------------------------------------------
# Runtime extensions required by composer.lock (audit I-2), plus pdo_pgsql
# (mandatory for Supabase; not declared by any package) and gd (image handling).
RUN docker-php-ext-install \
        pdo \
        pdo_pgsql \
        mbstring \
        xml \
        dom \
        tokenizer \
        ctype \
        json \
        hash \
        openssl \
        filter \
        fileinfo \
        iconv \
        pcre \
        gd \
    && docker-php-ext-enable \
        pdo_pgsql

# --- Composer ----------------------------------------------------------------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer set config --global repo-packages.dist dist

# --- Node + frontend build ---------------------------------------------------
# package-lock.json must be present (audit R3) so `npm ci` is reproducible.
# The bunny() font helper in vite.config.js downloads fonts at build time,
# so network access is required here (audit I-3).
COPY --chown=www-data:www-data package.json package-lock.json ./
RUN npm ci --no-audit --no-fund \
    && npm run build

# --- Application -------------------------------------------------------------
# .dockerignore (audit R2) keeps vendor, node_modules, tests, .env, and
# compiled views out of the build context.
COPY --chown=www-data:www-data . /var/www/html
WORKDIR /var/www/html

# --- Composer install (production only) --------------------------------------
# --no-dev: excludes the test/tooling tree. --no-scripts: skips post-install
# Artisan hooks that assume a bootable environment. --optimize-autoloader:
# classmap optimization for production.
RUN composer install --no-dev --no-interaction --no-scripts --optimize-autoloader \
    && rm -rf /root/.composer/cache

# --- nginx configuration -----------------------------------------------------
# LISTEN_PORT is substituted at container start from Render's $PORT.
# nginx is the ONLY trusted reverse proxy (audit §3.4): it sets
# X-Forwarded-Proto=https, X-Forwarded-For, and X-Forwarded-Host, and serves
# public/build/ statically. bootstrap/app.php trusts 127.0.0.1 only.
COPY --chown=www-data:www-data docker/nginx.conf /etc/nginx/nginx.conf
COPY --chown=www-data:www-data docker/php-fpm-pool.conf /etc/php/8.4/fpm/pool.d/www.conf

# --- Runtime -----------------------------------------------------------------
# Startup runs config/route/view caching from the LIVE environment so cached
# values match the deployed env. Migrations are deliberately NOT run here
# (audit §4): Supabase is already migrated, and an automatic migrate at
# startup risks destructive changes if a migration is miswritten.
# The maintenance.php guard in public/index.php is respected.
COPY --chown=www-data:www-data docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Health check: Laravel's /up endpoint. curl is installed (line 17).
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -fsS "http://localhost:${PORT:-8080}/up" >/dev/null || exit 1

EXPOSE 8080
USER www-data
ENTRYPOINT ["entrypoint.sh"]