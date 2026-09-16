<?php

namespace App\Services;

use App\Models\Equivalencia;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoAntigo;
use App\Models\ProgressoNovo;

/**
 * Calcula se um item (antigo ou novo) conta como concluído para um jovem,
 * considerando tanto a marcação direta (Fase 4) quanto o crédito cruzado
 * vindo de equivalências com o outro sistema (Fase 6).
 *
 * Registrado como singleton no container (`AppServiceProvider`) de propósito:
 * a tela de progresso chama esses métodos uma vez por item (dezenas a
 * centenas de vezes numa única renderização), e sem cache isso vira um N+1
 * severo — chegava a 2000+ queries pra renderizar os 18 blocos do programa
 * novo. O cache abaixo é por (jovem, item), válido só durante a vida do
 * singleton (uma requisição HTTP), e ignora `$visitados` de propósito: o
 * resultado final de um item não muda dependendo de por onde a recursão
 * começou, a não ser em cadastros com ciclo (caso já sinalizado como
 * "mal cadastrado" pela proteção de profundidade abaixo).
 *
 * Importante: quem grava uma mudança em `concluido` (`VerProgresso::
 * toggleAntigo/toggleNovo/confirmarAntigo/confirmarNovo`) precisa chamar
 * `limparCache()` logo depois de salvar — como o serviço é singleton por
 * requisição, uma ação que muda o progresso e o Livewire re-renderiza a
 * página *na mesma requisição* veria o valor antigo em cache sem isso.
 */
class EquivalenciaCreditoService
{
    /**
     * Limite de profundidade da recursão, como proteção contra equivalências
     * mal cadastradas formando ciclos (ex.: A equivale a B, e B equivale a A).
     */
    private const PROFUNDIDADE_MAXIMA = 20;

    /** @var array<string, bool> */
    private array $cacheAntigo = [];

    /** @var array<string, bool> */
    private array $cacheNovo = [];

    /**
     * Esquece todo o cache memoizado — chamar sempre que `concluido` for
     * alterado em `progresso_antigo`/`progresso_novo`, pra não arriscar
     * servir um resultado desatualizado dentro da mesma requisição.
     */
    public function limparCache(): void
    {
        $this->cacheAntigo = [];
        $this->cacheNovo = [];
    }

    /**
     * @param  array<int, string>  $visitados  chaves "antigo:{id}"/"novo:{id}" já percorridas nesta cadeia
     */
    public function itemAntigoConcluido(Jovem $jovem, ItemAntigo $item, array $visitados = []): bool
    {
        $chaveCache = "{$jovem->id}:{$item->id}";

        if (array_key_exists($chaveCache, $this->cacheAntigo)) {
            return $this->cacheAntigo[$chaveCache];
        }

        return $this->cacheAntigo[$chaveCache] = $this->calcularItemAntigoConcluido($jovem, $item, $visitados);
    }

    /**
     * @param  array<int, string>  $visitados
     */
    private function calcularItemAntigoConcluido(Jovem $jovem, ItemAntigo $item, array $visitados): bool
    {
        $chave = "antigo:{$item->id}";

        if (in_array($chave, $visitados, true) || count($visitados) >= self::PROFUNDIDADE_MAXIMA) {
            return false;
        }

        $marcadoDireto = ProgressoAntigo::query()
            ->where('jovem_id', $jovem->id)
            ->where('item_antigo_id', $item->id)
            ->where('concluido', true)
            ->exists();

        if ($marcadoDireto) {
            return true;
        }

        $equivalencias = Equivalencia::query()
            ->where('item_antigo_id', $item->id)
            ->get();

        if ($equivalencias->isEmpty()) {
            return false;
        }

        $visitados[] = $chave;

        $itensNovos = ItemNovo::query()
            ->whereIn('id', $equivalencias->pluck('item_novo_id')->unique())
            ->get();

        foreach ($itensNovos as $itemNovo) {
            if (! $this->itemNovoConcluido($jovem, $itemNovo, $visitados)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, string>  $visitados  chaves "antigo:{id}"/"novo:{id}" já percorridas nesta cadeia
     */
    public function itemNovoConcluido(Jovem $jovem, ItemNovo $item, array $visitados = []): bool
    {
        $chaveCache = "{$jovem->id}:{$item->id}";

        if (array_key_exists($chaveCache, $this->cacheNovo)) {
            return $this->cacheNovo[$chaveCache];
        }

        return $this->cacheNovo[$chaveCache] = $this->calcularItemNovoConcluido($jovem, $item, $visitados);
    }

    /**
     * @param  array<int, string>  $visitados
     */
    private function calcularItemNovoConcluido(Jovem $jovem, ItemNovo $item, array $visitados): bool
    {
        $chave = "novo:{$item->id}";

        if (in_array($chave, $visitados, true) || count($visitados) >= self::PROFUNDIDADE_MAXIMA) {
            return false;
        }

        $marcadoDireto = ProgressoNovo::query()
            ->where('jovem_id', $jovem->id)
            ->where('item_novo_id', $item->id)
            ->where('concluido', true)
            ->exists();

        if ($marcadoDireto) {
            return true;
        }

        $equivalencias = Equivalencia::query()
            ->where('item_novo_id', $item->id)
            ->get();

        if ($equivalencias->isEmpty()) {
            return false;
        }

        $visitados[] = $chave;

        $itensAntigos = ItemAntigo::query()
            ->whereIn('id', $equivalencias->pluck('item_antigo_id')->unique())
            ->get();

        foreach ($itensAntigos as $itemAntigo) {
            if (! $this->itemAntigoConcluido($jovem, $itemAntigo, $visitados)) {
                return false;
            }
        }

        return true;
    }
}
