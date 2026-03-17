<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\Administrateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookController extends Controller
{
    public function __construct()
    {
        // Dans Laravel 11, les middlewares fonctionnent différemment
        // On peut les définir dans les routes directement
    }

    public function index(Request $request, $categorySlug = null)
    {
        if ($categorySlug && $categorySlug !== 'all') {
            $category = Category::where('slug', $categorySlug)->firstOrFail();
            $books = $category->getLivres($request->all());
        } else {
            $query = Book::with('category', 'statistique');
            
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('author', 'like', "%{$search}%");
                });
            }

            if ($request->has('sort')) {
                switch ($request->sort) {
                    case 'popular':
                        $query->populaires();
                        break;
                    case 'recent':
                        $query->latest();
                        break;
                    case 'title':
                        $query->orderBy('title');
                        break;
                }
            }

            if ($request->has('condition')) {
                $query->where('condition', $request->condition);
            }

            if ($request->has('disponible') && $request->disponible) {
                $query->disponibles();
            }

            $books = $query->get();
        }

        return response()->json([
            'success' => true,
            'total' => $books->count(),
            'data' => $books->map(function ($book) {
                return $book->afficherDetails();
            })
        ]);
    }

    public function show($categorySlug, $bookSlug)
    {
        $category = Category::where('slug', $categorySlug)->firstOrFail();
        
        $book = Book::where('slug', $bookSlug)
            ->where('category_id', $category->id)
            ->with('category', 'statistique')
            ->firstOrFail();

        $book->incrementViews();

        return response()->json([
            'success' => true,
            'data' => $book->afficherDetails()
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
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'description' => 'required|string',
            'total_copies' => 'required|integer|min:1',
            'category_id' => 'required|exists:categories,id'
        ]);

        $admin = Administrateur::find($user->id);
        $book = $admin->ajouterLivre($validated);

        return response()->json([
            'success' => true,
            'message' => 'Livre ajouté avec succès',
            'data' => $book->afficherDetails()
        ], 201);
    }

    public function update(Request $request, Book $book)
    {
        $user = $this->checkAdmin();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Vous devez être administrateur.'
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'author' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'condition' => 'sometimes|in:neuf,bon,moyen,degrade',
            'total_copies' => 'sometimes|integer|min:1',
            'available_copies' => 'sometimes|integer|min:0|max:' . ($book->total_copies ?? PHP_INT_MAX)
        ]);

        if (isset($validated['condition'])) {
            $book->marquerCommeDegrade($validated['condition']);
            unset($validated['condition']);
        }

        if (isset($validated['total_copies'])) {
            $oldTotal = $book->total_copies;
            $oldAvailable = $book->available_copies;
            
            if ($validated['total_copies'] > $oldTotal) {
                $validated['available_copies'] = $oldAvailable + ($validated['total_copies'] - $oldTotal);
            } elseif ($validated['total_copies'] < $oldTotal) {
                $newAvailable = max(0, $oldAvailable - ($oldTotal - $validated['total_copies']));
                $validated['available_copies'] = $newAvailable;
            }
        }

        $admin = Administrateur::find($user->id);
        $admin->modifierLivre($book, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Livre modifié avec succès',
            'data' => $book->fresh()->afficherDetails()
        ]);
    }

    public function destroy(Book $book)
    {
        $user = $this->checkAdmin();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Vous devez être administrateur.'
            ], 403);
        }

        $admin = Administrateur::find($user->id);
        $admin->supprimerLivre($book);

        return response()->json([
            'success' => true,
            'message' => 'Livre supprimé avec succès'
        ]);
    }

    public function markAsDamaged(Request $request, Book $book)
    {
        $user = $this->checkAdmin();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Vous devez être administrateur.'
            ], 403);
        }

        $validated = $request->validate([
            'condition' => 'required|in:moyen,degrade'
        ]);

        $book->marquerCommeDegrade($validated['condition']);

        return response()->json([
            'success' => true,
            'message' => 'Livre marqué comme ' . $validated['condition'],
            'data' => $book->afficherDetails()
        ]);
    }
}