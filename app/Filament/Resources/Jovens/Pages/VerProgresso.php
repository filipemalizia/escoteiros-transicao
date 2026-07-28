<?php

namespace App\Filament\Resources\Jovens\Pages;

use App\Filament\Resources\Jovens\JovemResource;
use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\EixoNovo;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\ProgressoAntigo;
use App\Models\ProgressoNovo;
use App\Services\EquivalenciaCreditoService;
use App\Services\EtapaProgressaoService;
use App\Services\StatusProgressaoService;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class VerProgresso extends Page
{
    use InteractsWithRecord;

    protected static string $resource = JovemResource::class;

    protected string $view = 'filament.resources.jovens.pages.ver-progresso';

    public string $abaAtiva = 'antigo';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::getResource()::canView($this->getRecord()), 403);
    }

    public function getTitle(): string|Htmlable
    {
        return "Progresso de {$this->getRecord()->nome}";
    }

    public function getAreasAntigas(): Collection
    {
        return AreaDesenvolvimentoAntiga::query()
            ->where('ramo_id', $this->getRecord()->ramo_atual_id)
            ->with('competencias.itens')
            ->orderBy('nome')
            ->get();
    }

    public function getEixosNovos(): Collection
    {
        return EixoNovo::query()
            ->where('ramo_id', $this->getRecord()->ramo_atual_id)
            ->with('blocos.itens.especialidade')
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
            ->where('jovem_id', $this->getRecord()->id)
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
            ->where('jovem_id', $this->getRecord()->id)
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
        return app(StatusProgressaoService::class)->statusCompetencia($this->getRecord(), $competencia);
    }

    /**
     * @return array{status: string, obrigatorias_necessarias: int, obrigatorias_concluidas: int, variaveis_necessarias: int, variaveis_concluidas: int, substitutiva_concluida: bool}
     */
    public function statusBloco(BlocoNovo $bloco): array
    {
        return app(StatusProgressaoService::class)->statusBloco($this->getRecord(), $bloco);
    }

    /**
     * @return array{total: int, concluidas: int, percentual: float}
     */
    public function getPercentualAntigo(): array
    {
        return app(StatusProgressaoService::class)->percentualAntigo($this->getRecord());
    }

    /**
     * @return array{total: int, concluidos: int, percentual: float}
     */
    public function getPercentualNovo(): array
    {
        return app(StatusProgressaoService::class)->percentualNovo($this->getRecord());
    }

    /**
     * @return array<int, array{bloco: BlocoNovo, status: string, detalhe: string}>
     */
    public function getPendenciasNovo(): array
    {
        return app(StatusProgressaoService::class)->pendenciasNovo($this->getRecord());
    }

    /**
     * Concluído (direto OU crédito de equivalência). Usado pro estado visual do checkbox.
     */
    public function itemAntigoConcluido(ItemAntigo $item): bool
    {
        return app(EquivalenciaCreditoService::class)->itemAntigoConcluido($this->getRecord(), $item);
    }

    /**
     * Concluído (direto OU crédito de equivalência). Usado pro estado visual do checkbox.
     */
    public function itemNovoConcluido(ItemNovo $item): bool
    {
        return app(EquivalenciaCreditoService::class)->itemNovoConcluido($this->getRecord(), $item);
    }

    public function getEtapaAntigo(): string
    {
        return app(EtapaProgressaoService::class)->etapaAntigo($this->getRecord());
    }

    public function getEtapaNovo(): string
    {
        return app(EtapaProgressaoService::class)->etapaNovo($this->getRecord());
    }

    public function getElegivelReconhecimentoAntigo(): bool
    {
        return app(EtapaProgressaoService::class)->elegivelReconhecimentoAntigo($this->getRecord());
    }

    public function getElegivelReconhecimentoNovo(): bool
    {
        return app(EtapaProgressaoService::class)->elegivelReconhecimentoNovo($this->getRecord());
    }

    public function getNomeReconhecimentoAntigo(): string
    {
        return app(EtapaProgressaoService::class)->nomeReconhecimento($this->getRecord()->ramoAtual, 'antigo');
    }

    public function getNomeReconhecimentoNovo(): string
    {
        return app(EtapaProgressaoService::class)->nomeReconhecimento($this->getRecord()->ramoAtual, 'novo');
    }

    /**
     * @return array<int, array{chave: string, tipo: string, label: string, meta?: int, valor: bool|int}>
     */
    public function getRequisitosComplementaresAntigo(): array
    {
        return $this->requisitosComValor(
            app(EtapaProgressaoService::class)->chavesComplementaresAntigo($this->getRecord()->ramoAtual->nome)
        );
    }

    /**
     * @return array<int, array{chave: string, tipo: string, label: string, meta?: int, valor: bool|int}>
     */
    public function getRequisitosComplementaresNovo(): array
    {
        return $this->requisitosComValor(
            app(EtapaProgressaoService::class)->chavesComplementaresNovo($this->getRecord()->ramoAtual->nome)
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
                    ? $this->getRecord()->requisitoNumero($definicao['chave'])
                    : $this->getRecord()->requisitoBool($definicao['chave']),
            ],
            $definicoes
        );
    }

    public function toggleRequisitoBool(string $chave): void
    {
        $novoValor = ! $this->getRecord()->requisitoBool($chave);

        $this->getRecord()->requisitosComplementares()->updateOrCreate(
            ['chave' => $chave],
            ['tipo' => 'booleano', 'valor_booleano' => $novoValor, 'valor_numero' => null]
        );

        $this->getRecord()->unsetRelation('requisitosComplementares');
    }

    public function atualizarRequisitoNumero(string $chave, int $valor): void
    {
        $this->getRecord()->requisitosComplementares()->updateOrCreate(
            ['chave' => $chave],
            ['tipo' => 'contador', 'valor_numero' => max(0, $valor), 'valor_booleano' => null]
        );

        $this->getRecord()->unsetRelation('requisitosComplementares');
    }

    /**
     * @return array{total: int, concluidos: int, percentual: float}
     */
    public function getResumoAntigo(): array
    {
        return app(StatusProgressaoService::class)->resumoAntigo($this->getRecord());
    }

    /**
     * @return array{blocos_total: int, blocos_concluidos: int, obrigatorias_total: int, obrigatorias_concluidas: int, variaveis_minimas_total: int, variaveis_atingidas: int}
     */
    public function getResumoNovo(): array
    {
        return app(StatusProgressaoService::class)->resumoNovo($this->getRecord());
    }

    public function toggleAntigo(int $itemAntigoId): void
    {
        $progresso = ProgressoAntigo::query()->firstOrNew([
            'jovem_id' => $this->getRecord()->id,
            'item_antigo_id' => $itemAntigoId,
        ]);

        $concluido = ! $progresso->concluido;

        $progresso->concluido = $concluido;
        $progresso->data_conclusao = $concluido ? Carbon::today() : null;
        $progresso->registrado_por_id = auth()->id();
        $progresso->save();
    }

    public function toggleNovo(int $itemNovoId): void
    {
        $progresso = ProgressoNovo::query()->firstOrNew([
            'jovem_id' => $this->getRecord()->id,
            'item_novo_id' => $itemNovoId,
        ]);

        $concluido = ! $progresso->concluido;

        $progresso->concluido = $concluido;
        $progresso->data_conclusao = $concluido ? Carbon::today() : null;
        $progresso->registrado_por_id = auth()->id();
        $progresso->save();
    }
}
