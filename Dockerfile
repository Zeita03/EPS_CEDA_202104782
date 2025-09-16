# Usar imagen oficial de PHP con Apache
FROM php:7.4-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo pdo_mysql mysqli zip intl mbstring xml

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Configurar el DocumentRoot de Apache
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos del proyecto
COPY . /var/www/html

# Instalar dependencias de Composer
RUN composer install --no-dev --optimize-autoloader

# Crear directorios necesarios y establecer permisos
RUN mkdir -p /var/www/html/data/cache \
    && mkdir -p /var/www/html/archivos \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/data \
    && chmod -R 777 /var/www/html/data/cache \
    && chmod -R 777 /var/www/html/archivos

# Exponer puerto 80
EXPOSE 80

# Comando por defecto
CMD ["apache2-foreground"]
