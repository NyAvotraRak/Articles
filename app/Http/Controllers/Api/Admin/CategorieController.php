<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategorieRequest;
use App\Http\Requests\Admin\SearchCategorieRequest;
use App\Models\Categorie;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CategorieController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(SearchCategorieRequest $request)
    {
        // $mdp = 1234567;
        // // Sauvegarder l'utilisateur dans la base de données
        // $user = User::create([
        //     'nom_utilisateur' => 'azer',
        //     'prenom_utilisateur' => 'sdf',
        //     'email' => 'k@gmail.com',
        //     'mot_de_passe' => Hash::make($mdp),
        //     'validation_code' => '1234',
        //     'est_verifie' => true
        // ]);
        // dd($request->validated('nom_categorie'));
        try {

            // Construire la requête de base pour récupérer les catégories
            $query = Categorie::query()->orderBy('created_at', 'desc');
            
            // Appliquer le filtre sur le nom de la catégorie si fourni
            if ($categorie = $request->validated('nom_categorie')) {
                $query = $query->where('nom_categorie', 'like', "%{$categorie}%");
            }
    
            // Exécuter la requête pour récupérer les résultats
            $categories = $query->get();
            // dd($categories);

            // Vérifier si aucune catégorie n'est disponible
            if ($categories->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune catégorie disponible.',
                ], 404);
            }
    
            // Retourner les données sous forme de JSON avec code 200
            return response()->json([
                'success' => true,
                'categories' => $categories,
                'total_categorie' => $categories->count(),
                'input' => $request->input('nom_categorie'),
            ], 200);
    
        } catch (\Exception $e) {
            // En cas d'erreur, retourner une erreur 400 avec le message d'exception
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération des catégories.',
                'error' => $e->getMessage(), // Optionnel, pour plus de détails
            ], 400);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategorieRequest $request)
    {
        // dd($request->input(['nom_categorie']));
        try {
            // Tenter de créer la catégorie
            $categorie = Categorie::create($request->validated());
            // dd($categorie);
    
            // Retourner une réponse 201 en cas de succès
            return response()->json([
                'success' => true,
                'message' => 'La catégorie a bien été créée',
                // 'categorie' => $categorie,
                'redirect_url' => route('admin.categorie.index'),
            ], 201, [], JSON_UNESCAPED_SLASHES);
        } catch (\Exception $e) {
            // Retourner une réponse 400 en cas d'erreur
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la catégorie',
                'error' => $e->getMessage(), // Facultatif : pour des détails sur l'erreur
            ], 400);
        }
    }
    public function edit($id)
    {
        try {
            // Récupérer la catégorie par son ID
            $categorie = Categorie::findOrFail($id);

            // Si vous travaillez avec une API, retourner les données en JSON
            return response()->json([
                'success' => true,
                'categorie' => $categorie,
            ], 200);

        } catch (\Exception $e) {
            // Gérer les erreurs, par exemple si l'ID n'existe pas
            return response()->json([
                'success' => false,
                'message' => 'Erreur : catégorie non trouvée.',
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategorieRequest $request, $id)
    {
        try {
            // Récupérer la catégorie à mettre à jour
            $categorie = Categorie::findOrFail($id);

            // Mettre à jour la catégorie avec les données validées
            $categorie->update($request->validated());

            // Retourner une réponse en cas de succès
            return response()->json([
                'success' => true,
                'message' => 'La catégorie a bien été mise à jour',
                // 'categorie' => $categorie,
                'redirect_url' => route('admin.categorie.index'),
            ], 200, [], JSON_UNESCAPED_SLASHES);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Gérer le cas où la catégorie n'est pas trouvée
            return response()->json([
                'success' => false,
                'message' => 'Erreur : catégorie non trouvée.',
            ], 404);
        } catch (\Exception $e) {
            // Gérer toute autre erreur
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la catégorie.',
                'error' => $e->getMessage(), // Optionnel pour plus de détails
            ], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            // Récupérer la catégorie à supprimée
            $categorie = Categorie::findOrFail($id);

            // Suppression de la catégorie
            $categorie->delete();
    
            // Réponse en cas de succès
            return response()->json([
                'success' => true,
                'message' => 'La catégorie a bien été supprimée',
                'redirect_url' => route('admin.categorie.index'),
            ], 200, [], JSON_UNESCAPED_SLASHES);
        } catch (\Exception $e) {
            // Réponse en cas d'erreur
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la catégorie',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
