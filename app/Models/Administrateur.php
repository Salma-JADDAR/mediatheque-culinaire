<?php

namespace App\Models;

use App\Notifications\BookDegradedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class Administrateur extends User
{
    protected $table = 'users';

    protected static function booted()
    {
        static::addGlobalScope('administrateur', function ($builder) {
            $builder->where('role', 'administrateur');
        });
    }

    public function ajouterCategory($data)
    {
        return Category::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'slug' => Str::slug($data['name'])
        ]);
    }

    public function modifierCategory(Category $category, $data)
    {
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        return $category->update($data);
    }

    public function supprimerCategory(Category $category)
    {
        return $category->delete();
    }

    public function ajouterLivre($data)
    {
        $book = Book::create([
            'title' => $data['title'],
            'author' => $data['author'],
            'description' => $data['description'],
            'slug' => Str::slug($data['title']),
            'isDamaged' => false,
            'condition' => 'neuf',
            'views' => 0,
            'total_copies' => $data['total_copies'] ?? 1,
            'available_copies' => $data['total_copies'] ?? 1,
            'category_id' => $data['category_id']
        ]);

        Statistique::create([
            'book_id' => $book->id,
            'views' => 0,
            'borrow_count' => 0
        ]);

        return $book;
    }

    public function modifierLivre(Book $book, $data)
    {
        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }
        return $book->update($data);
    }

    public function supprimerLivre(Book $book)
    {
        return $book->delete();
    }

    public function voirStatistiques()
    {
        $totalLivres = Book::count();
        $totalCategories = Category::count();
        
        $livresPopulaires = Book::with('category')
            ->orderBy('views', 'desc')
            ->take(5)
            ->get()
            ->map(function ($book) {
                return [
                    'titre' => $book->title,
                    'auteur' => $book->author,
                    'vues' => $book->views,
                    'categorie' => $book->category->name
                ];
            });

        $categoriesPopulaires = Category::withCount('books')
            ->orderBy('books_count', 'desc')
            ->take(5)
            ->get()
            ->map(function ($cat) {
                return [
                    'nom' => $cat->name,
                    'nombre_livres' => $cat->books_count
                ];
            });

        $etatCollection = [
            'neuf' => Book::where('condition', 'neuf')->count(),
            'bon' => Book::where('condition', 'bon')->count(),
            'moyen' => Book::where('condition', 'moyen')->count(),
            'degrade' => Book::where('condition', 'degrade')->count()
        ];

        return [
            'total_livres' => $totalLivres,
            'total_categories' => $totalCategories,
            'livres_plus_consultes' => $livresPopulaires,
            'categories_plus_representees' => $categoriesPopulaires,
            'etat_de_la_collection' => $etatCollection
        ];
    }

    public function voirLivresDegrades()
    {
        return Book::with('category')
            ->where('condition', 'degrade')
            ->orWhere('isDamaged', true)
            ->get()
            ->map(function ($book) {
                return [
                    'id' => $book->id,
                    'titre' => $book->title,
                    'auteur' => $book->author,
                    'categorie' => $book->category->name,
                    'etat' => $book->condition,
                    'exemplaires_total' => $book->total_copies,
                    'exemplaires_disponibles' => $book->available_copies
                ];
            });
    }

    public function notifierLivreDegrade(Book $book)
    {
        Notification::send($this, new BookDegradedNotification($book));
    }
}