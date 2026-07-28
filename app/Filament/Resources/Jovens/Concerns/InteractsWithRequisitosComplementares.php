<?php

namespace App\Filament\Resources\Jovens\Concerns;

use App\Services\EtapaProgressaoService;
use Illuminate\Support\Collection;

trait InteractsWithRequisitosComplementares
{
    /** @var array<string, mixed> */
    protected array $requisitosComplementaresPendentes = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extrairRequisitosComplementares(array $data): array
    {
        $this->requisitosComplementaresPendentes = [
            ...($data['requisitos_antigo'] ?? []),
            ...($data['requisitos_novo'] ?? []),
        ];

        unset($data['requisitos_antigo'], $data['requisitos_novo']);

        return $data;
    }

    protected function persistirRequisitosComplementares(): void
    {
        $definicoes = $this->definicoesRequisitos($this->record->ramoAtual->nome);

        foreach ($this->requisitosComplementaresPendentes as $chave => $valor) {
            $definicao = $definicoes->get($chave);

            if (! $definicao) {
                continue;
            }

            $this->record->requisitosComplementares()->updateOrCreate(
                ['chave' => $chave],
                $definicao['tipo'] === 'contador'
                    ? ['tipo' => 'contador', 'valor_numero' => (int) $valor, 'valor_booleano' => null]
                    : ['tipo' => 'booleano', 'valor_booleano' => (bool) $valor, 'valor_numero' => null]
            );
        }
    }

    /**
     * @return Collection<string, array{chave: string, tipo: string, label: string, meta?: int}>
     */
    protected function definicoesRequisitos(string $ramoNome): Collection
    {
        $service = new EtapaProgressaoService;

        return collect($service->chavesComplementaresAntigo($ramoNome))
            ->concat($service->chavesComplementaresNovo($ramoNome))
            ->keyBy('chave');
    }
}
