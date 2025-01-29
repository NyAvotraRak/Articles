<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\SearchCategorieRequest;
use App\Http\Requests\Admin\SearchProduitRequest;
use App\Models\Categorie;
use App\Models\Produit;
use Illuminate\Http\Request;

class ProduitCategorieController extends Controller
{
    // public function indexCategorie(SearchCategorieRequest $request)
    // {
    //     try {

    //         // Construire la requête de base pour récupérer les catégories
    //         $query = Categorie::with('produit')->orderBy('created_at', 'desc');
            
    //         // Appliquer le filtre sur le nom de la catégorie si fourni
    //         if ($categorie = $request->validated('recherche')) {
    //             $query = $query->where('nom_categorie', 'like', "%{$categorie}%");
    //         }
    
    //         // Exécuter la requête pour récupérer les résultats
    //         $categories = $query->get();
    //         // dd($categories);

    //         // Vérifier si aucune catégorie n'est disponible
    //         if ($categories->isEmpty()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Aucune catégorie disponible.',
    //             ], 404);
    //         }
    
    //         // Retourner les données sous forme de JSON avec code 200
    //         return response()->json([
    //             'success' => true,
    //             'categories' => $categories,
    //             'total_categorie' => $categories->count(),
    //             'recherche' => $request->validated('recherche'),
    //         ], 200);
    
    //     } catch (\Exception $e) {
    //         // En cas d'erreur, retourner une erreur 400 avec le message d'exception
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Une erreur est survenue lors de la récupération des catégories.',
    //             'error' => $e->getMessage(), // Optionnel, pour plus de détails
    //         ], 400);
    //     }
    // }
    public function indexCategorie(SearchCategorieRequest $request)
    {
        try {
            // Construire la requête de base pour récupérer les catégories avec leurs produits
            $query = Categorie::with('produits')->orderBy('created_at', 'desc');

            // Appliquer le filtre sur le nom de la catégorie si fourni
            if ($request->filled('recherche')) {
                $categorie = $request->validated('recherche');
                $query->where('nom_categorie', 'like', "%{$categorie}%");
            }

            // Exécuter la requête pour récupérer les résultats
            $categories = $query->get();

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
                'recherche' => $request->validated('recherche'),
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


    public function showCategorie($id)
    {
        try {
            // Rechercher la catégorie demandée avec ses produits
            $categorie = Categorie::with('produits')->find($id);
    
            // Vérifier si la catégorie existe
            if (!$categorie) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune catégorie trouvée pour cet identifiant.',
                ], 404);
            }
    
            // Retourner la catégorie trouvée avec ses produits
            return response()->json([
                'success' => true,
                'categorie' => $categorie,
            ], 200);
    
        } catch (\Exception $e) {
            // En cas d'erreur, retourner une réponse d'erreur avec le message d'exception
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération de la catégorie.',
                'error' => $e->getMessage(),
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

    public function showProduit($id)
    {
        try {
            // Rechercher le produit demandé avec sa catégorie associée
            $produit = Produit::with('categorie')->find($id);

            // Vérifier si le produit existe
            if (!$produit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun produit trouvé pour cet identifiant.',
                ], 404);
            }

            // Retourner le produit trouvé avec sa catégorie
            return response()->json([
                'success' => true,
                'produit' => $produit,
            ], 200);

        } catch (\Exception $e) {
            // En cas d'erreur, retourner une réponse d'erreur avec le message d'exception
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération du produit.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

}
