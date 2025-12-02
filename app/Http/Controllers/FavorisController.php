<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
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

    public function addFavoris() {}

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
}
