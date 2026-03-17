<?php
// app/Policies/StatisticsPolicy.php

namespace App\Policies;

use App\Models\User;

class StatisticsPolicy
{
    /**
     * Vérifier si l'utilisateur peut voir les statistiques
     */
    public function viewStatistics(User $user)
    {
        return $user->isAdministrateur();
    }
}