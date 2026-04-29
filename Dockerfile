FROM node:20-alpine AS assets

WORKDIR /app

COPY package.json ./
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
COPY routes ./routes
COPY app ./app
COPY config ./config
COPY database ./database
COPY bootstrap ./bootstrap
COPY storage ./storage
COPY composer.json ./composer.json
COPY composer.lock ./composer.lock

RUN npm install
RUN npm run build

FROM php:8.4-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Update Apache document root to point to Laravel's public directory
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY . /var/www/html

RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader

COPY --from=assets /app/public/build /var/www/html/public/build

COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]
