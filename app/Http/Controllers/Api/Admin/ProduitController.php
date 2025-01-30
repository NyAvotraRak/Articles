<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategorieRequest;
use App\Http\Requests\Admin\ProduitRequest;
use App\Http\Requests\Admin\SearchProduitRequest;
use App\Models\Categorie;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProduitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(SearchProduitRequest $request)
    {
        try {
            // Récupérer le mot saisi
            $recherche = $request->validated('recherche');

            // Récupérer toutes les données de Produit avec Categorie
            $query = Produit::with('categorie')->orderBy('created_at', 'desc');

            // Appliquer les différents filtres
            if ($recherche) {
                $query->where(function ($q) use ($recherche) {
                    $q->where('nom_produit', 'like', "%{$recherche}%")
                    ->orWhere('description_produit', 'like', "%{$recherche}%")
                    ->orWhere('prix', '<=', $recherche);
                });
            }

            // Exécuter la requête pour récupérer les résultats
            $produits = $query->get();
    
            // Vérifier si aucun produit n'est disponible
            if ($produits->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun produit disponible.',
                ], 404);
            }
    
            // Retourner les données sous forme de JSON avec code 200
            return response()->json([
                'success' => true,
                'produits' => $produits,
                'total_produit' => $produits->count(),
                'recherche' => $request->validated('recherche'),
            ], 200);
    
        } catch (\Exception $e) {
            // En cas d'erreur, retourner une erreur 400 avec le message d'exception
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération des produits.',
                'error' => $e->getMessage(), // Optionnel, pour plus de détails
            ], 400);
        }
    }
    
    /**
     * Show the form for creating a new resource.
    //  */
    // public function create()
    // {
    //     try {
            
    //         $produit = new Produit();

    //         // Récupérer les catégories avec leurs ID et noms
    //         $categories = Categorie::pluck('nom_categorie', 'id');
    //         // dd($categories);
    
    //         // Vérifier si aucune catégorie n'est disponible
    //         if ($categories->isEmpty()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Aucune catégorie disponible.',
    //             ], 404);
    //         }
    
    //         // Retourner les catégories sous forme de JSON
    //         return response()->json([
    //             'success' => true,
    //             'categories' => $categories,
    //             'produit' => $produit,
    //         ], 200);
    
    //     } catch (\Exception $e) {
    //         // Gérer les erreurs et retourner une réponse JSON
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Une erreur est survenue lors de la récupération des catégories.',
    //             'error' => $e->getMessage(), // Optionnel pour débogage
    //         ], 500);
    //     }
    // }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProduitRequest $request)
    {
        try {
            // Récupérer toutes les catégories sans filtrage et sans tri
            $query = Categorie::query();

            // Appliquer le filtre sur le nom du produit si fourni
            if ($nom_categorie = $request->validated('categorie_nom')) {
                $query = $query->where('nom_categorie', $nom_categorie);
            }

            $categorie = $query->first();

            if(!$categorie){
                // Sauvegarder la categorie dans la base de données
                $categorie = Categorie::create([
                    'nom_categorie' => $request->validated('categorie_nom')
                ]);
            }

            $data = $this->extract_data(new Produit(), $request);
            // Tenter de créer la catégorie
            // dd($data);
            $produit = Produit::create([
                'nom_produit' => $data['nom_produit'],
                'description_produit' => $data['description_produit'],
                'image_produit' => $data['image_produit'],
                'prix' => $data['prix'],
                'categorie_id' => $categorie->id
            ]);
    
            // Retourner une réponse 201 en cas de succès
            return response()->json([
                'success' => true,
                'message' => 'Le produit a bien été créé',
                'produit' => $produit,
                'redirect_url' => route('admin.produit.index'),
            ], 201, [], JSON_UNESCAPED_SLASHES);
        } catch (\Exception $e) {
            // Retourner une réponse 400 en cas d'erreur
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du produit',
                'error' => $e->getMessage(), // Facultatif : pour des détails sur l'erreur
            ], 400);
        }
    }
    public function edit($id)
    {
        try {
            // Récupérer le produit par son ID
            $produit = Produit::findOrFail($id);

            // Récupérer les catégories avec leurs ID et noms
            $categories = Categorie::pluck('nom_categorie', 'id');

            // Si vous travaillez avec une API, retourner les données en JSON
            return response()->json([
                'success' => true,
                'produit' => $produit,
                'categories' => $categories
            ], 200);

        } catch (\Exception $e) {
            // Gérer les erreurs, par exemple si l'ID n'existe pas
            return response()->json([
                'success' => false,
                'message' => 'Erreur : Produit non trouvé.',
            ], 404);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(ProduitRequest $request, $id)
    {
        // dd($id, $request);
        try {
            // Récupérer toutes les catégories sans filtrage et sans tri
            $query = Categorie::query();

            // Appliquer le filtre sur le nom du produit si fourni
            if ($nom_categorie = $request->validated('categorie_nom')) {
                $query = $query->where('nom_categorie', $nom_categorie);
            }

            $categorie = $query->first();

            if(!$categorie){
                // Sauvegarder la categorie dans la base de données
                $categorie = Categorie::create([
                    'nom_categorie' => $request->validated('categorie_nom')
                ]);
            }
            // Récupérer le produit à mettre à jour
            $produit = Produit::findOrFail($id);
            // dd($produit);
            $data = $this->extract_data($produit, $request);

            // Si l'image_produit n'est pas présente dans la requête, conserver l'ancienne image
            if (empty($data['image_produit'])) {
                $data['image_produit'] = $produit->image_produit;
            }

            // Mettre à jour le produit avec les données validées
            $produit->update([
                'nom_produit' => $data['nom_produit'],
                'description_produit' => $data['description_produit'],
                'image_produit' => $data['image_produit'],
                'prix' => $data['prix'],
                'categorie_id' => $categorie->id
            ]);

            // Retourner une réponse en cas de succès
            return response()->json([
                'success' => true,
                'message' => 'Le produit a bien été mise à jour',
                'produit' => $produit,
                'redirect_url' => route('admin.produit.index'),
            ], 200, [], JSON_UNESCAPED_SLASHES);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Gérer le cas où le produit n'est pas trouvé
            return response()->json([
                'success' => false,
                'message' => 'Erreur : produit non trouvé.',
            ], 404);
        } catch (\Exception $e) {
            // Gérer toute autre erreur
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du produit.',
                'error' => $e->getMessage(), // Optionnel pour plus de détails
            ], 400);
        }
    }
    
    private function extract_data(Produit $produit, ProduitRequest $request)
    {
        $data = $request->validated();
        /** @var Uploadedfile|null $image_produit */
        $image_produit = $request->validated('image_produit');
        // dd($image_produit);
        if ($image_produit == null || $image_produit->getError()) {
            return $data;
        }
        if ($produit->image_produit) {
            Storage::disk('public')->delete($produit->image_produit);
        }
        $data['image_produit'] = $image_produit->store('image', 'public');
        return $data;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            // Récupérer le produit à supprimé
            $produit = Produit::findOrFail($id);
                
            // Vérifier si une image existe et la supprimer
            if ($produit->image_produit) {
                Storage::disk('public')->delete($produit->image_produit);
            }

            // Suppression du produit
            $produit->delete();
    
            // Réponse en cas de succès
            return response()->json([
                'success' => true,
                'message' => 'Le produit a bien été supprimé',
                'redirect_url' => route('admin.produit.index'),
            ], 200, [], JSON_UNESCAPED_SLASHES);
        } catch (\Exception $e) {
            // Réponse en cas d'erreur
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du produit',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
