<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            // Retourne une réponse JSON avec le code HTTP 401 (Non autorisé)
            abort(response()->json([
                'error' => 'Non authentifié',
                'message' => 'Vous devez être connecté pour accéder à cette ressource.',
            ], 401));
        }
    
        return null; // Pas de redirection
    }
}
