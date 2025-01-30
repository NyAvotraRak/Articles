<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Http\Requests\Auth\UpdateUserRequest;
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

    public function index()
    {
        try {

            // Construire la requête de base pour récupérer les utilisateurs
            $users = User::select('id', 'nom_utilisateur', 'prenom_utilisateur', 'email', 'telephones')
                            ->get();

            // Retourner les données sous forme de JSON avec code 200
            return response()->json([
                'success' => true,
                'users' => $users,
            ], 200);
    
        } catch (\Exception $e) {
            // En cas d'erreur, retourner une erreur 400 avec le message d'exception
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération des utilisateurs.',
                'error' => $e->getMessage(), // Optionnel, pour plus de détails
            ], 400);
        }

    }

    public function register(RegisterUserRequest $request)
    {
        // dd($request);
        try {

            // Vérifier si un compte existe déjà dans la base de données
            $compte_existant = User::exists();
            // dd($compte_existant);

            if ($compte_existant) {
                return response()->json([
                    'message' => 'Un compte existe déjà dans le système. Il est impossible de créer un autre compte.',
                ], 403); // 403 Forbidden
            }

            // Générer un code de validation aléatoiree
            $code_validation = Str::random(6); // Code de 6 caractères
            // dd($validationCode);

            // Stocker les données utilisateur dans le cache
            $cle_cache = 'inscription_utilisateur_' . $request->email;

            Cache::put($cle_cache, [
                'nom_utilisateur' => $request->validated('nom_utilisateur'),
                'prenom_utilisateur' => $request->validated('prenom_utilisateur'),
                'email' => $request->validated('email'),
                'mot_de_passe' => Hash::make($request->validated('mot_de_passe')),
                'validation_code' => $code_validation,
                'telephones' => $request->validated('telephones'), // Enregistrer les téléphones dans le cache
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
    
    public function update(UpdateUserRequest $request, $id)
    {
        // dd($request);
        try {
            // Récupérer l'utilisateur à mettre à jour
            $user = User::findOrFail($id);

            // Générer un code de validation aléatoiree
            $code_validation = Str::random(6); // Code de 6 caractères
    
            // Récupérer les données validées
            $data = $request->validated();
    
            // Si le mot de passe est fourni, le hacher
            if (!empty($data['mot_de_passe'])) {
                $data['mot_de_passe'] = $data['mot_de_passe'];
            } else {
                // Supprimer la clé 'mot_de_passe' pour éviter une mise à jour à null
                unset($data['mot_de_passe']);
            }
            // dd($data['mot_de_passe']);
            
            // Si 'telephones' existe dans les données, on l'ajoute
            if (isset($data['telephones'])) {
                $user->telephones = $data['telephones'];
            }
    
            // Stocker les données dans le cache avant de les valider
            $cle_cache = 'modification_utilisateur_' . $data['email'];
            // dd($cle_cache);
            /*dd([
                
                'id' => $user->id,
                'nom_utilisateur' => $data['nom_utilisateur'],
                'prenom_utilisateur' => $data['prenom_utilisateur'],
                'email' => $data['email'],
                'telephones' => isset($data['telephones']) ? $data['telephones'] : $user->telephones, // Si 'telephones' est absent, on garde l'ancien
                'mot_de_passe' => isset($data['mot_de_passe']) ? Hash::make($data['mot_de_passe']) : $user->mot_de_passe,
            ]);*/
            Cache::put($cle_cache, [
                'id' => $user->id,
                'nom_utilisateur' => $data['nom_utilisateur'],
                'prenom_utilisateur' => $data['prenom_utilisateur'],
                'email' => $data['email'],
                'validation_code' => $code_validation,
                'telephones' => isset($data['telephones']) ? $data['telephones'] : $user->telephones, // Si 'telephones' est absent, on garde l'ancien
                'mot_de_passe' => isset($data['mot_de_passe']) ? $data['mot_de_passe'] : $user->mot_de_passe,
            ], now()->addMinutes(10)); // Expire après 10 minutes
            
            // Envoyer l'email avec le code de validation
            Mail::to($data['email'])->send(new ContactUserMail($code_validation));

            // Retourner une réponse JSON pour informer l'utilisateur de vérifier son email
            return response()->json([
                'hash' => $data['mot_de_passe'],
                'message' => 'Un email de validation a été envoyé. Veuillez vérifier votre boîte de réception.',
            ], 200);

            // Mettre à jour l'utilisateur avec les données restantes
            // $user->update($data);
    
            // Retourner une réponse en cas de succès
            return response()->json([
                'success' => true,
                'message' => 'L\'utilisateur a bien été mis à jour.',
                'redirect_url' => route('admin.utilisateur.index'),
            ], 200, [], JSON_UNESCAPED_SLASHES);
    
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Gérer le cas où l'utilisateur n'est pas trouvé
            return response()->json([
                'success' => false,
                'message' => 'Erreur : Utilisateur non trouvé.',
            ], 404);
        } catch (\Exception $e) {
            // Gérer toute autre erreur
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'utilisateur.',
                'error' => $e->getMessage(), // Optionnel pour plus de détails
            ], 400);
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
        
                // Vérification pour inscription
                $cle_cache_inscription = 'inscription_utilisateur_' . $request->email;
                $donnees_inscription = Cache::get($cle_cache_inscription);
        
                if ($donnees_inscription && $donnees_inscription['validation_code'] === $request->validation_code) {
                    // Création d'un nouvel utilisateur
                    $user = User::create([
                        'nom_utilisateur' => $donnees_inscription['nom_utilisateur'],
                        'prenom_utilisateur' => $donnees_inscription['prenom_utilisateur'],
                        'email' => $donnees_inscription['email'],
                        'mot_de_passe' => $donnees_inscription['mot_de_passe'],
                        'telephones' => $donnees_inscription['telephones'], // Enregistrer les téléphones
                    ]);
        
                    // Supprimer le cache
                    Cache::forget($cle_cache_inscription);
        
                    return response()->json([
                        'message' => 'Compte créé avec succès.',
                        'user' => $user,
                    ], 201);
                }
                
                // Vérification pour modification
                $cle_cache_modification = 'modification_utilisateur_' . $request->email;
                $donnees_modification = Cache::get($cle_cache_modification);
                // dd($donnees_modification['mot_de_passe']);
        
                if ($donnees_modification && $donnees_modification['validation_code'] === $request->validation_code) {
                    // Mise à jour de l'utilisateur existant
                    $user = User::findOrFail($donnees_modification['id']);
                    // dd($user);
                    $user->update([
                        'nom_utilisateur' => $donnees_modification['nom_utilisateur'],
                        'prenom_utilisateur' => $donnees_modification['prenom_utilisateur'],
                        'email' => $donnees_modification['email'],
                        'mot_de_passe' => Hash::make($donnees_modification['mot_de_passe']),
                        'telephones' => $donnees_modification['telephones'],
                    ]);
                    
                    // dd($user);
                    // Supprimer le cache
                    Cache::forget($cle_cache_modification);
        
                    return response()->json([
                        'message' => 'Compte mis à jour avec succès.',
                        'user' => $user,
                    ], 200);
                }
        
                // Si aucune clé valide n'est trouvée
                return response()->json([
                    'message' => 'Aucune demande trouvée ou code de validation incorrect.',
                ], 400);
        
            } catch (\Illuminate\Validation\ValidationException $validationException) {
                return response()->json([
                    'message' => 'Erreur de validation.',
                    'errors' => $validationException->errors(),
                ], 422);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Une erreur est survenue lors de la validation.',
                    'error' => $exception->getMessage(),
                ], 500);
            }
        }
        

}
