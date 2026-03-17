<?php

namespace App\Http\Controllers;

use App\Models\Administrateur;
use App\Models\Statistique;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
  

    private function checkAdmin()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }
        
        if ($user->role !== 'administrateur') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Vous devez être administrateur.'
            ], 403);
        }
        
        return $user;
    }

    public function index()
    {
        $check = $this->checkAdmin();
        if ($check instanceof \Illuminate\Http\JsonResponse) {
            return $check;
        }
        $user = $check;
        
        $admin = Administrateur::find($user->id);
        
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Administrateur non trouvé'
            ], 404);
        }
        
        $statistiques = $admin->voirStatistiques();

        return response()->json([
            'success' => true,
            'data' => $statistiques
        ]);
    }

    public function degradedBooks()
    {
        $check = $this->checkAdmin();
        if ($check instanceof \Illuminate\Http\JsonResponse) {
            return $check;
        }
        $user = $check;
        
        $admin = Administrateur::find($user->id);
        
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Administrateur non trouvé'
            ], 404);
        }
        
        $livresDegrades = $admin->voirLivresDegrades();

        return response()->json([
            'success' => true,
            'total' => count($livresDegrades),
            'data' => $livresDegrades
        ]);
    }

    public function popularBooks(Request $request)
    {
        // Cette méthode peut être accessible sans être admin
        $limit = $request->get('limit', 10);
        
        try {
            $populaires = Statistique::getLivresPopulaires($limit);
            
            return response()->json([
                'success' => true,
                'data' => $populaires
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des livres populaires'
            ], 500);
        }
    }

    public function byCategory()
    {
        $check = $this->checkAdmin();
        if ($check instanceof \Illuminate\Http\JsonResponse) {
            return $check;
        }
        
        try {
            $stats = DB::table('categories')
                ->leftJoin('books', 'categories.id', '=', 'books.category_id')
                ->leftJoin('statistiques', 'books.id', '=', 'statistiques.book_id')
                ->select(
                    'categories.id',
                    'categories.name',
                    'categories.slug',
                    DB::raw('COUNT(DISTINCT books.id) as total_livres'),
                    DB::raw('COALESCE(SUM(statistiques.views), 0) as total_vues'),
                    DB::raw('COALESCE(SUM(statistiques.borrow_count), 0) as total_emprunts'),
                    DB::raw('SUM(CASE WHEN books.condition = "degrade" THEN 1 ELSE 0 END) as livres_degrades')
                )
                ->groupBy('categories.id', 'categories.name', 'categories.slug')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques par catégorie'
            ], 500);
        }
    }

    public function collectionHealth()
    {
        $check = $this->checkAdmin();
        if ($check instanceof \Illuminate\Http\JsonResponse) {
            return $check;
        }
        
        try {
            $totalLivres = Book::count();
            
            $livresEmpruntes = 0;
            if (DB::getSchemaBuilder()->hasTable('borrowings')) {
                $livresEmpruntes = DB::table('borrowings')
                    ->whereNull('returned_at')
                    ->where('status', '!=', 'retourne')
                    ->count();
            }

            $etatLivres = [
                'neuf' => Book::where('condition', 'neuf')->count(),
                'bon' => Book::where('condition', 'bon')->count(),
                'moyen' => Book::where('condition', 'moyen')->count(),
                'degrade' => Book::where('condition', 'degrade')->count()
            ];

            $pourcentageDegrades = $totalLivres > 0 
                ? round(($etatLivres['degrade'] / $totalLivres) * 100, 2) 
                : 0;

            $santeCollection = 'Excellente';
            if ($pourcentageDegrades >= 10) {
                $santeCollection = 'À améliorer';
            } elseif ($pourcentageDegrades >= 5) {
                $santeCollection = 'Bonne';
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_livres' => $totalLivres,
                    'livres_empruntes' => $livresEmpruntes,
                    'taux_emprunt' => $totalLivres > 0 ? round(($livresEmpruntes / $totalLivres) * 100, 2) : 0,
                    'etat_livres' => $etatLivres,
                    'pourcentage_degrades' => $pourcentageDegrades,
                    'sante_collection' => $santeCollection
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'état de la collection'
            ], 500);
        }
    }

    public function bookStatistics($bookId)
    {
        $check = $this->checkAdmin();
        if ($check instanceof \Illuminate\Http\JsonResponse) {
            return $check;
        }
        
        try {
            $book = Book::with('category', 'statistique')->findOrFail($bookId);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'livre' => [
                        'titre' => $book->title,
                        'auteur' => $book->author,
                        'categorie' => $book->category->name
                    ],
                    'statistiques' => [
                        'vues' => $book->views,
                        'vues_total' => $book->statistique->views ?? 0,
                        'emprunts' => $book->statistique->borrow_count ?? 0,
                        'popularite' => $book->statistique ? $book->statistique->calculerPopularite() : 'Nouveau',
                        'etat' => $book->condition,
                        'disponibilite' => $book->available_copies > 0 ? 'Disponible' : 'Indisponible',
                        'exemplaires_disponibles' => $book->available_copies,
                        'exemplaires_total' => $book->total_copies
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Livre non trouvé'
            ], 404);
        }
    }
}