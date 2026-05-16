# Multi-stage build for EqualVoice application
FROM php:8.1-apache as builder

# Install required PHP extensions and system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    pdo \
    pdo_mysql \
    zip \
    mysqli

# Enable Apache modules
RUN a2enmod rewrite \
    && a2enmod headers \
    && a2enmod deflate

# Set Apache document root and permissions
RUN rm -rf /var/www/html/* \
    && mkdir -p /var/www/html

# Copy application files
COPY . /var/www/html/

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && find /var/www/html -type f -name "*.php" -exec chmod 644 {} \;

# Configure PHP
RUN echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/timeouts.ini \
    && echo "display_errors = Off" >> /usr/local/etc/php/conf.d/errors.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/errors.ini

# Configure Apache
COPY ./docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Create logs directory
RUN mkdir -p /var/log/apache2 && chmod 755 /var/log/apache2

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/index.php || exit 1

# Expose port
EXPOSE 80

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
