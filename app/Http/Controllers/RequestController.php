<?php

namespace App\Http\Controllers;

use App\Models\Request as RequestModel;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    // recup toutes nos request !
    public function getRequest()
    {
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
            ->join('users', 'requests.user_id', '=', 'users.id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ], 201);
    }

    public function getRequestById($id)
    {

        $request = RequestModel::find($id);

        if (!$request) {
            return response()->json([
                'succes' => false,
                'message' => "Demande non trouvée"
            ], 404);
        }

        return response()->json([
            'succes' => true,
            'message' => 'Demande trouvée',
            'data' => $request
        ], 200);
    }

    /**
     * Récupère toutes les demandes liées à un ID utilisateur spécifique.
     * C'est la fonction nécessaire pour la vue 'Mes Demandes'.
     *
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRequestsByUser($userId)
    {
        // Jointures pour obtenir les détails de l'utilisateur/créateur de la demande
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
            ->join('users', 'requests.user_id', '=', 'users.id')
            ->where('requests.user_id', $userId)
            ->get();

        if ($data->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Aucune demande trouvée pour cet utilisateur.',
                'data' => []
            ], 200);
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }

    public function updateRequest(Request $requestParam, $id)
    {
        $request = RequestModel::find($id);

        if (!$request) {
            return response()->json([
                'success' => false,
                'message' => 'Demande non trouvée.',
            ], 404);
        }

        $validatedData = $requestParam->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:2000',
            'type'        => 'sometimes|string|max:255|in:RECLAMATION,DEMANDES,SUPPRESSION,MODIFICATION',
        ], [
            'title.string'       => 'Le titre doit être une chaîne de caractères.',
            'title.max'          => 'Le titre ne peut pas dépasser 255 caractères.',
            'description.string' => 'La description doit être une chaîne de caractères.',
            'description.max'    => 'La description ne peut pas dépasser 2000 caractères.',
            'type.string'        => 'Le type doit être une chaîne de caractères.',
            'type.in'            => 'Le type doit être l\'une des valeurs suivantes : RECLAMATION, DEMANDES, SUPPRESSION ou MODIFICATION.',
        ]);

        $request->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Demande mise à jour avec succès.',
            'data'    => $request,
        ], 200);
    }

    public function addRequest(Request $requestParam)
    {
        $validatedData = $requestParam->validate([
            'title'       => 'required|string|max:255',
            'type'        => 'required|string|max:255|in:RECLAMATION,DEMANDES,SUPPRESSION,MODIFICATION',
            'description' => 'required|string|max:2000',
        ], [
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

        // on recup l'utilisateur connecté via le token
        $user = $requestParam->user();

        // on recup l'ID de la compagnie rattachée à cet utilisateur
        $companyId = $user->id;

        try {
            $request = RequestModel::create([
                'title'       => $validatedData['title'],
                'type'        => $validatedData['type'],
                'description' => $validatedData['description'],
                'user_id'     => $companyId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Demande créée avec succès.',
                'data'    => $request,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Échec de la création de la demande.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteRequest($id)
    {

        $request = RequestModel::find($id);

        if (!$request) {

            return response()->json([
                'success' => false,
                'message' => 'Demande non trouvé',
            ], 404);
        }

        try {
            $request->delete();

            return  response()->json([
                'success' => true,
                'message' => 'Demande supprimé avec succès.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Echec de la suppression de la demande',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
