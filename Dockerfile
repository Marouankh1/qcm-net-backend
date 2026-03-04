FROM php:8.2-fpm-alpine
WORKDIR /var/www
RUN apk add --no-cache libpq-dev zip unzip git curl 
RUN docker-php-ext-install pdo pdo_pgsql
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY . .
RUN composer install --no-dev --optimize-autoloader

EXPOSE 5174
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=5174"]