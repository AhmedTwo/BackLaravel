<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function getCompany()
    {

        $data = Company::select(
            'id',
            'name',
            'logo',
            'number_of_employees',
            'industry',
            'address',
            'latitude',
            'longitude',
            'description',
            'email_company',
            'n_siret',
            'status',
            'created_at',
            'updated_at',
        )->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200); // code reponse 200 pour success
    }

    public function getCompanyById($id)
    {
        $company = Company::findOrFail($id);

        if (!$company) {
            return response()->json([
                'succes' => false,
                'message' => "Société non trouvée"
            ], 404);
        }

        return response()->json([
            'succes' => true,
            'message' => 'Société trouvée',
            'data' => $company
        ], 200);
    }

    public function updateCompany(Request $requestParam, $id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Société non trouvée.',
            ], 404);
        }

        $validatedData = $requestParam->validate([
            'name'                  => 'sometimes|string|max:255',
            // Si vous envoyez le logo comme URL (string), vous avez besoin d'une règle 'string' ou d'ignorer 'file'
            // Si vous envoyez un fichier, c'est OK (voir point B pour la méthode d'envoi)
            'logo'                  => 'sometimes|nullable|file|mimes:jpeg,png,jpg,webp|max:2048', // AJOUT DE nullable
            // AJOUTER 'nullable' pour autoriser l'envoi de champs vides ou nulls
            'number_of_employees' => 'sometimes|nullable|integer',
            'industry'              => 'sometimes|string|max:255',
            'address'               => 'sometimes|string|max:255',
            'latitude'              => 'sometimes|nullable|numeric',
            'longitude'             => 'sometimes|nullable|numeric',
            'description'           => 'sometimes|string|max:2000',
            'email_company'         => 'sometimes|string|email|max:255|unique:companys,email_company,' . $id,
            'n_siret' => 'sometimes|nullable|string|min:14|max:14',
        ], [
            // Champs texte
            'name.string'                   => 'Le nom doit être une chaîne de caractères.',
            'name.max'                      => 'Le nom ne peut pas dépasser 255 caractères.',
            'number_of_employees.integer' => 'Le nombre d\'employés doit être un nombre entier.',
            'industry.string'               => 'Le secteur d\'activité doit être une chaîne de caractères.',
            'industry.max'                  => 'Le secteur d\'activité ne peut pas dépasser 255 caractères.',
            'address.string'                => 'L\'adresse doit être une chaîne de caractères.',
            'address.max'                   => 'L\'adresse ne peut pas dépasser 255 caractères.',
            'latitude.numeric'              => 'La latitude doit être un nombre valide.',
            'longitude.numeric'             => 'La longitude doit être un nombre valide.',
            'description.string'            => 'La description doit être une chaîne de caractères.',
            'description.max'               => 'La description ne peut pas dépasser 2000 caractères.',
            'email_company.string'          => 'L\'adresse e-mail doit être une chaîne de caractères.',
            'email_company.email'           => 'Le format de l\'adresse e-mail n\'est pas valide.',
            'email_company.max'             => 'L\'adresse e-mail ne peut pas dépasser 255 caractères.',
            'email_company.unique'          => 'Cette adresse e-mail est déjà utilisée.',
            'n_siret.string'                => 'Le numéro SIRET doit être une chaîne de caractères.',
            'n_siret.min'                   => 'Le numéro SIRET doit contenir 14 caractères.',
            'n_siret.max'                   => 'Le numéro SIRET doit contenir 14 caractères.',
            'logo.file'                     => 'Le logo doit être un fichier.',
            'logo.mimes'                    => 'Le logo doit être au format jpeg, png, jpg ou webp.',
            'logo.max'                      => 'Le logo est trop volumineux (2 Mo maximum).',
        ]);

        // Gestion du logo :
        if ($requestParam->hasFile('logo')) {
            // S'assurer que 'Storage' est importé 
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $validatedData['logo'] = $requestParam->file('logo')->store('photo_company', 'public');
        }

        $company->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Société mise à jour avec succès.',
            'data' => $company,
        ], 200);
    }

    public function addCompany(Request $requestParam)
    {
        $validatedData = $requestParam->validate([
            'name'                  => 'required|string|max:255',
            'logo'                  => 'nullable|file|mimes:jpeg,png,jpg,webp|max:2048',
            'number_of_employees' => 'required|integer', // La conversion de type dans Vue devrait résoudre le problème
            'industry'              => 'required|string|max:255',
            'address'               => 'required|string|max:255',
            'latitude'              => 'nullable|numeric', // Correction: Rendre ce champ nullable pour accepter null/vide
            'longitude'             => 'nullable|numeric', // Correction: Rendre ce champ nullable
            'description'           => 'required|string|max:2000',
            'email_company'         => 'required|email|max:255|unique:companys,email_company',
            'n_siret'               => 'required|string|min:14|max:14',
        ], [
            // Champs texte et numériques
            'name.required'                 => 'Le nom est obligatoire.',
            'name.string'                   => 'Le nom doit être une chaîne de caractères.',
            'name.max'                      => 'Le nom ne peut pas dépasser 255 caractères.',
            'number_of_employees.required'  => 'Le nombre d\'employés est obligatoire.',
            'number_of_employees.integer'   => 'Le nombre d\'employés doit être un nombre entier.',
            'industry.required'             => 'Le secteur d\'activité est obligatoire.',
            'industry.string'               => 'Le secteur d\'activité doit être une chaîne de caractères.',
            'industry.max'                  => 'Le secteur d\'activité ne peut pas dépasser 255 caractères.',
            'address.required'              => 'L\'adresse est obligatoire.',
            'address.string'                => 'L\'adresse doit être une chaîne de caractères.',
            'address.max'                   => 'L\'adresse ne peut pas dépasser 255 caractères.',
            'latitude.numeric'              => 'La latitude doit être un nombre valide.',
            'longitude.numeric'             => 'La longitude doit être un nombre valide.',
            'description.required'          => 'La description est obligatoire.',
            'description.string'            => 'La description doit être une chaîne de caractères.',
            'description.max'               => 'La description ne peut pas dépasser 2000 caractères.',
            'email_company.required'        => 'L\'adresse e-mail est obligatoire.',
            'email_company.email'           => 'Le format de l\'adresse e-mail n\'est pas valide.',
            'email_company.max'             => 'L\'adresse e-mail ne peut pas dépasser 255 caractères.',
            'email_company.unique'          => 'Cette adresse e-mail est déjà utilisée.',
            'n_siret.required'              => 'Le numéro SIRET est obligatoire.',
            'n_siret.string'                => 'Le numéro SIRET doit être une chaîne de caractères.',
            'n_siret.min'                   => 'Le numéro SIRET doit contenir 14 caractères.',
            'n_siret.max'                   => 'Le numéro SIRET doit contenir 14 caractères.',
            'logo.file'                     => 'Le logo doit être un fichier.',
            'logo.mimes'                    => 'Le logo doit être au format jpeg, png, jpg ou webp.',
            'logo.max'                      => 'Le logo est trop volumineux (2 Mo maximum).',
        ]);

        try {
            // Gestion du logo si présent
            if ($requestParam->hasFile('logo')) {
                $validatedData['logo'] = $requestParam->file('logo')->store('photo_company', 'public');
            } else {
                // S'assurer que 'logo' est défini à null si non fourni et si la colonne DB le permet
                $validatedData['logo'] = null;
            }

            // Correction: Utilisez $validatedData pour toutes les valeurs
            $company = Company::create([
                'name'                  => $validatedData['name'],
                'logo'                  => $validatedData['logo'],
                'number_of_employees'   => $validatedData['number_of_employees'],
                'industry'              => $validatedData['industry'],
                'address'               => $validatedData['address'],
                // Correction: L'utilisation de ?? null dans le create n'est pas nécessaire si les valeurs viennent de validatedData et que la DB est nullable
                'latitude'              => $validatedData['latitude'],
                'longitude'             => $validatedData['longitude'],
                'description'           => $validatedData['description'],
                'email_company'         => $validatedData['email_company'],
                'n_siret'               => $validatedData['n_siret'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Société créée avec succès.',
                'data' => $company,
            ], 201);
        } catch (\Exception $e) {
            // Log de l'erreur côté serveur
            Log::error("Erreur lors de la création de la société : " . $e->getMessage());

            return response()->json([
                'message' => 'Échec de l\'ajout de la société.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteCompany($id)
    {

        $company = Company::find($id);

        if (!$company) {
            return response()->json([
                'succes' => false,
                'message' => 'Société non trouvée, impossible de la supprimer',
            ], 404);
        }

        try {
            // Suppression du logo associé avant la suppression de l'enregistrement
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }

            $company->delete();

            return  response()->json([
                'success' => true,
                'message' => 'Société supprimé avec succès.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Echec de la suppression de la Société',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
