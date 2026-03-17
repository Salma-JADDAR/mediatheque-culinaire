<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Book;
use App\Models\Statistique;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Création d'un admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'administrateur',
        ]);

        // Création d'un utilisateur normal
        User::create([
            'name' => 'Gourmand',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'role' => 'gourmand',
        ]);

        // Catégories
        $categories = [
            ['name' => 'Pâtisserie Française', 'description' => 'Découvrez l\'art de la pâtisserie française'],
            ['name' => 'Cuisine du Monde', 'description' => 'Explorez les saveurs du monde entier'],
            ['name' => 'Sans Gluten', 'description' => 'Recettes adaptées aux intolérances'],
            ['name' => 'Cuisine Rapide', 'description' => 'Repas express pour tous les jours'],
            ['name' => 'Cuisine Italienne', 'description' => 'Les meilleures recettes italiennes'],
            ['name' => 'Cuisine Marocaine', 'description' => 'Saveurs et épices du Maroc'],
        ];

        foreach ($categories as $cat) {
            Category::create([
                'name' => $cat['name'],
                'slug' => Str::slug($cat['name']),
                'description' => $cat['description']
            ]);
        }

        // Livres
        $french = Category::where('name', 'Pâtisserie Française')->first();
        $world = Category::where('name', 'Cuisine du Monde')->first();
        $italian = Category::where('name', 'Cuisine Italienne')->first();

        $book1 = Book::create([
            'title' => 'Les Meilleures Recettes de Pâtes',
            'author' => 'Chef Luigi',
            'description' => 'Découvrez toutes les recettes de pâtes italiennes traditionnelles',
            'slug' => 'les-meilleures-recettes-de-pates',
            'isDamaged' => false,
            'condition' => 'neuf',
            'views' => 150,
            'total_copies' => 5,
            'available_copies' => 3,
            'category_id' => $italian->id
        ]);

        Statistique::create([
            'book_id' => $book1->id,
            'views' => 150,
            'borrow_count' => 10
        ]);

        $book2 = Book::create([
            'title' => 'Tajine et Couscous',
            'author' => 'Chef Fatima',
            'description' => 'Les secrets de la cuisine marocaine',
            'slug' => 'tajine-et-couscous',
            'isDamaged' => true,
            'condition' => 'degrade',
            'views' => 200,
            'total_copies' => 3,
            'available_copies' => 1,
            'category_id' => $world->id
        ]);

        Statistique::create([
            'book_id' => $book2->id,
            'views' => 200,
            'borrow_count' => 15
        ]);

        $book3 = Book::create([
            'title' => 'Macarons et Desserts',
            'author' => 'Chef Pierre',
            'description' => 'L\'art de la pâtisserie française',
            'slug' => 'macarons-et-desserts',
            'isDamaged' => false,
            'condition' => 'bon',
            'views' => 300,
            'total_copies' => 4,
            'available_copies' => 2,
            'category_id' => $french->id
        ]);

        Statistique::create([
            'book_id' => $book3->id,
            'views' => 300,
            'borrow_count' => 20
        ]);
    }
}