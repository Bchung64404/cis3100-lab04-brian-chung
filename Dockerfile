FROM php:8.2-fpm

# Install PDO MySQL extension required for PHP database connections
RUN docker-php-ext-install pdo pdo_mysql