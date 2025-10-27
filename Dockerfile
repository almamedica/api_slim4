# Etapa 1: Instalar dependencias con Composer
FROM composer:2 as vendor
WORKDIR /app
COPY composer.json composer.lock ./
# Instala dependencias. Usamos --no-dev porque el servidor ya viene en la imagen base
RUN composer install --no-dev --optimize-autoloader

# ---------------------------------------------------------------------

# Etapa 2: Construir la imagen final de la aplicación
FROM php:8.1-apache

# Instala las extensiones de PHP que necesitas (¡importante para la BD!)
RUN docker-php-ext-install pdo pdo_mysql

# Habilita el módulo de reescritura de Apache
RUN a2enmod rewrite

# Copia nuestro archivo de configuración de Apache personalizado
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf

# Establece el directorio de trabajo
WORKDIR /var/www/html

# Copia las dependencias de la Etapa 1
COPY --from=vendor /app/vendor ./vendor

# Copia todo el código de tu aplicación
COPY . .

# ¡Importante! Establece la carpeta 'public' como la raíz del servidor
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# (Opcional pero recomendado) Ajusta permisos
RUN chown -R www-data:www-data /var/www/html