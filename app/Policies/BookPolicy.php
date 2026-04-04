<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Book;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookPolicy
{
    use HandlesAuthorization;

    /**
     * Vérifier si l'utilisateur peut voir la liste des livres
     */
    public function viewAny(User $user): bool
    {
        return true; // Tous les utilisateurs peuvent voir les livres
    }

    /**
     * Vérifier si l'utilisateur peut voir un livre spécifique
     */
    public function view(User $user, Book $book): bool
    {
        return true; // Tous les utilisateurs peuvent voir les détails d'un livre
    }

    /**
     * Vérifier si l'utilisateur peut créer un livre (admin seulement)
     */
    public function create(User $user): bool
    {
        return $user->role === 'administrateur';
    }

    /**
     * Vérifier si l'utilisateur peut modifier un livre (admin seulement)
     */
    public function update(User $user, Book $book): bool
    {
        return $user->role === 'administrateur';
    }

    /**
     * Vérifier si l'utilisateur peut supprimer un livre (admin seulement)
     */
    public function delete(User $user, Book $book): bool
    {
        return $user->role === 'administrateur';
    }

    /**
     * Vérifier si l'utilisateur peut marquer un livre comme dégradé (admin seulement)
     */
    public function markAsDamaged(User $user, Book $book): bool
    {
        return $user->role === 'administrateur';
    }

    /**
     * Vérifier si l'utilisateur peut restaurer un livre supprimé
     */
    public function restore(User $user, Book $book): bool
    {
        return $user->role === 'administrateur';
    }

    /**
     * Vérifier si l'utilisateur peut supprimer définitivement un livre
     */
    public function forceDelete(User $user, Book $book): bool
    {
        return $user->role === 'administrateur';
    }
}