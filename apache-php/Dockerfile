FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libzip-dev zip unzip \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite headers expires \
    && apt-get clean

COPY apache.conf /etc/apache2/sites-available/000-default.conf
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80
