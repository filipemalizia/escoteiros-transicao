<?php

namespace App\Policies;

use App\Models\ItemPersonalizado;
use App\Models\User;

class ItemPersonalizadoPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ItemPersonalizado $itemPersonalizado): bool
    {
        return $this->temAcesso($user, $itemPersonalizado);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ItemPersonalizado $itemPersonalizado): bool
    {
        return $this->temAcesso($user, $itemPersonalizado);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ItemPersonalizado $itemPersonalizado): bool
    {
        return $this->temAcesso($user, $itemPersonalizado);
    }

    /**
     * Admin sempre pode. Um usuário comum pode gerenciar (ver/editar/
     * excluir/confirmar) um item personalizado se tiver acesso a pelo
     * menos um dos jovens vinculados a ele — não precisa ser quem criou.
     */
    private function temAcesso(User $user, ItemPersonalizado $itemPersonalizado): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $itemPersonalizado->jovens->contains(
            fn ($jovem) => $user->can('view', $jovem)
        );
    }
}
