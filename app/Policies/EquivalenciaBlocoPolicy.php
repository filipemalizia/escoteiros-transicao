<?php

namespace App\Policies;

use App\Models\EquivalenciaBloco;
use App\Models\User;

class EquivalenciaBlocoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EquivalenciaBloco $equivalenciaBloco): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EquivalenciaBloco $equivalenciaBloco): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EquivalenciaBloco $equivalenciaBloco): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EquivalenciaBloco $equivalenciaBloco): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EquivalenciaBloco $equivalenciaBloco): bool
    {
        return $user->isAdmin();
    }
}
