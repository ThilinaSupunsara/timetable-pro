# Use the official PHP 8.2 with Apache base image
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# 1. Install system dependencies, PHP, NodeJS AND PYTHON
# මෙතන මම python3, python3-pip, python3-venv අලුතින් එකතු කළා
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libpq-dev \
    nodejs \
    npm \
    python3 \
    python3-pip \
    python3-venv \
    && docker-php-ext-install pdo_pgsql zip exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. --- PYTHON SETUP (NEW PART) ---
# Virtual Environment එකක් හදනවා (Error නැතුව Run වෙන්න)
ENV VIRTUAL_ENV=/opt/venv
RUN python3 -m venv $VIRTUAL_ENV
ENV PATH="$VIRTUAL_ENV/bin:$PATH"

# Google OR-Tools Library එක Install කරනවා
RUN pip install ortools

# 3. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Configure Apache using your custom file
COPY .docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# 5. Copy application code and set correct permissions
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Vendor folder එක තිබුනොත් මකන්න (Fresh install සඳහා)
RUN rm -rf /var/www/html/vendor

# 6. Install Composer (PHP) dependencies
RUN composer install --no-dev --optimize-autoloader

# 7. Install NPM (frontend) dependencies and build assets
RUN npm install
RUN npm run build

# 8. Copy and enable the entrypoint script
COPY .docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
