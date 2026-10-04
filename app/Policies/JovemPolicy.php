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
     * gerenciar jovens da(s) equipe(s) a que está vinculado — exceto um
     * jovem ainda sem equipe, que fica visível pra qualquer chefe, pra que
     * algum deles possa associá-lo à própria equipe (ex.: jovem recém
     * importado por planilha, antes de ser distribuído).
     */
    private function podeGerenciar(User $user, Jovem $jovem): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($jovem->equipe_id === null) {
            return true;
        }

        return $user->equipes()->whereKey($jovem->equipe_id)->exists();
    }
}
