<?php

namespace App\Services;

use App\Models\Equivalencia;
use App\Models\EquivalenciaEspecialidade;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoAntigo;
use App\Models\ProgressoNovo;
use Illuminate\Support\Collection;

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
     * Ids dos itens já marcados como concluídos direto, por jovem — 1 consulta
     * por jovem em vez de 1 por item (a Entrega de Distintivos calcula todos
     * os jovens de uma vez).
     *
     * @var array<int, array<int, true>>
     */
    private array $concluidosDiretoNovo = [];

    /** @var array<int, array<int, true>> */
    private array $concluidosDiretoAntigo = [];

    /**
     * Equivalências cadastradas por item: não dependem do jovem, então são
     * lidas uma vez por requisição e reaproveitadas por todos os jovens.
     *
     * @var array<int, Collection<int, Equivalencia>>
     */
    private array $equivalenciasPorItemNovo = [];

    /** @var array<int, Collection<int, Equivalencia>> */
    private array $equivalenciasPorItemAntigo = [];

    /** @var array<int, Collection<int, EquivalenciaEspecialidade>> */
    private array $equivalenciasEspecialidadePorItemNovo = [];

    public function __construct(
        private readonly EspecialidadeStatusService $especialidadeStatusService = new EspecialidadeStatusService,
    ) {}

    /**
     * Esquece todo o cache memoizado (o próprio e o do
     * {@see EspecialidadeStatusService} injetado) — chamar sempre que
     * `concluido` for alterado em
     * `progresso_antigo`/`progresso_novo`/`progresso_especialidade`, pra não
     * arriscar servir um resultado desatualizado dentro da mesma requisição.
     */
    public function limparCache(): void
    {
        $this->cacheAntigo = [];
        $this->cacheNovo = [];
        $this->concluidosDiretoNovo = [];
        $this->concluidosDiretoAntigo = [];
        $this->equivalenciasPorItemNovo = [];
        $this->equivalenciasPorItemAntigo = [];
        $this->equivalenciasEspecialidadePorItemNovo = [];
        $this->especialidadeStatusService->limparCache();
    }

    /**
     * @return array<int, true>
     */
    private function itensAntigosConcluidosDireto(Jovem $jovem): array
    {
        return $this->concluidosDiretoAntigo[$jovem->id] ??= ProgressoAntigo::query()
            ->where('jovem_id', $jovem->id)
            ->where('concluido', true)
            ->pluck('item_antigo_id')
            ->mapWithKeys(fn ($id) => [$id => true])
            ->all();
    }

    /**
     * @return array<int, true>
     */
    private function itensNovosConcluidosDireto(Jovem $jovem): array
    {
        return $this->concluidosDiretoNovo[$jovem->id] ??= ProgressoNovo::query()
            ->where('jovem_id', $jovem->id)
            ->where('concluido', true)
            ->pluck('item_novo_id')
            ->mapWithKeys(fn ($id) => [$id => true])
            ->all();
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

        $marcadoDireto = isset($this->itensAntigosConcluidosDireto($jovem)[$item->id]);

        if ($marcadoDireto) {
            return true;
        }

        $equivalencias = $this->equivalenciasPorItemAntigo[$item->id] ??= Equivalencia::query()
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

        $marcadoDireto = isset($this->itensNovosConcluidosDireto($jovem)[$item->id]);

        if ($marcadoDireto) {
            return true;
        }

        if ($this->itemNovoConcluidoViaEspecialidade($jovem, $item)) {
            return true;
        }

        $equivalencias = $this->equivalenciasPorItemNovo[$item->id] ??= Equivalencia::query()
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

    /**
     * Crédito de especialidade: um item novo (tipicamente uma ação
     * Substitutiva de bloco) que uma Especialidade/Insígnia inteira já
     * conquistada substitui — cadastrado via `EquivalenciaEspecialidade`.
     * Unidirecional (a especialidade nunca é creditada de volta a partir do
     * item), então não entra na proteção de ciclo/`$visitados`.
     */
    private function itemNovoConcluidoViaEspecialidade(Jovem $jovem, ItemNovo $item): bool
    {
        return ($this->equivalenciasEspecialidadePorItemNovo[$item->id] ??= EquivalenciaEspecialidade::query()
            ->where('item_novo_id', $item->id)
            ->with('especialidadeDistintivo')
            ->get())
            ->filter(fn (EquivalenciaEspecialidade $equivalencia) => $equivalencia->especialidadeDistintivo !== null)
            ->contains(function (EquivalenciaEspecialidade $equivalencia) use ($jovem) {
                $status = $this->especialidadeStatusService->statusEspecialidade($jovem, $equivalencia->especialidadeDistintivo);

                if ($status['status'] !== 'Concluído') {
                    return false;
                }

                // nivel_minimo=null (padrão) aceita qualquer nível; nivel_atingido
                // vem null pra especialidades sem estrutura de níveis (atividades_temas
                // ou Insígnia), que também não têm como ser exigidas num nível específico.
                return $equivalencia->nivel_minimo === null
                    || $status['nivel_atingido'] === null
                    || $status['nivel_atingido'] >= $equivalencia->nivel_minimo;
            });
    }
}
