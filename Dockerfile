# ==============================================================================
# TAHAP 1: Node.js Asset Builder (Compile Frontend)
# ==============================================================================
FROM node:20-alpine AS asset-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# ==============================================================================
# TAHAP 2: Runtime Production Environment (PHP + Apache resmi Debian)
# ==============================================================================
FROM php:8.4-apache

# 1. Install system dependencies pakai apt-get (karena ini base Debian/Apache)
RUN apt-get update && apt-get install -y \
    git curl unzip zip libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql zip gd exif pcntl

# 2. Ambil Composer resmi
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# 3. Aktifkan mod_rewrite Apache untuk routing Laravel
RUN a2enmod rewrite

# 4. Arahkan Document Root Apache langsung ke folder public Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# 5. Salin source code aplikasi dari repositori
COPY . .

# 6. Ambil hasil build frontend (Vite/Mix) dari Tahap 1
COPY --from=asset-builder /app/public/build ./public/build

# 7. Install dependensi PHP (vendor) untuk Laravel
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-plugins --no-scripts || true

# 8. Setel izin hak akses folder agar Apache bisa jalan lancar
RUN chown -R www-data:www-data /var/www/html \
 && chown -R www-data:www-data /var/www/html/storage \
 && chown -R www-data:www-data /var/www/html/bootstrap/cache \
 && chmod -R 775 /var/www/html/storage \
 && chmod -R 775 /var/www/html/bootstrap/cache