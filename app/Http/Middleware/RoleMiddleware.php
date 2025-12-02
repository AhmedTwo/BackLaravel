<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     * @param  string  $role
     */

    public function handle(Request $request, Closure $next, ...$allowedRoles): Response
    {
        $user = $request->user();

        // pas connecté → 401
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // rôle de l'utilisateur (string)
        $userRole = $user->role;

        // si le rôle du user n'est PAS dans la liste des rôles autorisés → 403
        if (!in_array($userRole, $allowedRoles, true)) {
            return response()->json([
                'message' => 'Authentifié. Mais pas le bon role.',
                'required_roles' => $allowedRoles,
                'your_role' => $userRole,
            ], 403);
        }

        // sinon on laisse passer
        return $next($request);
    }
}
