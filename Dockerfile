# EqualVoice Application - Dockerfile
# PHP 8.1 + Apache

FROM php:8.1-apache

# Build metadata labels
ARG BUILD_DATE
ARG VCS_REF
ARG BUILD_VERSION
LABEL maintainer="EqualVoice Team" \
      org.label-schema.build-date="${BUILD_DATE}" \
      org.label-schema.vcs-ref="${VCS_REF}" \
      org.label-schema.version="${BUILD_VERSION}"

# ── 1. System dependencies ─────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libzip-dev \
        zip \
        unzip \
        git \
        curl \
        default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# ── 2. PHP extensions ──────────────────────────────────────────────────────────
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        gd \
        pdo \
        pdo_mysql \
        zip \
        mysqli \
        opcache

# ── 3. Apache modules ──────────────────────────────────────────────────────────
RUN a2enmod rewrite headers deflate expires

# ── 4. Copy docker config files FIRST (explicit paths, unaffected by the
#        .dockerignore rules that exclude the docker/ folder from COPY . ) ──────
COPY docker/apache.conf   /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini       /usr/local/etc/php/conf.d/custom.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# ── 5. Application files ───────────────────────────────────────────────────────
RUN rm -rf /var/www/html
COPY . /var/www/html/

# Remove files that must not be served
RUN rm -f  /var/www/html/Dockerfile \
           /var/www/html/docker-compose.yml \
           /var/www/html/.dockerignore \
           /var/www/html/Jenkinsfile \
    && rm -rf /var/www/html/.git \
              /var/www/html/.github \
              /var/www/html/docker

# ── 6. Permissions ─────────────────────────────────────────────────────────────
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

# ── 7. Log directories ─────────────────────────────────────────────────────────
RUN mkdir -p /var/log/apache2 /var/log/php \
    && chmod 755 /var/log/apache2 /var/log/php \
    && touch /var/log/php-error.log \
    && chown www-data:www-data /var/log/php-error.log

# ── 8. Health check ────────────────────────────────────────────────────────────
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/index.php || exit 1

# ── 9. Expose & start ──────────────────────────────────────────────────────────
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
