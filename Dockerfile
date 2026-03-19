FROM php:8.2-apache

# Installer TOUTES les dépendances courantes de Laravel
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql gd zip

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN a2enmod rewrite
COPY . /var/www/html

# On lance l'installation en ignorant la plateforme si besoin (plus robuste)
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD php artisan migrate:fresh --force --no-interaction && apache2-foreground