FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git libicu-dev libonig-dev libxml2-dev curl \
    && docker-php-ext-install pdo pdo_mysql intl opcache

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

# Set the working directory
WORKDIR /var/www/html

# Copy your app files (optional — if you don’t use volumes)
# COPY . /var/www/html
