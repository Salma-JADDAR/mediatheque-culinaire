<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Statistique extends Model
{
    use HasFactory;

    protected $table = 'statistiques';

    protected $fillable = [
        'book_id',
        'user_id',
        'borrow_count',
        'views'
    ];

    protected $casts = [
        'borrow_count' => 'integer',
        'views' => 'integer'
    ];

    public function calculerPopularite()
    {
        $score = ($this->views * 1) + ($this->borrow_count * 3);
        
        if ($score > 100) {
            return 'Très populaire';
        } elseif ($score > 50) {
            return 'Populaire';
        } elseif ($score > 20) {
            return 'Moyennement populaire';
        } elseif ($score > 0) {
            return 'Peu populaire';
        } else {
            return 'Nouveau';
        }
    }

    public static function getLivresPopulaires($limit = 10)
    {
        return self::with('book.category')
            ->orderBy('views', 'desc')
            ->orderBy('borrow_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($stat) {
                return [
                    'livre' => [
                        'titre' => $stat->book->title,
                        'auteur' => $stat->book->author,
                        'categorie' => $stat->book->category->name
                    ],
                    'statistiques' => [
                        'vues' => $stat->views,
                        'emprunts' => $stat->borrow_count,
                        'popularite' => $stat->calculerPopularite()
                    ]
                ];
            });
    }

    public function incrementBorrowCount()
    {
        $this->increment('borrow_count');
        return $this;
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}