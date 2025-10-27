# Etapa 1: Instalar dependencias con Composer
FROM composer:2 as vendor
WORKDIR /app
COPY composer.json composer.lock ./
# Instala dependencias (sin dev, ya que el servidor viene de la base)
RUN composer install --no-dev --optimize-autoloader

# ---------------------------------------------------------------------

# Etapa 2: Construir la imagen final de la aplicación
FROM php:8.1-apache

# Instala las extensiones de PHP que necesitas (¡importante para la BD!)
RUN docker-php-ext-install pdo pdo_mysql

# Habilita el módulo de reescritura de Apache
RUN a2enmod rewrite

# Copia nuestro archivo de configuración personalizado de Apache
# (Este archivo todavía dice *:80, pero el entrypoint.sh lo corregirá)
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf

# --- ¡NUEVO! ---
# Copia el script de entrypoint que acabamos de crear
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
# Le da permisos de ejecución
RUN chmod +x /usr/local/bin/entrypoint.sh
# --- FIN NUEVO ---

# Establece el directorio de trabajo
WORKDIR /var/www/html

# Copia las dependencias de la Etapa 1
COPY --from=vendor /app/vendor ./vendor

# Copia todo el código de tu aplicación
COPY . .

# ---> ¡IMPORTANTE! <---
# Copia el .htaccess de la raíz (el que funciona en XAMPP)
#COPY .htaccess /var/www/html/.htaccess

# (Opcional pero recomendado) Ajusta permisos
RUN chown -R www-data:www-data /var/www/html

# --- ¡NUEVO! ---
# Define nuestro script como el punto de entrada.
# Este script se ejecutará en lugar del CMD por defecto de la imagen.
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]