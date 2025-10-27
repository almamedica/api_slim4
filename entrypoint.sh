#!/bin/bash

# Modifica la configuración de Apache para que escuche en el puerto
# definido por la variable de entorno $PORT (provista por Cloud Run).

# 1. Cambiar el puerto en ports.conf
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf

# 2. Cambiar el puerto en la configuración de tu VirtualHost
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# 3. Ejecutar el comando original de la imagen (iniciar Apache)
exec apache2-foreground