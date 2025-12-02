<?php

namespace Database\Seeders;

use App\Models\Request; // Importation du modèle Request
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RequestsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Request::create([
            'title'        => "Problème de connexion au tableau de bord",
            'description'  => "Je n'arrive pas à accéder à mon tableau de bord après la mise à jour. L'erreur 500 s'affiche.",
            'type'         => "support technique",
            'status'       => "en cours",
            'user_id'      => 3, // 'Thomas Lefevre' 
            'company_id'   => null,
        ]);

        Request::create([
            'title'        => "Demande de partenariat et intégration API",
            'description'  => "Nous souhaiterions intégrer notre système RH directement à votre plateforme via votre API. Contactez-nous pour un RDV.",
            'type'         => "partenariat commercial",
            'status'       => "en cours",
            'user_id'      => null, 
            'company_id'   => 1, // 'Innovatech Solutions' 
        ]);

        Request::create([
            'title'        => "Correction de l'adresse de l'entreprise",
            'description'  => "L'adresse enregistrée pour Global Finance Partners est incorrecte. La bonne adresse est : 35 Avenue Foch.",
            'type'         => "changement de données",
            'status'       => "en cours",
            'user_id'      => 2, // 'Marie Durand'
            'company_id'   => null, 
        ]);
    }
}
