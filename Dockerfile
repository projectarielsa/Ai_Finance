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
# TAHAP 2: Runtime Production Environment (PHP + Nginx)
# ==============================================================================
FROM php:8.3-fpm-alpine

# Set Workspace
WORKDIR /var/www/html

# Install Dependency Sistem & Ekstensi PHP yang dibutuhkan Laravel
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    libzip-dev \
    unzip \
    git \
    curl \
    oniguruma-dev \
    nginx \
    supervisor

# Konfigurasi dan install ekstensi PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring zip exif pcntl gd

# Ambil Composer versi resmi terbaru
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Salin source code aplikasi
COPY . .

# Salin aset frontend yang sudah di-build dari Tahap 1
COPY --from=asset-builder /app/public/build ./public/build

# Install dependensi PHP untuk production (tanpa dev-tools agar ringan)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-plugins --no-scripts

# Salin konfigurasi server internal (Nginx & Supervisor)
COPY ./docker/nginx.conf /etc/nginx/nginx.conf
COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Setel izin hak akses folder Storage & Cache agar bisa ditulis oleh sistem Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Buka port 80 untuk lalu lintas web
EXPOSE 80

# Jalankan Supervisor untuk mengelola proses Nginx & PHP-FPM secara bersamaan
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]