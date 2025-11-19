FROM php:8.2-apache

# Instalar extensiones de PHP necesarias
RUN apt-get update && apt-get install -y \
    libicu-dev \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip pdo pdo_mysql intl

# Habilitar mod_rewrite para Apache
RUN a2enmod rewrite

# Configurar el DocumentRoot de Apache
ENV APACHE_DOCUMENT_ROOT /var/www/public

# Actualizar la configuración de Apache
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configurar el directorio de trabajo
WORKDIR /var/www

# Copiar la configuración de Apache para la API
COPY .docker/000-default.conf /etc/apache2/sites-available/000-default.conf

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar los archivos del proyecto
COPY . /var/www/

# Instalar dependencias de PHP
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Asegurarse de que Apache puede escribir en los directorios necesarios
RUN chown -R www-data:www-data /var/www