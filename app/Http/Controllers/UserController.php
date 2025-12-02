<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function getUser()
    {
        $data = User::select(
            'id',
            'nom',
            'prenom',
            'email',
            'password',
            'role',
            'telephone',
            'ville',
            'code_postal',
            'cv_pdf',
            'qualification',
            'preference',
            'disponibilite',
            'photo',
            'created_at',
            'updated_at',
        )->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200); // code reponse 200 pour success
    }

    public function getUserById($id)
    {

        $user = User::find($id);

        if (!$user) {

            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur trouvé',
            'data' => $user,
        ], 200);
    }

    public function getUserByRole($role)
    {

        $user = User::where('role', $role)->get();

        if (!$user) {

            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur trouvé',
            'data' => $user,
        ], 200);
    }

    public function updateUser(Request $requestParam, $id)
    {
        // on trouve l'utilisateur
        $user = User::find($id);

        Log::info('Données reçues dans updateUser:', $requestParam->all());

        // on verifie s'il existe
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé',
            ], 404);
        }

        // on valide les données reçues
        $validatedData = $requestParam->validate([
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            // NOUVEAU: Règle d'unicité qui ignore l'utilisateur $id pour éviter l'erreur 422
            'email'         => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users')->ignore($id),
            ],
            'telephone' => 'sometimes|string|max:20',
            'ville' => 'sometimes|string|max:50',
            'code_postal' => 'sometimes|string|max:20',
            'cv_pdf' => 'sometimes|file|mimes:pdf|max:10240',
            'qualification' => 'sometimes|string|max:255',
            'preference' => 'sometimes|string|max:255',
            'disponibilite' => 'sometimes|boolean',
            'photo' => 'sometimes|file|mimes:jpeg,png,jpg,webp|max:2048',
            'current_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:8|confirmed',
        ], [
            // Messages personnalisés
            'nom.string'        => 'Le nom doit être une chaîne de caractères.',
            'nom.max'           => 'Le nom ne peut pas dépasser 255 caractères.',
            'prenom.string'     => 'Le prénom doit être une chaîne de caractères.',
            'prenom.max'        => 'Le prénom ne peut pas dépasser 255 caractères.',
            'telephone.string'  => 'Le numéro de téléphone doit être une chaîne de caractères.',
            'telephone.max'     => 'Le numéro de téléphone ne peut pas dépasser 20 caractères.',
            'ville.string'      => 'La ville doit être une chaîne de caractères.',
            'ville.max'         => 'La ville ne peut pas dépasser 50 caractères.',
            'code_postal.string' => 'Le code postal doit être une chaîne de caractères.',
            'code_postal.max'   => 'Le code postal ne peut pas dépasser 20 caractères.',
            'cv_pdf.string'     => 'Le chemin du CV doit être une chaîne de caractères.',
            'cv_pdf.max'        => 'Le chemin du CV ne peut pas dépasser 255 caractères.',
            'qualification.string' => 'La qualification doit être une chaîne de caractères.',
            'qualification.max'    => 'La qualification ne peut pas dépasser 255 caractères.',
            'preference.string' => 'La préférence doit être une chaîne de caractères.',
            'preference.max'    => 'La préférence ne peut pas dépasser 255 caractères.',
            'photo.file'        => 'L\'image doit être un fichier.',
            'photo.mimes'       => 'L\'image doit être au format jpeg, png, jpg ou webp.',
            'photo.max'         => 'L\'image est trop volumineuse (2 Mo maximum).',
            'email.unique'      => 'Cette adresse e-mail est déjà utilisée par un autre compte.',
            'disponibilite.boolean' => 'La disponibilité doit être une valeur Vrai ou Faux.',
        ]);

        // Gestion de la photo
        if ($requestParam->hasFile('photo')) {
            $photoPath = $requestParam->file('photo')->store('photo_user', 'public');
            $validatedData['photo'] = $photoPath;

            // Supprimer l'ancienne photo si elle existe
            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }
        }

        if ($requestParam->filled('current_password')) {
            // Vérifier le mdp actuel
            if (!Hash::check($requestParam->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le mot de passe actuel est incorrect.',
                ], 403);
            }

            // Vérifier nouveau mdp + confirmation
            if (!$requestParam->filled('new_password')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veuillez saisir un nouveau mot de passe.',
                ], 422);
            }

            // Mise à jour sécurisée
            $validatedData['password'] = Hash::make($requestParam->new_password);
        }

        // on met à jour l'utilisateur
        $user->update($validatedData);

        // on retourne la réponse
        return response()->json([
            'success' => true,
            'message' => 'Utilisateur trouvé et mis à jour avec succès',
            'data' => $user, // utilisateur mis à jour
        ], 200);
    }

    public function addUser(Request $requestParam)
    {
        // Ajout de 'company_id' comme champ optionnel (par défaut, pour un candidat)
        $validatedData = $requestParam->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:255',
            'telephone' => 'required|string|max:20',
            'ville' => 'required|string|max:50',
            'code_postal' => 'required|string|max:20',
            'qualification' => 'nullable|string|max:255',
            'preference' => 'nullable|string|max:255',
            'disponibilite' => 'nullable|in:0,1',
            'photo' => 'nullable|image|max:2048',
            // 'nullable' : La photo est optionnelle
            // 'image' : Assure que c'est un fichier image valide (jpg, png, gif, svg, webp)
            // 'max:2048' : Taille maximale de 2 Mo (2048 kilobytes)
            'cv_pdf' => 'nullable|file|mimes:pdf|max:10240', // 10Mo max pour le PDF
            'company_id' => 'nullable|integer|exists:companys,id',
        ], [
            // Messages personnalisés
            'nom.required'         => 'Le nom est obligatoire.',
            'nom.string'           => 'Le nom doit être une chaîne de caractères.',
            'nom.max'              => 'Le nom ne peut pas dépasser 255 caractères.',
            'prenom.required'      => 'Le prénom est obligatoire.',
            'prenom.string'        => 'Le prénom doit être une chaîne de caractères.',
            'prenom.max'           => 'Le prénom ne peut pas dépasser 255 caractères.',
            'email.required'       => 'L\'adresse e-mail est obligatoire.',
            'email.string'         => 'L\'adresse e-mail doit être une chaîne de caractères.',
            'email.email'          => 'Le format de l\'adresse e-mail n\'est pas valide.',
            'email.max'            => 'L\'adresse e-mail ne peut pas dépasser 255 caractères.',
            'email.unique'         => 'Cette adresse e-mail est déjà utilisée.',
            'password.required'    => 'Le mot de passe est obligatoire.',
            'password.string'      => 'Le mot de passe doit être une chaîne de caractères.',
            'password.min'         => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.max'         => 'Le mot de passe ne peut pas dépasser 255 caractères.',
            'telephone.required'   => 'Le numéro de téléphone est obligatoire.',
            'telephone.string'     => 'Le numéro de téléphone doit être une chaîne de caractères.',
            'telephone.max'        => 'Le numéro de téléphone ne peut pas dépasser 20 caractères.',
            'ville.required'       => 'La ville est obligatoire.',
            'ville.string'         => 'La ville doit être une chaîne de caractères.',
            'ville.max'            => 'La ville ne peut pas dépasser 50 caractères.',
            'code_postal.required' => 'Le code postal est obligatoire.',
            'code_postal.string'   => 'Le code postal doit être une chaîne de caractères.',
            'code_postal.max'      => 'Le code postal ne peut pas dépasser 20 caractères.',
            'qualification.string'   => 'La qualification doit être une chaîne de caractères.',
            'qualification.max'      => 'La qualification ne peut pas dépasser 255 caractères.',
            'disponibilite.string'   => 'La disponibilité doit être une chaîne de caractères.',
            'disponibilite.max'      => 'La disponibilité ne peut pas dépasser 255 caractères.',
            'photo.image' => 'Le fichier doit être une image (jpeg, png, etc.).',
            'photo.max' => 'La taille de l\'image ne doit pas dépasser 2 Mo.',
            'cv_pdf.file' => 'Le CV doit être un fichier.',
            'cv_pdf.mimes' => 'Le CV doit être au format PDF.',
            'cv_pdf.max' => 'La taille du CV ne doit pas dépasser 10 Mo.',
            'company_id.exists' => 'L\'ID de la société fournie n\'existe pas.'
        ]);

        try {

            $photoPath = null;
            $cvPath = null;

            // LOGIQUE DE GESTION ET DE SAUVEGARDE DU FICHIER PHOTO
            if ($requestParam->hasFile('photo')) {
                $photoPath = $requestParam->file('photo')->store('photo_user', 'public');
            }

            // LOGIQUE DE GESTION ET DE SAUVEGARDE DU FICHIER CV
            if ($requestParam->hasFile('cv_pdf')) {
                $cvPath = $requestParam->file('cv_pdf')->store('cv', 'public');
            }

            // LOGIQUE POUR GÉRER LE RÔLE AUTOMATIQUEMENT
            $email = $validatedData['email'];

            if (isset($validatedData['company_id'])) {
                // Si company_id est présent (provient du formulaire AddCompany)
                $role = 'company';
                // s'assurer que si c'est un user company, 
                // le champ company_id est bien mis à jour
                $companyId = $validatedData['company_id'];
            } else {
                // Sinon (formulaire d'inscription normal)
                $role = str_ends_with($email, '@company.com') ? 'company' : 'candidat';
                $companyId = null;
            }

            // Hachage du mot de passe
            $validatedData['password'] = Hash::make($validatedData['password']);

            $qualificationValue = $validatedData['qualification'] ?? ''; // La chaîne vide pour NOT NULL
            $preferenceValue = $validatedData['preference'] ?? null; // Null est OK pour preference
            $cvPathValue = $cvPath ?? ''; // $cvPath est null si aucun fichier n'a été uploadé

            // Création de l'utilisateur
            $user = User::create([
                'nom' => $validatedData['nom'],
                'prenom' => $validatedData['prenom'],
                'email' => $email,
                'password' => $validatedData['password'],
                'role' => $role, // Rôle déterminé par la nouvelle logique
                'telephone' => $validatedData['telephone'],
                'ville' => $validatedData['ville'],
                'code_postal' => $validatedData['code_postal'],
                'qualification' => $qualificationValue,
                'preference' => $preferenceValue,
                'disponibilite' => (int)($validatedData['disponibilite'] ?? 0),

                // LIAISON AVEC LA SOCIÉTÉ
                'company_id' => $companyId,

                'photo' => $photoPath ?? '/public/assets/images/userDefault.jpeg',
                'cv_pdf' => $cvPathValue, // Sera '' si manquant et $cvPath est null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès.',
                'data' => $user
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Échec de l\'ajout de l\'utilisateur.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function deleteUser($id)
    {

        $user = User::find($id);

        if (!$user) {

            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé',
            ], 404);
        }

        try {
            $user->delete();

            return  response()->json([
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Echec de la suppression de l\'utilisateur',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
