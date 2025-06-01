FROM php:8.1-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    curl \
    libicu-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libzip-dev \
    libc-client-dev \
    libkrb5-dev \
    mariadb-client \
    unzip \
    wget \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install gd intl mysqli pdo pdo_mysql zip xml calendar imap

# Instalar pcov para cobertura de código
RUN pecl install pcov && docker-php-ext-enable pcov

# Activar rewrite para Apache
RUN a2enmod rewrite

# Configuración adicional de Apache para Dolibarr
RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/dolibarr.conf \
    && a2enconf dolibarr

# Copiar el código de la app al contenedor
COPY . /var/www/html/


WORKDIR /var/www/html

# Crear el directorio de documentos y ajustar permisos
RUN mkdir -p /var/www/html/documents && \
    chown -R www-data:www-data /var/www/html/documents && \
    chmod -R 755 /var/www/html/documents

# Ajustar permisos generales
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Instalar Composer manualmente
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Enlace simbólico adicional para garantizar acceso desde cualquier shell
RUN ln -s /usr/local/bin/composer /bin/composer

# Instalar PHPUnit globalmente desde archivo .phar
RUN wget -O /usr/local/bin/phpunit https://phar.phpunit.de/phpunit-9.phar && \
    chmod +x /usr/local/bin/phpunit

# Activar cobertura con pcov si se desea
ENV PCOV_ENABLE=1

EXPOSE 80
