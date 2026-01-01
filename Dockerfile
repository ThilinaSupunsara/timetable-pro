# Use the official PHP 8.2 with Apache base image
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# 1. Install system dependencies & PHP/NodeJS
# මෙතන මම libpq-dev දැම්මා Postgres සඳහා
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libpq-dev \
    nodejs \
    npm \
    && docker-php-ext-install pdo_pgsql zip exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Configure Apache using your custom file
COPY .docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# 4. Copy application code and set correct permissions
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

RUN rm -rf /var/www/html/vendor

# 5. Install Composer (PHP) dependencies
RUN composer install --no-dev --optimize-autoloader

# 6. Install NPM (frontend) dependencies and build assets
RUN npm install
RUN npm run build

# 7. Copy and enable the entrypoint script
COPY .docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
