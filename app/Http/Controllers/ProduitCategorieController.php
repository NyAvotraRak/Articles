<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\SearchCategorieRequest;
use App\Http\Requests\Admin\SearchProduitRequest;
use App\Models\Categorie;
use App\Models\Produit;
use Illuminate\Http\Request;

class ProduitCategorieController extends Controller
{
    public function indexCategorie(SearchCategorieRequest $id)
    {
        
        try {
            // Initialiser la requête en incluant les produits liés à chaque catégorie
            // et en triant par ordre décroissant de date de création
            $query = Categorie::with('produits')->orderBy('created_at', 'desc');
        
            // Vérifier si une catégorie spécifique est demandée via un ID dans la requête
            if ($id->filled('id')) {
                // Chercher la catégorie correspondant à l'ID fourni
                $categorie = Categorie::with('produits')->find($id->id);
        
                // Vérifier si la catégorie existe
                if (!$categorie) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Aucune catégorie disponible.',
                    ], 404);
                }
        
                // Retourner la catégorie trouvée avec ses produits au format JSON
                return response()->json([
                    'success' => true,
                    'categorie' => $categorie,
                    'id' => $id->validated('id'),
                ], 200);
            }
            // Si aucun ID n'est fourni, récupérer toutes les catégories avec leurs produits
            $categories = $query->get();
    
            // Retourner les données sous forme de JSON avec code 200
            return response()->json([
                'success' => true,
                'categories' => $categories,
                'total_categorie' => $categories->count()
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

    public function indexProduit(SearchProduitRequest $request)
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
}
