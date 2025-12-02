<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Offer;
use Database\Factories\OffersFactory;
use Illuminate\Database\Seeder;

class OffersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Offer::create([
            'title'              => "Développeur Full Stack Laravel",
            'description'        => "Rejoignez notre équipe dynamique pour construire la prochaine génération de nos plateformes web.",
            'mission'            => "Développement back-end (PHP/Laravel) et front-end (Vue.js/React). Maintenance et optimisation des bases de données. Collaboration avec l'équipe produit.",
            'location'           => "Paris (75002)",
            'category'           => "Développement Web",
            'employment_type_id' => 1, // s'assurer que l'id 1 existe et que sa soit le CDI
            'technologies_used'  => "PHP, Laravel, Vue.js, MySQL, Docker, Git",
            'benefits'           => "Télétravail partiel, Mutuelle premium, Tickets restaurant, Bonus annuel.",
            'participants_count' => 5,
            'image_url'          => "/public/assets/images/offreDefault.jpeg",
            'id_company'         => 1, // s'assurer que l'entreprise avec l'ID 2 existe
        ]);

        Offer::create([
            'title'              => "Chef de Projet Digital Junior",
            'description'        => "Pilotage de petits projets digitaux, de la conception à la mise en ligne. Idéal pour un premier poste après études.",
            'mission'            => "Suivi du planning et du budget, coordination des équipes techniques et marketing, rédaction de spécifications fonctionnelles.",
            'location'           => "Lyon (69003)",
            'category'           => "Gestion de Projet",
            'employment_type_id' => 2, // s'assurer que l'id 2 existe et que sa soit le CDD
            'technologies_used'  => "Jira, Trello, Google Analytics, Scrum/Agile",
            'benefits'           => "Prime de transport, Formation continue, Événements d'équipe trimestriels.",
            'participants_count' => 12,
            'image_url'          => "/public/assets/images/offreDefault.jpeg",
            'id_company'         => 2,
        ]);

        Offer::create([
            'title'              => "Data Scientist / ML Engineer",
            'description'        => "Construction et déploiement de modèles prédictifs pour optimiser nos opérations logistiques.",
            'mission'            => "Collecte et nettoyage de données. Développement d'algorithmes de Machine Learning (Python). Mise en production sur cloud.",
            'location'           => "Bordeaux (33000)",
            'category'           => "Data Science & IA",
            'employment_type_id' => 1,
            'technologies_used'  => "Python, TensorFlow, PyTorch, SQL, AWS/Azure, Docker",
            'benefits'           => "100% Télétravail possible, Actionnariat, Congés supplémentaires.",
            'participants_count' => 8,
            'image_url'          => "/public/assets/images/offreDefault.jpeg",
            'id_company'         => 3,
        ]);

        // sa me permet de faire apl au factories et dajouter 10 alea
        // OffersFactory::factory()
        //     ->count(10)
        //     ->create();
    }
}
