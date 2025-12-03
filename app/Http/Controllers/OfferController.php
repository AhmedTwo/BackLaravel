<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function getOffer()
    {
        $data = Offer::select(
            'id',
            'title',
            'description',
            'mission',
            'location',
            'category',
            'employment_type_id', // dans le front on a donc appelé {{ offer.employment_type.name }}
            'technologies_used',
            'benefits',
            'participants_count',
            'image_url',
            'created_at',
            'updated_at',
            'id_company',
        )->with('employment_type')
            ->get();



        return response()->json([
            'success' => true,
            'data' => $data
        ], 200); // code reponse 200 pour success
    }

    public function getOfferById(Request $requestParam, $id)
    {

        // Je récupère l'utilisateur connecté via le jeton de la requête.
        $user = $requestParam->user();

        // Si l'utilisateur n'est **pas** connecté (pas de jeton valide)
        if (!$user) {
            // alors je renvoie une erreur **401** (Non autorisé).
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé. Veuillez vous connecter.'
            ], 401);
        }

        $offer = Offer::find($id);

        if (!$offer) {
            return response()->json([
                'succes' => false,
                'message' => "Offre non trouvée"
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Offre trouvée',
            'data' => $offer
        ], 200);
    }

    public function getOffersByCompany(Request $requestParam)
    {

        // Je récupère l'utilisateur connecté via le jeton de la requête.
        $user = $requestParam->user();

        // Je vérifie si l'utilisateur est bien connecté.
        if (!$user) {
            // Si non, je renvoie une erreur **401** (Non autorisé).
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé. Veuillez vous connecter.'
            ], 401);
        }

        $companyId = $user->company_id;

        $offers = Offer::with('employment_type')
            ->where('id_company', $companyId)
            ->get();
        return response()->json([
            'data' => $offers
        ]);
    }

    public function updateOffer(Request $requestParam, $id)
    {

        // Je récupère l'utilisateur connecté via le jeton de la requête.
        $user = $requestParam->user();

        // Si l'utilisateur n'est **pas** connecté (pas de jeton valide)
        if (!$user) {
            // alors je renvoie une erreur **401** (Non autorisé).
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé. Veuillez vous connecter.'
            ], 401);
        }

        $offer = Offer::find($id);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Offre non trouvée',
            ], 404);
        }

        // SÉCURITÉ : Je dois m'assurer que **seul le créateur**
        // de l'offre puisse la modifier.
        if ($offer->id_company  !== $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => "Accès refusé : vous n'êtes pas autorisé à modifier cette offre."
            ], 403);
        }

        $validatedData = $requestParam->validate([
            'title'              => 'sometimes|string|max:255',
            'description'        => 'sometimes|string|max:2000',
            'mission'            => 'sometimes|string|max:255',
            'location'           => 'sometimes|string|max:255',
            'category'           => 'sometimes|string|max:255',
            // Ajouter 'integer' et 'exists' pour la clé étrangère afin de faire le lien avec la table employment_type
            'employment_type_id' => 'sometimes|integer|exists:employment_type,id',
            'technologies_used'  => 'sometimes|string|max:255',
            'benefits'           => 'sometimes|string|max:255',
            'image_url'          => 'sometimes|file|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            // Champs texte
            'title.string'              => 'Le titre doit être une chaîne de caractères.',
            'title.max'                 => 'Le titre ne peut pas dépasser 255 caractères.',
            'description.string'        => 'La description doit être une chaîne de caractères.',
            'description.max'           => 'La description ne peut pas dépasser 2000 caractères.',
            'mission.string'            => 'La mission doit être une chaîne de caractères.',
            'mission.max'               => 'La mission ne peut pas dépasser 255 caractères.',
            'location.string'           => 'Le lieu doit être une chaîne de caractères.',
            'location.max'              => 'Le lieu ne peut pas dépasser 255 caractères.',
            'category.string'           => 'La catégorie doit être une chaîne de caractères.',
            'category.max'              => 'La catégorie ne peut pas dépasser 255 caractères.',
            'employment_type_id.integer' => 'Le type de contrat doit être un entier valide.',
            'employment_type_id.exists' => 'Le type de contrat sélectionné n\'existe pas.',
            'technologies_used.string'  => 'Les technologies doivent être une chaîne de caractères.',
            'technologies_used.max'     => 'Les technologies ne peuvent pas dépasser 255 caractères.',
            'benefits.string'           => 'Les avantages doivent être une chaîne de caractères.',
            'benefits.max'              => 'Les avantages ne peuvent pas dépasser 255 caractères.',
            'image_url.file'            => 'L\'image doit être un fichier.',
            'image_url.mimes'           => 'L\'image doit être au format jpeg, png, jpg ou webp.',
            'image_url.max'             => 'L\'image est trop volumineuse (2 Mo maximum).',
        ]);

        $offer->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Offre mise à jour avec succès',
            'data' => $offer
        ], 200);
    }

    public function addOffer(Request $requestParam)
    {
        $validatedData = $requestParam->validate([
            'title'              => 'required|string|max:255',
            'description'        => 'required|string|max:2000',
            'mission'            => 'required|string|max:255',
            'location'           => 'required|string|max:255',
            'category'           => 'required|string|max:255',
            'employment_type_id' => 'required|integer|exists:employment_type,id',
            'technologies_used'  => 'required|string|max:255',
            'benefits'           => 'required|string|max:255',
            'image_url'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096'
        ], [
            // Champs obligatoires
            'title.required'              => 'Le titre est obligatoire.',
            'title.string'                => 'Le titre doit être une chaîne de caractères.',
            'title.max'                   => 'Le titre ne peut pas dépasser 255 caractères.',
            'description.required'        => 'La description est obligatoire.',
            'description.string'          => 'La description doit être une chaîne de caractères.',
            'description.max'             => 'La description ne peut pas dépasser 2000 caractères.',
            'mission.required'            => 'La mission est obligatoire.',
            'mission.string'              => 'La mission doit être une chaîne de caractères.',
            'mission.max'                 => 'La mission ne peut pas dépasser 255 caractères.',
            'location.required'           => 'Le lieu est obligatoire.',
            'location.string'             => 'Le lieu doit être une chaîne de caractères.',
            'location.max'                => 'Le lieu ne peut pas dépasser 255 caractères.',
            'category.required'           => 'La catégorie est obligatoire.',
            'category.string'             => 'La catégorie doit être une chaîne de caractères.',
            'category.max'                => 'La catégorie ne peut pas dépasser 255 caractères.',
            'employment_type_id.required' => 'Le type de contrat est obligatoire.',
            'employment_type_id.integer'  => 'Le type de contrat doit être un entier valide.',
            'employment_type_id.exists'   => 'Le type de contrat sélectionné n\'existe pas.',
            'technologies_used.required'  => 'Les technologies utilisées sont obligatoires.',
            'technologies_used.string'    => 'Les technologies doivent être une chaîne de caractères.',
            'technologies_used.max'       => 'Les technologies ne peuvent pas dépasser 255 caractères.',
            'benefits.required'           => 'Les avantages sont obligatoires.',
            'benefits.string'             => 'Les avantages doivent être une chaîne de caractères.',
            'benefits.max'                => 'Les avantages ne peuvent pas dépasser 255 caractères.',
            'image_url.file'            => 'L\'image doit être un fichier.',
            'image_url.mimes'           => 'L\'image doit être au format jpeg, png, jpg ou webp.',
            'image_url.max'             => 'L\'image est trop volumineuse (2 Mo maximum).',
        ]);

        // recuperation de l'ID de la Compagnie Connectée 
        // on recup l'utilisateur (company) connecté via le token
        $user = $requestParam->user();
        // on recup l'ID de la compagnie rattachée à cet utilisateur
        $companyId = $user->company_id;

        // On prépare un chemin par défaut si aucune image n'est fournie
        $imagePath = '/assets/images/offerDefault.jpeg';

        // Vérifie si une image a été envoyée dans la requête
        if ($requestParam->hasFile('image_url')) {

            // Sauvegarde du fichier dans storage/app/public/photo_offer
            // store() retourne le chemin relatif du fichier, ex : "photo_offer/az45f.png"
            $path = $requestParam->file('image_url')->store('photo_offer', 'public');

            // Le chemin public utilisé pour afficher l’image dans le frontend
            // /storage → lien symbolique vers storage/app/public
            $imagePath = '/storage/' . $path;
        }

        try {

            $offer = Offer::create([
                'title'              => $validatedData['title'],
                'description'        => $validatedData['description'],
                'mission'            => $validatedData['mission'],
                'location'           => $validatedData['location'],
                'category'           => $validatedData['category'],
                'employment_type_id' => $validatedData['employment_type_id'],
                'technologies_used'  => $validatedData['technologies_used'],
                'benefits'           => $validatedData['benefits'],
                'image_url'          => $imagePath,
                'id_company'         => $companyId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Offre créée avec succès.',
                'data' => $offer,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Échec de l\'ajout de l\'offre.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteOffer(Request $requestParam, $id)
    {
        // Je récupère l'utilisateur **connecté** pour vérifier les droits.
        $user = $requestParam->user();

        // si différent de l'utilisateur **connecté** 
        if (!$user) {
            // alors je renvoie une erreur **401** (Non autorisé).
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé. Veuillez vous connecter.'
            ], 401);
        }

        // Je recherche l'offre à supprimer par son ID.
        $offer = Offer::find($id);

        if (!$offer) {
            return response()->json([
                'succes' => false,
                'message' => 'Offre non trouvée, impossible de la supprimer',
            ], 404);
        }

        // SÉCURITÉ : Je vérifie si l'utilisateur connecté est bien le créateur de l'offre avant de la supprimer.
        if ($offer->id_company !== $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => "Accès refusé : vous n'êtes pas autorisé à supprimer cette offre."
            ], 403);
        }

        try {
            $offer->delete();

            return  response()->json([
                'success' => true,
                'message' => 'Offre supprimée avec succès.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Echec de la suppression de l\'offre',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
