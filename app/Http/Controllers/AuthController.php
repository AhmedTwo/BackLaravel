<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {

        // On valide les champs
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        // Vérifier si l'utilisateur existe // On récupère le 1er qui sort.
        $user = User::where('email', $request->email)->first();

        // Erreur SI utilisateur non trouvé OU si le mot de passe de la requête correspond à celui du User
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email ou mot de passe incorrect'
            ], 401);
        }

        // if (!$user->role == 'admin') {
        //     return back()->withErrors(['email' => 'Connexion inaccessible']);;
        // }

        // Générer un token pour l'utilisateur avec Sanctum
        $token = $user->createToken('userToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'user'    => $user,
            'token'   => $token
        ], 200);
    }
}
