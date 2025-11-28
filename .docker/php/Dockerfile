FROM php:8.2-fpm

# Dependências do sistema e extensões PHP necessárias para Laravel
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl zip unzip libzip-dev libxml2-dev libonig-dev mariadb-client \
    pkg-config $PHPIZE_DEPS \
 && docker-php-ext-install -j$(nproc) pdo_mysql mbstring zip \
 && docker-php-ext-enable opcache \
 && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

CMD ["php-fpm"]