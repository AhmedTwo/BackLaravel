<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Offer;
use Illuminate\Http\Request;

class FavorisController extends Controller
{
    public function getFavoris()
    {

        $data = Favorite::select(
            'id',
            'user_id',
            'offer_id',
            'created_at',
            'updated_at',
        )->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200); // code reponse 200 pour success
    }

    public function getFavorisById($id)
    {
        $favoris = Favorite::find($id);

        if (!$favoris) {
            return response()->json([
                'succes' => false,
                'message' => "Favoris non trouvée"
            ], 404);
        }

        return response()->json([
            'succes' => true,
            'message' => 'Favoris trouvée',
            'data' => $favoris
        ], 200);
    }

    public function deleteFavoris($id)
    {

        $favoris = Favorite::find($id);

        if (!$favoris) {
            return response()->json([
                'succes' => false,
                'message' => 'Favoris non trouvée, impossible de la supprimer',
            ], 404);
        }

        try {
            $favoris->delete();

            return  response()->json([
                'success' => true,
                'message' => 'Favoris supprimée avec succès.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Echec de la suppression du Favoris',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // Ajout d'une offre aux favoris
    public function addFavorite(Request $request, $offerId)
    {
        // Je commence par récupérer l'utilisateur qui est actuellement **connecté** via la requête.
        // Je stocke l'objet utilisateur dans la variable **$user**.
        $user = $request->user();

        // Je vérifie si l'**Offre** avec cet identifiant ($offerId) existe bien dans ma base de données.
        if (!Offer::find($offerId)) {
            // Si l'offre n'existe **pas**, je renvoie une réponse JSON d'erreur
            // avec le code de statut **404** (Non trouvée).
            return response()->json(['success' => false, 'message' => 'Offre non trouvée.'], 404);
        }

        // Je veux m'assurer que l'offre n'est pas **déjà** dans les favoris de l'utilisateur.
        // Donc je recherche un enregistrement dans ma table `Favorite` qui correspond
        // à l'ID de mon utilisateur ($user->id) ET à l'ID de l'offre ($offerId).
        $favorite = Favorite::where('user_id', $user->id) // le where est pour chercher
            ->where('offer_id', $offerId)
            ->first(); // Je prends le premier (et seul) résultat que je trouve.

        // Si la variable **$favorite** contient un enregistrement (donc c'est déjà un favori), je m'arrête là.
        if ($favorite) {
            // Je renvoie un statut **409** (Conflit) pour indiquer que l'opération a déjà été effectuée.
            return response()->json(['success' => false, 'message' => 'Offre déjà dans les favoris.'], 409);
        }

        // L'offre est valide et n'est pas un favori, je peux donc **créer** le nouvel enregistrement.
        // J'insère une nouvelle ligne dans la table `Favorite`.
        Favorite::create([ // le create est pour ajouter
            'user_id' => $user->id, // J'utilise l'ID de l'utilisateur que j'ai récupéré au début.
            'offer_id' => $offerId, // J'utilise l'ID de l'offre passé en paramètre.
        ]);

        // Tout est bon ! Je confirme le succès avec une réponse JSON et le code de statut **201** (Créé).
        return response()->json(['success' => true, 'message' => 'Offre ajoutée aux favoris.'], 201);
    }

    // Suppression d'une offre des favoris
    public function removeFavorite(Request $request, $offerId)
    {
        // Je commence par récupérer l'utilisateur qui est actuellement **connecté**.
        $user = $request->user();

        // Je recherche l'enregistrement spécifique dans la table **Favorite**.
        // Je dois trouver la ligne qui correspond à l'ID de mon utilisateur ET à l'ID de l'offre à supprimer.
        $favorite = Favorite::where('user_id', $user->id)
            ->where('offer_id', $offerId)
            ->first(); // Je récupère le premier (et unique) résultat.

        // Si je ne trouve **pas** cet enregistrement dans les favoris soit le **!** pour dire **différent**
        if (!$favorite) {
            // je renvoie une réponse JSON d'erreur avec le statut **404** (Non trouvée),
            // car l'offre n'existe pas dans cette liste.
            return response()->json(['success' => false, 'message' => 'Offre non trouvée dans les favoris.'], 404);
        }

        // Si l'enregistrement est trouvé, je peux le **supprimer** de la base de données.
        $favorite->delete();

        // Je confirme le succès de la suppression avec une réponse JSON et le statut **200** (OK).
        return response()->json(['success' => true, 'message' => 'Offre retirée des favoris.'], 200);
    }

    // Récupérer toutes les offres favorites de l'utilisateur connecté
    public function getUserFavorites(Request $request)
    {
        // Je récupère l'utilisateur qui est **connecté** pour savoir quelles sont ses offres en question.
        $user = $request->user();

        // Je lance une requête sur la table **Favorite** en filtrant par l'ID de mon utilisateur.
        $favorites = Favorite::where('user_id', $user->id)
            // J'utilise `->with()` pour charger les relations en même temps (c'est de l'optimisation !).
            // J'inclus l'objet **offer** (les détails de l'offre).
            ->with(['offer' => function ($query) {
                // À l'intérieur de l'offre, je charge également le **employment_type** (type de contrat).
                $query->with('employment_type');
            }])
            ->get(); // J'obtiens la collection de tous les enregistrements favoris. ->get() pour recupérer

        // La collection `$favorites` contient les objets 'Favorite' qui eux-mêmes contiennent l'objet 'Offer'.
        // J'utilise `->pluck('offer')` pour extraire **seulement** l'objet `offer` de chaque élément.
        $favoriteOffers = $favorites->pluck('offer')->filter(); // Puis je filtre les offres nulles si jamais une offre a été supprimée.

        // Je renvoie les données des offres extraites dans une réponse JSON avec le statut **200**.
        return response()->json([
            'success' => true,
            'data' => $favoriteOffers // la data des offres extraites
        ], 200);
    }

    // Vérifier si une offre est favorite pour l'utilisateur
    public function checkFavorite(Request $request, $offerId)
    {
        // Je récupère l'utilisateur qui est **connecté**.
        $user = $request->user();

        // Je cherche s'il existe (avec `->exists()` fonction propre a laravel) une ligne dans la table **Favorite**
        // qui correspond à l'ID de mon utilisateur ET à l'ID de l'offre.
        // La variable **$isFavorite** sera un simple booléen (**true** ou **false**).
        $isFavorite = Favorite::where('user_id', $user->id)
            ->where('offer_id', $offerId)
            ->exists();

        // Je retourne la réponse JSON. J'envoie la valeur booléenne pour indiquer
        // si l'offre est ou n'est pas favorite pour cet utilisateur.
        return response()->json(['isFavorite' => $isFavorite], 200);
    }
}
