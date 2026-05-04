FROM php:8.2-cli

RUN apt-get update \
    && apt-get install -y libsqlite3-dev git unzip \
    && docker-php-ext-install pdo_sqlite \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy all files first
COPY . .

# Then install PHP dependencies
RUN composer install --no-interaction --no-scripts
