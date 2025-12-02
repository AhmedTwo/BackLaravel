<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Ce fichier a été généré par la commande artisan.

return new class extends Migration
{
    /**
     * Exécute les migrations (crée la table 'applys').
     * Elle est appelée lorsque vous exécutez php artisan migrate
     */
    public function up(): void
    {
        // Création de la table 'applys'
        Schema::create('applys', function (Blueprint $table) {
            $table->id(); // Colonne ID auto-incrémentée (clé primaire)

            // Clé étrangère vers la table `offers`
            // Le postuler doit être lié à une offre existante
            $table->foreignId('offer_id')
                ->constrained('offers') // Vérifie que l'ID existe dans la table 'offers'
                ->onDelete('cascade'); // Supprime la candidature si l'offre est supprimée

            // Clé étrangère vers la table `users`
            // Le postuler doit être lié à un utilisateur existant
            $table->foreignId('user_id')
                ->constrained('users') // Vérifie que l'ID existe dans la table 'users'
                ->onDelete('cascade'); // Supprime la candidature si l'utilisateur est supprimé

            // Statut de la candidature (par défaut 'pending')
            // Utile pour que la compagnie puisse suivre l'état (en attente, accepté, refusé)
            $table->string('status')->default('pending')->comment('pending, accepted, rejected');

            // Contrainte UNIQUE : Empêche un utilisateur de postuler deux fois à la MÊME offre.
            $table->unique(['offer_id', 'user_id']);

            $table->timestamps(); // Ajoute les colonnes created_at et updated_at
        });
    }

    /**
     * Annule les migrations (supprime la table 'applys').
     * Elle est appelée lorsque vous exécutez php artisan migrate:rollback
     */
    public function down(): void
    {
        Schema::dropIfExists('applys');
    }
};
