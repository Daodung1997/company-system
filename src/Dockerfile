FROM php:8.4-apache

# Install system dependencies and required PHP extensions for Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd

# Enable Apache rewrite module for Laravel routing
RUN a2enmod rewrite

# Change Apache document root to Laravel public directory
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/var/www/html/public/!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set container working directory
WORKDIR /var/www/html

# Copy all backend application source files into the container
COPY . .

# Install Composer packages
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set correct read/write permissions for Laravel storage and cache directories
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Make docker-entrypoint.sh executable
RUN chmod +x docker-entrypoint.sh

# Expose HTTP port 80
EXPOSE 80

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]

# Run Apache in the foreground
CMD ["apache2-foreground"]
