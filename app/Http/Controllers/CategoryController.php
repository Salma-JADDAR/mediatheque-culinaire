<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Administrateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CategoryController extends Controller
{
    use AuthorizesRequests; // ← Ajoute cette ligne pour utiliser les policies

    public function index()
    {
        // Policy: viewAny - Tout le monde peut voir les catégories
        $this->authorize('viewAny', Category::class);

        $categories = Category::withCount('books')
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'nombre_livres' => $category->books_count,
                    'url' => url("/api/categories/{$category->slug}")
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function show($slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        
        // Policy: view - Tout le monde peut voir une catégorie
        $this->authorize('view', $category);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'created_at' => $category->created_at
            ]
        ]);
    }

    public function store(Request $request)
    {
        // Policy: create - Seulement les admins peuvent créer
        $this->authorize('create', Category::class);

        $validated = $request->validate([
            'name' => 'required|string|unique:categories|max:255',
            'description' => 'nullable|string'
        ]);

        $category = Category::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Catégorie créée avec succès',
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description
            ]
        ], 201);
    }

    public function update(Request $request, Category $category)
    {
        // Policy: update - Seulement les admins peuvent modifier
        $this->authorize('update', $category);

        $validated = $request->validate([
            'name' => 'sometimes|string|unique:categories,name,' . $category->id . '|max:255',
            'description' => 'nullable|string'
        ]);

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Catégorie modifiée avec succès',
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description
            ]
        ]);
    }

    public function destroy(Category $category)
    {
        // Policy: delete - Vérifie si admin ET si pas de livres
        $this->authorize('delete', $category);
        
        // La policy a déjà vérifié s'il y a des livres
        // Mais on garde une vérification supplémentaire
        if ($category->books()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une catégorie qui contient des livres'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Catégorie supprimée avec succès'
        ]);
    }

    public function books(Request $request, $categorySlug)
    {
        $category = Category::where('slug', $categorySlug)->firstOrFail();
        
        // Policy: viewBooks - Tout le monde peut voir les livres d'une catégorie
        $this->authorize('viewBooks', $category);
        
        $filtres = [
            'search' => $request->search,
            'sort' => $request->sort,
            'condition' => $request->condition
        ];
        
        $books = $category->getLivres($filtres);
        
        return response()->json([
            'success' => true,
            'category' => $category->name,
            'total' => $books->count(),
            'data' => $books->map(function ($book) {
                return $book->afficherDetails();
            })
        ]);
    }
}