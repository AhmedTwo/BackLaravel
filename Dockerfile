FROM php:8.2-apache

# Installer les dépendances système nécessaires
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

# Activer le module de réécriture Apache
RUN a2enmod rewrite

# Copier les fichiers du projet
COPY . /var/www/html

# Installer les dépendances PHP (ignore les erreurs de plateforme pour Render)
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# --- CONFIGURATION CRUCIALE POUR LES PHOTOS ---
# Créer le lien symbolique pour le stockage
RUN php artisan storage:link || true

# Donner les droits d'écriture à Apache sur les dossiers nécessaires
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
# -----------------------------------------------

# Pointer Apache vers le dossier /public de Laravel
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

EXPOSE 80

# Lancer les migrations et Apache
CMD php artisan migrate --force --no-interaction && apache2-foreground