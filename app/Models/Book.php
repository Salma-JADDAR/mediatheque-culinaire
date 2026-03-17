<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
        'description',
        'slug',
        'isDamaged',
        'condition',
        'views',
        'total_copies',
        'available_copies',
        'category_id'
    ];

    protected $casts = [
        'isDamaged' => 'boolean',
        'views' => 'integer',
        'total_copies' => 'integer',
        'available_copies' => 'integer'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($book) {
            $book->slug = Str::slug($book->title);
        });

        static::updating(function ($book) {
            if ($book->isDirty('title')) {
                $book->slug = Str::slug($book->title);
            }
        });
    }

    public function afficherDetails()
    {
        return [
            'id' => $this->id,
            'titre' => $this->title,
            'auteur' => $this->author,
            'description' => $this->description,
            'slug' => $this->slug,
            'categorie' => $this->category->name,
            'categorie_slug' => $this->category->slug,
            'est_degrade' => $this->isDamaged,
            'etat' => $this->condition,
            'nombre_vues' => $this->views,
            'exemplaires_total' => $this->total_copies,
            'exemplaires_disponibles' => $this->available_copies,
            'disponible' => $this->available_copies > 0,
            'url' => url("/api/categories/{$this->category->slug}/books/{$this->slug}"),
            'statistiques' => [
                'vues' => $this->views,
                'popularite' => $this->statistique ? $this->statistique->calculerPopularite() : 'Nouveau'
            ]
        ];
    }

    public function incrementViews()
    {
        $this->increment('views');
        
        if ($this->statistique) {
            $this->statistique->increment('views');
        } else {
            Statistique::create([
                'book_id' => $this->id,
                'views' => 1,
                'borrow_count' => 0
            ]);
        }
        
        return $this;
    }

    public function marquerCommeDegrade($condition = 'degrade')
    {
        $oldCondition = $this->condition;
        $oldIsDamaged = $this->isDamaged;
        
        $this->condition = $condition;
        $this->isDamaged = ($condition === 'degrade' || $condition === 'moyen');
        $this->save();

        if (!$oldIsDamaged && $this->isDamaged) {
            $admins = User::where('role', 'administrateur')->get();
            foreach ($admins as $admin) {
                if (method_exists($admin, 'notifierLivreDegrade')) {
                    $admin->notifierLivreDegrade($this);
                }
            }
        }

        return $this;
    }

    public function scopePopulaires($query)
    {
        return $query->orderBy('views', 'desc');
    }

    public function scopeDegrades($query)
    {
        return $query->where('isDamaged', true)
                     ->orWhere('condition', 'degrade');
    }

    public function scopeDisponibles($query)
    {
        return $query->where('available_copies', '>', 0);
    }

    public function scopeParCategorie($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function statistique()
    {
        return $this->hasOne(Statistique::class);
    }

 
}