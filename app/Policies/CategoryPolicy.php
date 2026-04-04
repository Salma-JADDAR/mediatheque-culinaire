<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Category;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Vérifier si l'utilisateur peut voir la liste des catégories
     */
    public function viewAny(User $user): bool
    {
        return true; // Tous les utilisateurs peuvent voir les catégories
    }

    /**
     * Vérifier si l'utilisateur peut voir une catégorie spécifique
     */
    public function view(User $user, Category $category): bool
    {
        return true; // Tous les utilisateurs peuvent voir les détails d'une catégorie
    }

    /**
     * Vérifier si l'utilisateur peut créer une catégorie (admin seulement)
     */
    public function create(User $user): bool
    {
        return $user->role === 'administrateur';
    }

    /**
     * Vérifier si l'utilisateur peut modifier une catégorie (admin seulement)
     */
    public function update(User $user, Category $category): bool
    {
        return $user->role === 'administrateur';
    }

    /**
     * Vérifier si l'utilisateur peut supprimer une catégorie (admin seulement)
     * Vérifie aussi que la catégorie ne contient pas de livres
     */
    public function delete(User $user, Category $category): bool
    {
        // Vérifier d'abord si l'utilisateur est admin
        if ($user->role !== 'administrateur') {
            return false;
        }
        
        // Vérifier si la catégorie contient des livres
        if ($category->books()->count() > 0) {
            return false; // Ne peut pas supprimer une catégorie avec des livres
        }
        
        return true;
    }

    /**
     * Vérifier si l'utilisateur peut voir les livres d'une catégorie
     */
    public function viewBooks(User $user, Category $category): bool
    {
        return true; // Tous les utilisateurs peuvent voir les livres d'une catégorie
    }

    /**
     * Vérifier si l'utilisateur peut restaurer une catégorie supprimée
     */
    public function restore(User $user, Category $category): bool
    {
        return $user->role === 'administrateur';
    }

    /**
     * Vérifier si l'utilisateur peut supprimer définitivement une catégorie
     */
    public function forceDelete(User $user, Category $category): bool
    {
        return $user->role === 'administrateur';
    }
}