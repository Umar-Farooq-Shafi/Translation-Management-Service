FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git zip unzip curl libpng-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install pdo_mysql zip bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

# Opcache recommended settings
RUN { \
  echo 'opcache.enable=1'; \
  echo 'opcache.enable_cli=1'; \
  echo 'opcache.memory_consumption=128'; \
  echo 'opcache.max_accelerated_files=10000'; \
  echo 'opcache.validate_timestamps=0'; \
} > /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

# Copy entire project first (so artisan exists)
COPY . .

# Install dependencies but skip scripts during build
RUN composer install --no-interaction --no-dev --optimize-autoloader --no-scripts

# Create necessary directories and set permissions
RUN mkdir -p storage bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
