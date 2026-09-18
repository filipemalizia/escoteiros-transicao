<?php

namespace App\Concerns\Portal;

use App\Models\Jovem;
use App\Services\Portal\SessaoJovemService;

/**
 * Autenticação/sessão do jovem, compartilhada por todo componente Livewire
 * do portal público. Cada componente chama `autenticarJovemNoPortal()`
 * explicitamente como a primeira linha do seu próprio `mount()` — o hook
 * automático `mount{NomeDaTrait}` do Livewire roda DEPOIS do `mount()` do
 * próprio componente (não antes), então não serve pra isso quando o
 * componente precisa checar posse do jovem (ex.: ramo do Eixo recebido)
 * dentro do seu próprio `mount()`.
 */
trait AutenticaJovemNoPortal
{
    public int $jovemId;

    private ?Jovem $jovemCache = null;

    public function autenticarJovemNoPortal(SessaoJovemService $sessao): void
    {
        $jovem = $sessao->jovemAutenticado();

        abort_if($jovem === null, 403);

        $this->jovemId = $jovem->id;
    }

    protected function jovem(): Jovem
    {
        return $this->jovemCache ??= Jovem::findOrFail($this->jovemId);
    }
}
