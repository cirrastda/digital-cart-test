FROM php:8.2-fpm

# Dependências do sistema e extensões PHP necessárias para Laravel (SQLite)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl zip unzip libzip-dev libxml2-dev libonig-dev libsqlite3-dev \
    pkg-config $PHPIZE_DEPS \
 && docker-php-ext-install -j$(nproc) mbstring zip pdo_sqlite \
 && docker-php-ext-enable opcache \
 && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY app-entrypoint.sh /usr/local/bin/app-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/app-entrypoint.sh"]
CMD ["php-fpm"]