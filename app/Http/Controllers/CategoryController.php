<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Administrateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    // SUPPRIME COMPLÈTEMENT LE CONSTRUCTEUR
    // public function __construct()
    // {
    //     $this->middleware('auth:sanctum')->except(['index', 'show', 'books']);
    // }

    public function index()
    {
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

    private function checkAdmin()
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'administrateur') {
            return false;
        }
        return $user;
    }

    public function store(Request $request)
    {
        $user = $this->checkAdmin();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Vous devez être administrateur.'
            ], 403);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|unique:categories|max:255',
            'description' => 'nullable|string'
        ]);

        $admin = Administrateur::find($user->id);
        $category = $admin->ajouterCategory($validated);

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
        $user = $this->checkAdmin();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Vous devez être administrateur.'
            ], 403);
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|string|unique:categories,name,' . $category->id . '|max:255',
            'description' => 'nullable|string'
        ]);

        $admin = Administrateur::find($user->id);
        $admin->modifierCategory($category, $validated);

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
        $user = $this->checkAdmin();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Vous devez être administrateur.'
            ], 403);
        }
        
        if ($category->books()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une catégorie qui contient des livres'
            ], 400);
        }

        $admin = Administrateur::find($user->id);
        $admin->supprimerCategory($category);

        return response()->json([
            'success' => true,
            'message' => 'Catégorie supprimée avec succès'
        ]);
    }

    public function books(Request $request, $categorySlug)
    {
        $category = Category::where('slug', $categorySlug)->firstOrFail();
        
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