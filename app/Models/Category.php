<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($category) {
            $category->slug = Str::slug($category->name);
        });

        static::updating(function ($category) {
            if ($category->isDirty('name')) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function ajouterLivre($data)
    {
        $data['category_id'] = $this->id;
        $data['slug'] = Str::slug($data['title']);
        
        $book = Book::create($data);

        Statistique::create([
            'book_id' => $book->id,
            'views' => 0,
            'borrow_count' => 0
        ]);

        return $book;
    }

    public function supprimerLivre(Book $book)
    {
        if ($book->category_id === $this->id) {
            return $book->delete();
        }
        return false;
    }

    public function getLivres($filtres = [])
    {
        $query = $this->books();
        
        if (isset($filtres['search']) && !empty($filtres['search'])) {
            $search = $filtres['search'];
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%");
            });
        }

        if (isset($filtres['sort'])) {
            switch ($filtres['sort']) {
                case 'popular':
                    $query->orderBy('views', 'desc');
                    break;
                case 'recent':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'title':
                    $query->orderBy('title', 'asc');
                    break;
            }
        }

        if (isset($filtres['condition'])) {
            $query->where('condition', $filtres['condition']);
        }

        return $query->with('statistique')->get();
    }

    public function books()
    {
        return $this->hasMany(Book::class);
    }

    public function statistiques()
    {
        return $this->hasManyThrough(Statistique::class, Book::class);
    }
}