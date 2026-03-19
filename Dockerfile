FROM php:8.2-apache

# 1. Installer les dépendances système (Zip est requis par Composer)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql

# 2. Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Activer le module de réécriture Apache
RUN a2enmod rewrite

# 4. Copier les fichiers du projet
COPY . /var/www/html

# 5. Installer les dépendances Laravel (Le fameux dossier vendor)
RUN composer install --no-dev --optimize-autoloader

# 6. Configuration Apache pour pointer sur /public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# 7. Droits d'écriture
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

# Commande de lancement : on force la migration au démarrage (gratuit !)
CMD php artisan migrate --force && apache2-foreground