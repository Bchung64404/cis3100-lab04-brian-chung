FROM php:8.2-fpm

RUN docker-php-ext-install pdo_mysql

WORKDIR /var/www
COPY www/ /var/www/html/
COPY database/ /var/www/database/
COPY scripts/ /var/www/scripts/

CMD ["sh", "-c", "php /var/www/scripts/migrate.php --check && exec php-fpm"]
