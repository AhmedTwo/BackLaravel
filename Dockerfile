FROM php:8.2-apache

# Installer TOUTES les dépendances courantes de Laravel
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libzip-at \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql gd zip

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN a2enmod rewrite
COPY . /var/www/html

# On lance l'installation
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# --- LES DEUX LIGNES CRUCIALES À AJOUTER/VÉRIFIER ---
# 1. On crée le lien symbolique AVANT de changer les permissions
RUN php artisan storage:link

# 2. On donne les droits d'écriture au serveur web sur TOUT le projet, surtout storage
RUN chown -R www-data:www-data /var/www/html && chmod -R 775 /var/www/html/storage
# ----------------------------------------------------

RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD php artisan migrate --force --no-interaction && apache2-foreground