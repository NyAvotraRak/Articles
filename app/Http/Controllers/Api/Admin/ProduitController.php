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
        // dd($request);
        try {
            // Récupérer toutes les données de Produit avec Categorie
            $query = Produit::with('categorie')->orderBy('created_at', 'desc');
            // dd($produits);

            // Appliquer le filtre sur le nom du produit si fourni
            if ($nom_produit = $request->validated('nom_produit')) {
                $query = $query->where('nom_produit', 'like', "%{$nom_produit}%");
            }

            // Appliquer le filtre sur la description du produit si fourni
            if ($description_produit = $request->validated('description_produit')) {
                $query = $query->where('description_produit', 'like', "%{$description_produit}%");
            }

            // Appliquer le filtre sur le prix du produit si fourni
            if ($prix = $request->validated('prix')) {
                $query = $query->where('prix', '<=', $prix);
            }

            // Exécuter la requête pour récupérer les résultats
            $produits = $query->get();
            // dd($produits);
    
            // Vérifier si aucun produit n'est disponible
            if ($produits->isEmpty()) {
                // dd($produits);
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
                'input_nom_produit' => $request->input('nom_produit'),
                'input_description_produit' => $request->input('description_produit'),
                'input_prix' => $request->input('prix'),
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
     */
    public function create()
    {
        try {
            
            $produit = new Produit();

            // Récupérer les catégories avec leurs ID et noms
            $categories = Categorie::pluck('nom_categorie', 'id');
            // dd($categories);
    
            // Vérifier si aucune catégorie n'est disponible
            if ($categories->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune catégorie disponible.',
                ], 404);
            }
    
            // Retourner les catégories sous forme de JSON
            return response()->json([
                'success' => true,
                'categories' => $categories,
                'produit' => $produit,
            ], 200);
    
        } catch (\Exception $e) {
            // Gérer les erreurs et retourner une réponse JSON
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération des catégories.',
                'error' => $e->getMessage(), // Optionnel pour débogage
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProduitRequest $request)
    {
        // dd($request);
        try {
            // Tenter de créer la catégorie
            $produit = Produit::create($this->extract_data(new Produit(), $request));
            // dd($produit);
    
            // Retourner une réponse 201 en cas de succès
            return response()->json([
                'success' => true,
                'message' => 'Le produit a bien été créé',
                // 'categorie' => $categorie,
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
            // dd($produit);
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
        // $data = $request->validated();
        // dd($data);
        try {
            // Récupérer le produit à mettre à jour
            $produit = Produit::findOrFail($id);
            // dd($produit);
            $data = $this->extract_data($produit, $request);
            // Vérifier si un nouveau fichier d'image a été téléchargé
            /*if ($request->hasFile('image_produit')) {
                // Supprimer l'ancienne image si elle existe
                if ($produit->image_produit) {
                    Storage::disk('public')->delete($produit->image_produit);
                }
                // Enregistrer le nouveau fichier d'image et mettre à jour le chemin dans les données
                $data['image_ministere'] = $request->file('image_produit')->store('image', 'public');
            }*/

            // Mettre à jour le produit avec les données validées
            $produit->update($data);
            // dd($produit);

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
            // dd($produit);
                
            // Vérifier si une image existe et la supprimer
            if ($produit->image_produit) {
                Storage::disk('public')->delete($produit->image_produit);
            }

            // Suppression du produit
            $produit->delete();
            // dd();
    
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
