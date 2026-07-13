FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libssl-dev \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Enable apache modules
RUN a2enmod rewrite headers

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy Apache configuration
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Copy Composer files
COPY composer.json ./

# Install dependencies
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Copy application files
COPY . .

# Fix CRLF carriage returns for Unix environment compatibility and set executable flag
RUN sed -i 's/\r$//' start.sh && chmod +x start.sh

# Adjust permissions for Apache
RUN chown -R www-data:www-data /var/www/html

# Default documentation port
EXPOSE 80

# Configure startup entrypoint
ENTRYPOINT ["/var/www/html/start.sh"]
