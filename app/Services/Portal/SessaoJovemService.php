<?php

namespace App\Services\Portal;

use App\Models\Jovem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;

/**
 * Sessão leve pro portal público do jovem — não é uma conta de usuário
 * (`App\Models\User`/Auth guard), só uma prova de que quem está navegando
 * validou registro + data de nascimento de um Jovem específico. Convive
 * na mesma sessão do Laravel usada pelo painel Filament, sob chaves
 * próprias, sem se misturar com o guard de autenticação dos usuários.
 */
class SessaoJovemService
{
    private const CHAVE_JOVEM_ID = 'portal_jovem_id';

    private const CHAVE_AUTENTICADO_EM = 'portal_autenticado_em';

    public function iniciarSessao(Jovem $jovem): void
    {
        Session::put(self::CHAVE_JOVEM_ID, $jovem->id);
        Session::put(self::CHAVE_AUTENTICADO_EM, now());
        Session::regenerate();
    }

    public function encerrarSessao(): void
    {
        Session::forget([self::CHAVE_JOVEM_ID, self::CHAVE_AUTENTICADO_EM]);
        Session::regenerate();
    }

    public function jovemAutenticado(): ?Jovem
    {
        if (! $this->sessaoValida()) {
            return null;
        }

        return Jovem::find(Session::get(self::CHAVE_JOVEM_ID));
    }

    public function sessaoValida(): bool
    {
        $autenticadoEm = Session::get(self::CHAVE_AUTENTICADO_EM);

        if (! Session::has(self::CHAVE_JOVEM_ID) || ! $autenticadoEm) {
            return false;
        }

        // Carbon 3 passou a retornar diffInMinutes() com sinal por padrão
        // (negativo se o argumento for uma data no passado) — abs() garante
        // que a checagem funcione independente da direção.
        return abs(now()->diffInMinutes(Carbon::parse($autenticadoEm))) <= config('portal.sessao_minutos');
    }
}
