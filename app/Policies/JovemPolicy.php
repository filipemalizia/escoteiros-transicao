<?php

namespace App\Policies;

use App\Models\Jovem;
use App\Models\User;

class JovemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Jovem $jovem): bool
    {
        return $this->podeGerenciar($user, $jovem);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Jovem $jovem): bool
    {
        return $this->podeGerenciar($user, $jovem);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Jovem $jovem): bool
    {
        return $this->podeGerenciar($user, $jovem);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Jovem $jovem): bool
    {
        return $this->podeGerenciar($user, $jovem);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Jovem $jovem): bool
    {
        return $this->podeGerenciar($user, $jovem);
    }

    /**
     * Um admin pode gerenciar qualquer jovem. Um usuário comum só pode
     * gerenciar jovens da(s) equipe(s) a que está vinculado — um jovem
     * sem equipe atribuída fica visível só para admins.
     */
    private function podeGerenciar(User $user, Jovem $jovem): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($jovem->equipe_id === null) {
            return false;
        }

        return $user->equipes()->whereKey($jovem->equipe_id)->exists();
    }
}
