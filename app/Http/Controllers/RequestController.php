<?php

namespace App\Http\Controllers;

use App\Models\Request as RequestModel;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    // --- Fonction 1 : Récupérer TOUTES les demandes ---
    public function getRequest()
    {
        // Je sélectionne toutes les demandes et j'ajoute les informations de l'utilisateur qui a créé la demande.
        // Je peux optimiser cette requête en utilisant la relation `user` car elle est définie dans le modèle `RequestModel`.
        // Pour l'instant, je garde la jointure manuelle pour la clarté.
        $data = RequestModel::select(
            'requests.id',
            'requests.title',
            'requests.description',
            'requests.type',
            'requests.status',
            'requests.created_at',
            'users.nom',
            'users.prenom',
            'users.photo',
            'users.qualification',
        )
            // Je fais une jointure (`->join`) entre la table `requests` et la table `users`
            // pour associer la demande à son créateur.
            ->join('users', 'requests.user_id', '=', 'users.id')
            ->get(); // le ->get() récupère tous les résultats.

        // Je retourne la collection de toutes les demandes trouvées avec un statut **201** (Créé/Succès).
        return response()->json([
            'success' => true,
            'data' => $data
        ], 201);
    }

    // --- Fonction 2 : Récupérer une demande par son ID avec vérification d'accès ---
    public function getRequestById(Request $requestParam, $id)
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

        // Je recherche la demande (`RequestModel` qui est bien sur le models Request) par son identifiant ($id).
        $request = RequestModel::find($id);

        // Si je ne trouve **aucune** demande avec cet ID
        if (!$request) {
            // alors je renvoie une erreur **404** (Non trouvée).
            return response()->json([
                'succes' => false,
                'message' => "Demande non trouvée"
            ], 404);
        }

        // SÉCURITÉ : Je vérifie que l'ID de l'utilisateur de la demande
        // est bien le même que l'ID de l'utilisateur **connecté** ($user->id).
        if ($request->user_id !== $user->id) {
            // Si les ID ne correspondent pas (l'utilisateur essaie de voir la demande de quelqu'un d'autre),
            // je renvoie une erreur **403** (Accès refusé).
            return response()->json([
                'success' => false,
                'message' => "Accès refusé : vous n'êtes pas autorisé à voir cette demande."
            ], 403);
        }

        // Si tout est valide, je retourne les détails de la demande avec un statut **200**.
        return response()->json([
            'succes' => true,
            'message' => 'Demande trouvée',
            'data' => $request
        ], 200);
    }

    // --- Fonction 3 : Récupérer toutes les demandes d'un utilisateur connecté ---
    public function getRequestsByUser(Request $requestParam)
    {
        // Je récupère l'utilisateur qui est **connecté**.
        $user = $requestParam->user();

        // Je vérifie si l'utilisateur est bien connecté.
        if (!$user) {
            // Si non, je renvoie une erreur **401** (Non autorisé).
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé. Veuillez vous connecter.'
            ], 401);
        }

        // Je construis la requête pour sélectionner les champs nécessaires à afficher.
        $data = RequestModel::select(
            'requests.id',
            'requests.title',
            'requests.description',
            'requests.type',
            'requests.status',
            'requests.created_at',
            'users.nom',
            'users.prenom',
            'users.photo',
            'users.qualification',
        )
            // Je fais la jointure avec la table `users` pour obtenir les détails du créateur (mon utilisateur).
            ->join('users', 'requests.user_id', '=', 'users.id')
            // C'est l'étape cruciale : je filtre pour n'afficher que les demandes
            // dont l'ID utilisateur (`requests.user_id`) correspond à l'ID de l'utilisateur connecté ($user->id).
            ->where('requests.user_id', $user->id)
            ->get(); // Je récupère la collection de ces demandes.

        // Je retourne la liste des demandes de l'utilisateur avec un statut **200**.
        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }

    // --- Fonction 4 : Mettre à jour une demande ---
    public function updateRequest(Request $requestParam, $id)
    {
        // Je récupère l'utilisateur **connecté** pour les vérifications de sécurité.
        $user = $requestParam->user();

        // si différent de l'utilisateur **connecté** 
        if (!$user) {
            // alors je renvoie une erreur **401** (Non autorisé).
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé. Veuillez vous connecter.'
            ], 401);
        }

        // Je recherche la demande à modifier via le models par son ID.
        $request = RequestModel::find($id);

        // Si la demande n'existe pas
        if (!$request) {
            // alors je renvoie une erreur **404** (Non trouvée).
            return response()->json([
                'success' => false,
                'message' => 'Demande non trouvée.',
            ], 404);
        }

        // SÉCURITÉ : Je dois m'assurer que **seul le créateur**
        // de la demande puisse la modifier.
        if ($request->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => "Accès refusé : vous n'êtes pas autorisé à modifier cette demande."
            ], 403);
        }

        // Je valide les données reçues de la requête.
        // `sometimes` signifie que le champ n'est pas obligatoire dans la requête,
        // mais s'il est présent, il doit respecter les règles.
        $validatedData = $requestParam->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:2000',
            // Le type doit faire partie d'une liste prédéfinie. chose que je fais dans le **in**
            'type'        => 'sometimes|string|max:255|in:RECLAMATION,DEMANDES,SUPPRESSION,MODIFICATION',
        ], [
            // Je définis mes messages d'erreur à ma guise si la validation échoue.
            'title.string'       => 'Le titre doit être une chaîne de caractères.',
            'title.max'          => 'Le titre ne peut pas dépasser 255 caractères.',
            'description.string' => 'La description doit être une chaîne de caractères.',
            'description.max'    => 'La description ne peut pas dépasser 2000 caractères.',
            'type.string'        => 'Le type doit être une chaîne de caractères.',
            'type.in'            => 'Le type doit être l\'une des valeurs suivantes : RECLAMATION, DEMANDES, SUPPRESSION ou MODIFICATION.',
        ]);

        // Je mets à jour la demande avec les données validées (seules les données présentes dans `$validatedData` seront donc modifiées).
        $request->update($validatedData);

        // Je retourne la demande mise à jour avec un statut **200**.
        return response()->json([
            'success' => true,
            'message' => 'Demande mise à jour avec succès.',
            'data'    => $request,
        ], 200);
    }

    // --- Fonction 5 : Créer une nouvelle demande ---
    public function addRequest(Request $requestParam)
    {
        // Je commence par valider toutes les données d'entrée. Tous les champs sont **required** ici sont donc obligatoire pour un ajout.
        $validatedData = $requestParam->validate([
            'title'       => 'required|string|max:255',
            // Le type doit être présent et faire partie de la liste.
            'type'        => 'required|string|max:255|in:RECLAMATION,DEMANDES,SUPPRESSION,MODIFICATION',
            'description' => 'required|string|max:2000',
        ], [
            // Mes messages d'erreur personnalisés pour l'utilisateur.
            'title.required'       => 'Le titre est obligatoire.',
            'title.string'         => 'Le titre doit être une chaîne de caractères.',
            'title.max'            => 'Le titre ne peut pas dépasser 255 caractères.',
            'description.required' => 'La description est obligatoire.',
            'description.string'   => 'La description doit être une chaîne de caractères.',
            'description.max'      => 'La description ne peut pas dépasser 2000 caractères.',
            'type.required'        => 'Le type est obligatoire.',
            'type.string'          => 'Le type doit être une chaîne de caractères.',
            'type.in'              => 'Le type doit être l\'une des valeurs suivantes : RECLAMATION, DEMANDES, SUPPRESSION ou MODIFICATION.',
        ]);

        // Je récupère l'utilisateur **connecté** via le jeton dans le params.
        $user = $requestParam->user();

        // Je prends l'ID de l'utilisateur que je met dans la variable $userId.
        $userId = $user->id;

        // J'utilise un bloc `try-catch` pour gérer les éventuelles erreurs de base de données lors de la création.
        // ce qui est un système de sécurité !!
        try {
            // Je crée un nouvel enregistrement dans la table `requests` avec les données validées.
            $request = RequestModel::create([
                'title'       => $validatedData['title'],
                'type'        => $validatedData['type'],
                'description' => $validatedData['description'],
                'user_id'     => $userId, // J'associe la demande à l'ID de l'utilisateur connecté.
            ]);

            // Je retourne la nouvelle demande créée avec un statut **201** (Créé).
            return response()->json([
                'success' => true,
                'message' => 'Demande créée avec succès.',
                'data'    => $request,
            ], 201);
        } catch (\Exception $e) {
            // Si la création échoue pour une raison inconnue (ex: problème de DB),
            // je retourne une erreur **500** (Erreur Serveur Interne).
            return response()->json([
                'success' => false,
                'message' => 'Échec de la création de la demande.',
                'error'   => $e->getMessage(),
            ], 500); // chose qui peut arriver mais pas souvent en soit
        }
    }

    // --- Fonction 6 : Supprimer une demande ---
    public function deleteRequest(Request $requestParam, $id)
    {
        // Vérifier si l'utilisateur est connecté
        $user = $requestParam->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé.'
            ], 401);
        }

        // Rechercher la demande par son ID
        $request = RequestModel::find($id);

        // Si la demande n'existe pas
        if (!$request) {
            return response()->json([
                'success' => false,
                'message' => 'Demande non trouvée',
            ], 404);
        }

        // SÉCURITÉ : Le candidat peut supprimer sa propre demande, l'admin peut supprimer toutes les demandes.
        if ($user->role !== 'admin' && $request->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => "Accès refusé : vous n'êtes pas autorisé à supprimer cette demande."
            ], 403);
        }

        // Suppression sécurisée
        try {
            $request->delete();

            return response()->json([
                'success' => true,
                'message' => 'Demande supprimée avec succès.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Échec de la suppression de la demande',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    // --- Fonction 7 : Toggle une demande ---
    public function toggleRequest(Request $requestParam, $id)
    {

        // on verifie si l'utilisateur est bien connecté
        $user = $requestParam->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé.'
            ], 401);
        }

        // on verifie si l'utilisateur a bien le rôle ADMIN
        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => "Accès refusé : vous n'êtes pas autorisé à modifier cette demande."
            ], 403);
        }

        // Je recherche la demande à toggle par son ID.
        $request = RequestModel::find($id);
        if (!$request) {
            return response()->json([
                'success' => false,
                'message' => 'Demande non trouvée.'
            ], 404);
        }

        // on determine le nouveau statut
        // vu que le statut de base est "En_cours", on le met à "validée"
        $newStatus = ($request->status === 'En Attente') ? 'Validée' : 'En Attente';

        // on met à jour le statut dans la base de données
        $request->status = $newStatus;
        $request->save();

        // on retourne une réponse de succès
        return response()->json([
            'message' => 'Statut de la demande mis à jour avec succès.',
            'new_status' => $newStatus
        ]);
    }
}
