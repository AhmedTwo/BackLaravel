<?php

namespace Database\Seeders;

use App\Models\Company; // Importation du modèle Company
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Company::create([
            'name'                => "Innovatech Solutions",
            'logo'                => "/public/storage/photo_company/téléchargement.jpeg",
            'number_of_employees' => 45,
            'industry'            => "Logiciels / SaaS",
            'address'             => "12 Rue de la Paix, 75002 Paris",
            'latitude'            => 48.8687,  // Coordonnées approximatives de Paris
            'longitude'           => 2.3332,
            'description'         => "Startup spécialisée dans les solutions d'IA pour l'optimisation logistique. Environnement de travail agile et stimulant.",
            'email_company'       => "contact@innovatech.fr",
            'n_siret'             => "85214796300025",
            'status'              => "en attente",
        ]);

        Company::create([
            'name'                => "Global Finance Partners",
            'logo'                => "/public/storage/photo_company/téléchargement.jpeg",
            'number_of_employees' => 1200,
            'industry'            => "Finance / Banque",
            'address'             => "24 Avenue des Lumières, 69009 Lyon",
            'latitude'            => 45.7745, // Coordonnées approximatives de Lyon
            'longitude'           => 4.8290,
            'description'         => "Institution financière de premier plan offrant une gamme complète de services bancaires et d'investissement.",
            'email_company'       => "jobs@globalfinance.com",
            'n_siret'             => "45874521300058",
            'status'              => "en attente",
        ]);

        Company::create([
            'name'                => "EzyShop Express",
            'logo'                => "/public/storage/photo_company/téléchargement.jpeg",
            'number_of_employees' => 150,
            'industry'            => "E-commerce / Retail",
            'address'             => "8 Allée des Frênes, 33000 Bordeaux",
            'latitude'            => 44.8378, // Coordonnées approximatives de Bordeaux
            'longitude'           => -0.5792,
            'description'         => "Plateforme de vente en ligne en forte croissance, spécialisée dans les produits écologiques et durables.",
            'email_company'       => "rh@ezyshop.fr",
            'n_siret'             => "54187963200014",
            'status'              => "en attente",
        ]);
    }
}
