FROM php:8.4-apache

# Extensiones PHP para Symfony 7.4 + Doctrine sobre MySQL. intl lo pide el
# framework; pdo_mysql es el driver de la base de este demo.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_mysql \
        intl \
        zip \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer (copiado desde la imagen oficial)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache: habilitar rewrite y apuntar el docroot a public/ (convencion Symfony)
RUN a2enmod rewrite
COPY vhost.conf /etc/apache2/sites-available/000-default.conf

# Entrypoint: instala dependencias y prepara la base en el primer arranque.
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

ENTRYPOINT ["entrypoint.sh"]
