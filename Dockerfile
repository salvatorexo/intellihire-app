# Use an official PHP image with Apache
FROM php:8.2-apache

# Install system dependencies for PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo pdo_mysql gd zip

# Enable Apache modules for .htaccess support
RUN a2enmod rewrite

# ⚠️ Disable OPcache revalidation delay so updated PHP files run immediately
RUN echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.file_update_protection=0" >> /usr/local/etc/php/conf.d/opcache.ini

# ⚠️ Cache-buster — increment to force a fresh Docker build
ARG CACHE_BUST=2026-10-12-003

# Copy project files into the container's web root
COPY . /var/www/html/

# Set permissions for uploads folder
RUN mkdir -p /var/www/html/uploads/cvs /var/www/html/uploads/logos \
    && chown -R www-data:www-data /var/www/html/uploads

# Expose port 80
EXPOSE 80