<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'nom'           => "Seghiri",
            'prenom'        => "Ahmed",
            'email'         => "seghiriahmed9@gmail.com",
            'password'      => Hash::make("ahmedmdp"), // Hachage du mot de passe
            'role'          => "admin",
            'telephone'     => "0768687403",
            'ville'         => "Sannois",
            'code_postal'   => "95110",
            'cv_pdf'        => "/public/assets/images/userDefault.jpeg",
            'qualification' => "Etudiant",
            'preference'    => "CDI, CDD",
            'disponibilite' => 1,
            'photo'         => "/public/assets/images/userDefault.jpeg",
        ]);

        User::create([
            'nom'           => "Tech",
            'prenom'        => "Pro",
            'email'         => "tech_pro@company.com",
            'password'      => Hash::make("techpromdp"), // Hachage du mot de passe
            'role'          => "company",
            'telephone'     => "0612345678",
            'ville'         => "Paris",
            'code_postal'   => "75001",
            'cv_pdf'        => "/public/assets/images/userDefault.jpeg",
            'qualification' => "Développeuse Senior",
            'preference'    => "CDI",
            'disponibilite' => 1,
            'photo'         => "/public/assets/images/userDefault.jpeg",
        ]);

        User::create([
            'nom'           => "Lefevre",
            'prenom'        => "Thomas",
            'email'         => "thomas@example.com",
            'password'      => Hash::make("thomasmdp"), // Hachage du mot de passe
            'role'          => "candidat",
            'telephone'     => "0198765432",
            'ville'         => "Lyon",
            'code_postal'   => "69002",
            'cv_pdf'        => "/public/assets/images/userDefault.jpeg",
            'qualification' => "Technicien",
            'preference'    => "Intérim, CDD",
            'disponibilite' => 0,
            'photo'         => "/public/assets/images/userDefault.jpeg",
        ]);
    }
}
