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

# Copy your project files into the container's web root
COPY . /var/www/html/

# Set permissions for the uploads folder (adjust if needed)
RUN chown -R www-data:www-data /var/www/html/uploads

# Expose port 80
EXPOSE 80