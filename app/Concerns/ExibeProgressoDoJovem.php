<?php

namespace App\Concerns;

use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\EixoNovo;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\ItemPersonalizado;
use App\Models\Jovem;
use App\Models\ProgressoAntigo;
use App\Models\ProgressoNovo;
use App\Models\ProgressoPersonalizado;
use App\Services\EquivalenciaCreditoService;
use App\Services\EtapaProgressaoService;
use App\Services\StatusProgressaoService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Getters somente-leitura de progresso de um Jovem, compartilhados entre a
 * tela de progresso do painel (adulto) e o portal público (jovem). Mutações
 * (toggle/confirmação/etc) ficam fora daqui de propósito, pra nunca ficarem
 * acessíveis a partir do portal público.
 */
trait ExibeProgressoDoJovem
{
    /**
     * Ordem de exibição das Áreas de Desenvolvimento do programa antigo
     * (documento oficial, igual pros 4 ramos). Áreas não listadas aqui
     * (ex.: cadastro divergente) aparecem no final, na ordem alfabética
     * padrão, em vez de sumirem.
     */
    protected const ORDEM_AREAS_ANTIGAS = ['Físico', 'Intelectual', 'Caráter', 'Afetivo', 'Social', 'Espiritual'];

    abstract protected function jovem(): Jovem;

    public function getAreasAntigas(): Collection
    {
        return AreaDesenvolvimentoAntiga::query()
            ->where('ramo_id', $this->jovem()->ramo_atual_id)
            ->with('competencias.itens')
            ->orderBy('nome')
            ->get()
            ->sortBy(function (AreaDesenvolvimentoAntiga $area) {
                $indice = array_search($area->nome, self::ORDEM_AREAS_ANTIGAS, true);

                return $indice === false ? 999 : $indice;
            })
            ->values();
    }

    public function getEixosNovos(): Collection
    {
        return EixoNovo::query()
            ->where('ramo_id', $this->jovem()->ramo_atual_id)
            ->with(['blocos.itens.especialidade', 'blocos.equivalenciasBloco.itemAntigo'])
            ->orderBy('nome')
            ->get();
    }

    /**
     * Registros diretos de progresso (Fase 4), indexados por item_antigo_id.
     * Usados pra saber se um item foi marcado diretamente (e por quem/quando),
     * em oposição a estar concluído só por crédito de equivalência.
     *
     * @return array<int, ProgressoAntigo>
     */
    public function getProgressoAntigoMap(): array
    {
        return ProgressoAntigo::query()
            ->where('jovem_id', $this->jovem()->id)
            ->with('registradoPor')
            ->get()
            ->keyBy('item_antigo_id')
            ->all();
    }

    /**
     * @return array<int, ProgressoNovo>
     */
    public function getProgressoNovoMap(): array
    {
        return ProgressoNovo::query()
            ->where('jovem_id', $this->jovem()->id)
            ->with('registradoPor')
            ->get()
            ->keyBy('item_novo_id')
            ->all();
    }

    /**
     * @return array{status: string, itens_necessarios: int, itens_concluidos: int}
     */
    public function statusCompetencia(CompetenciaAntiga $competencia): array
    {
        return app(StatusProgressaoService::class)->statusCompetencia($this->jovem(), $competencia);
    }

    /**
     * @return array{status: string, obrigatorias_necessarias: int, obrigatorias_concluidas: int, variaveis_necessarias: int, variaveis_concluidas: int, substitutiva_concluida: bool}
     */
    public function statusBloco(BlocoNovo $bloco): array
    {
        return app(StatusProgressaoService::class)->statusBloco($this->jovem(), $bloco);
    }

    /**
     * @return array{total: int, concluidas: int, percentual: float}
     */
    public function getPercentualAntigo(): array
    {
        return app(StatusProgressaoService::class)->percentualAntigo($this->jovem());
    }

    /**
     * @return array{total: int, concluidos: int, percentual: float}
     */
    public function getPercentualNovo(): array
    {
        return app(StatusProgressaoService::class)->percentualNovo($this->jovem());
    }

    /**
     * @return array<int, array{bloco: BlocoNovo, status: string, detalhe: string}>
     */
    public function getPendenciasNovo(): array
    {
        return app(StatusProgressaoService::class)->pendenciasNovo($this->jovem());
    }

    /**
     * @return array<int, ItemAntigo>
     */
    public function getPendenciasAntigo(): array
    {
        return app(StatusProgressaoService::class)->pendenciasAntigo($this->jovem());
    }

    /**
     * Concluído (direto OU crédito de equivalência). Usado pro estado visual do checkbox.
     */
    public function itemAntigoConcluido(ItemAntigo $item): bool
    {
        return app(EquivalenciaCreditoService::class)->itemAntigoConcluido($this->jovem(), $item);
    }

    /**
     * Concluído (direto OU crédito de equivalência). Usado pro estado visual do checkbox.
     */
    public function itemNovoConcluido(ItemNovo $item): bool
    {
        return app(EquivalenciaCreditoService::class)->itemNovoConcluido($this->jovem(), $item);
    }

    public function getEtapaAntigo(): string
    {
        return app(EtapaProgressaoService::class)->etapaAntigo($this->jovem());
    }

    public function getEtapaNovo(): string
    {
        return app(EtapaProgressaoService::class)->etapaNovo($this->jovem());
    }

    public function getElegivelReconhecimentoAntigo(): bool
    {
        return app(EtapaProgressaoService::class)->elegivelReconhecimentoAntigo($this->jovem());
    }

    public function getElegivelReconhecimentoNovo(): bool
    {
        return app(EtapaProgressaoService::class)->elegivelReconhecimentoNovo($this->jovem());
    }

    public function getNomeReconhecimentoAntigo(): string
    {
        return app(EtapaProgressaoService::class)->nomeReconhecimento($this->jovem()->ramoAtual, 'antigo');
    }

    public function getNomeReconhecimentoNovo(): string
    {
        return app(EtapaProgressaoService::class)->nomeReconhecimento($this->jovem()->ramoAtual, 'novo');
    }

    /**
     * @return array<int, array{chave: string, tipo: string, label: string, meta?: int, valor: bool|int}>
     */
    public function getRequisitosComplementaresAntigo(): array
    {
        return $this->requisitosComValor(
            app(EtapaProgressaoService::class)->chavesComplementaresAntigo($this->jovem()->ramoAtual->nome)
        );
    }

    /**
     * @return array<int, array{chave: string, tipo: string, label: string, meta?: int, valor: bool|int}>
     */
    public function getRequisitosComplementaresNovo(): array
    {
        return $this->requisitosComValor(
            app(EtapaProgressaoService::class)->chavesComplementaresNovo($this->jovem()->ramoAtual->nome)
        );
    }

    /**
     * @param  array<int, array{chave: string, tipo: string, label: string, meta?: int}>  $definicoes
     * @return array<int, array{chave: string, tipo: string, label: string, meta?: int, valor: bool|int}>
     */
    protected function requisitosComValor(array $definicoes): array
    {
        return array_map(
            fn (array $definicao) => [
                ...$definicao,
                'valor' => $definicao['tipo'] === 'contador'
                    ? $this->jovem()->requisitoNumero($definicao['chave'])
                    : $this->jovem()->requisitoBool($definicao['chave']),
            ],
            $definicoes
        );
    }

    /**
     * @return array{total: int, concluidos: int, percentual: float}
     */
    public function getResumoAntigo(): array
    {
        return app(StatusProgressaoService::class)->resumoAntigo($this->jovem());
    }

    /**
     * @return array{blocos_total: int, blocos_concluidos: int, obrigatorias_total: int, obrigatorias_concluidas: int, variaveis_minimas_total: int, variaveis_atingidas: int}
     */
    public function getResumoNovo(): array
    {
        return app(StatusProgressaoService::class)->resumoNovo($this->jovem());
    }

    /**
     * Itens "Variável" avulsos criados por um adulto especificamente pra
     * este jovem (ou pra ele e outros), dentro de um bloco — não fazem
     * parte do catálogo oficial (`ItemNovo`).
     *
     * @return Collection<int, ItemPersonalizado>
     */
    public function getItensPersonalizadosDoBloco(BlocoNovo $bloco): Collection
    {
        return ItemPersonalizado::query()
            ->where('bloco_novo_id', $bloco->id)
            ->whereHas('jovens', fn ($query) => $query->where('jovens.id', $this->jovem()->id))
            ->with('criadoPor')
            ->get();
    }

    /**
     * @return array<int, ProgressoPersonalizado>
     */
    public function getProgressoPersonalizadoMap(): array
    {
        return ProgressoPersonalizado::query()
            ->where('jovem_id', $this->jovem()->id)
            ->with('registradoPor')
            ->get()
            ->keyBy('item_personalizado_id')
            ->all();
    }

    public function itemPersonalizadoConcluido(ItemPersonalizado $item): bool
    {
        return ProgressoPersonalizado::query()
            ->where('jovem_id', $this->jovem()->id)
            ->where('item_personalizado_id', $item->id)
            ->where('concluido', true)
            ->exists();
    }
}
