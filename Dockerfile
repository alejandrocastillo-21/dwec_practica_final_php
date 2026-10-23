FROM php:8.3-apache

# pdo_mysql: la extensión que usa Database.php para hablar con MySQL
# mod_rewrite: lo necesitará el Paso 7 (URLs limpias con .htaccess)
RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite

# Composer dentro del contenedor (opcional, por si quieres ejecutar
# "composer dump-autoload" sin instalarlo en tu máquina)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www