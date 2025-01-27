<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Mail\ContactUserMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function register(RegisterUserRequest $request)
    {
        try {
            // Générer un code de validation aléatoire
            $code_validation = Str::random(6); // Code de 6 caractères
            // dd($validationCode);

            // Stocker les données utilisateur dans le cache
            $cle_cache = 'inscription_utilisateur_' . $request->email;
            // dd($cle_cache);
            Cache::put($cle_cache, [
                'nom_utilisateur' => $request->validated('nom_utilisateur'),
                'prenom_utilisateur' => $request->validated('prenom_utilisateur'),
                'email' => $request->validated('email'),
                'mot_de_passe' => Hash::make($request->validated('mot_de_passe')),
                'validation_code' => $code_validation,
            ], now()->addMinutes(10)); // Expire après 10 minutes

            // Envoyer l'email avec le code de validation
            Mail::to($request->validated('email'))->send(new ContactUserMail($code_validation));

            // Retourner une réponse JSON de succès
            return response()->json([
                'message' => 'Un email de validation a été envoyé. Veuillez vérifier votre boîte de réception.',
            ], 200);

        } catch (\Exception $erreur) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de l\'inscription.',
                'erreur' => $erreur->getMessage(),
            ], 500);
        }
    }

    public function login(LoginUserRequest $request)
    {
        try {
            // Recherche de l'utilisateur
            $user = User::where('email', $request->validated('email'))->first();
    
            // Vérification des identifiants
            if (! $user || ! Hash::check($request->validated('mot_de_passe'), $user->mot_de_passe)) {
                throw ValidationException::withMessages([
                    'email' => ['Les identifiants fournis sont incorrects.'],
                ]);
            }

            // Vérification si le compte est vérifié
            if (!$user->est_verifie) {
                return response()->json([
                    'message' => 'Votre compte n\'a pas encore été vérifié.',
                ], 403); // Code HTTP 403 : Accès interdit
            }
    
            // Création d'un token d'accès
            $token = $user->createToken('auth_token')->plainTextToken;
    
            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);
        } catch (ValidationException $e) {
            // Gestion des erreurs de validation
            return response()->json([
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Gestion des autres exceptions
            return response()->json([
                'message' => 'Une erreur est survenue lors de la connexion.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function logout(Request $request)
    {
        try {
            // Suppression du token d'accès actuel
            $request->user()->currentAccessToken()->delete();

            // Retourner une réponse de succès
            return response()->json(['message' => 'Déconnexion réussie']);
        } catch (\Exception $e) {
            // Capturer toute exception et renvoyer une réponse d'erreur
            return response()->json(['error' => 'Une erreur est survenue lors de la déconnexion.'], 500);
        }
    }

    public function validateCode(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'validation_code' => 'required|string',
            ]);
        
            // Récupérer les données utilisateur dans le cache
            $cle_cache = 'inscription_utilisateur_' . $request->email;
            $donnees_utilisateur  = Cache::get($cle_cache);
        
            if (!$donnees_utilisateur) {
                return response()->json([
                    'message' => 'Aucune demande d\'inscription trouvée ou le code a expiré.',
                ], 400);
            }
        
            // Vérifier si le code est correct
            if ($donnees_utilisateur['validation_code'] !== $request->validation_code) {
                return response()->json([
                    'message' => 'Le code de validation est incorrect.',
                ], 400);
            }
        
            // Sauvegarder l'utilisateur dans la base de données
            $user = User::create([
                'nom_utilisateur' => $donnees_utilisateur['nom_utilisateur'],
                'prenom_utilisateur' => $donnees_utilisateur['prenom_utilisateur'],
                'email' => $donnees_utilisateur['email'],
                'mot_de_passe' => $donnees_utilisateur['mot_de_passe'],
                'validation_code' => $donnees_utilisateur['validation_code'],
                'est_verifie' => true
            ]);
        
            // Supprimer les données du cache
            Cache::forget($cle_cache);
        
            return response()->json([
                'message' => 'Compte créé avec succès.',
                'user' => $user,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $erreur_de_validation) {
            return response()->json([
                'message' => 'Erreur de validation.',
                'erreurs' => $erreur_de_validation->errors(),
            ], 422);
        } catch (\Exception $erreur_generale) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la validation.',
                'erreur' => $erreur_generale->getMessage(),
            ], 500);
        }
    }

}
