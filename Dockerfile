FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libssl-dev \
    pkg-config

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mysqli mbstring exif pcntl bcmath gd

# Install MongoDB extension
RUN pecl install mongodb-1.16.0 && docker-php-ext-enable mongodb

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Install MongoDB PHP library
COPY composer.json /var/www/html/
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Copy application files
COPY scripts/ /var/www/html/

# Create config directory and copy database config
RUN mkdir -p /var/www/html/config
COPY scripts/config/database.php /var/www/html/config/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80 