<?php

namespace App\Filament\Widgets;

use App\Models\Equipe;
use App\Models\Jovem;
use App\Models\ProgressoAntigo;
use App\Models\ProgressoNovo;
use App\Models\ProgressoPersonalizado;
use App\Models\Ramo;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EstatisticasOverview extends StatsOverviewWidget
{
    /**
     * Ordem oficial dos ramos (não alfabética) — igual à usada em
     * `ExibeProgressoDoJovem::ORDEM_AREAS_ANTIGAS` pro mesmo tipo de caso.
     */
    private const ORDEM_RAMOS = ['Lobinho', 'Escoteiro', 'Sênior', 'Pioneiro'];

    protected function getStats(): array
    {
        $stats = [
            Stat::make('Total de Jovens', Jovem::count()),
            Stat::make('Total de Equipes', Equipe::count()),
            $this->statAvaliacoesPendentes(),
        ];

        $ramos = Ramo::withCount('jovens')->get()->sortBy(function (Ramo $ramo) {
            $indice = array_search($ramo->nome, self::ORDEM_RAMOS, true);

            return $indice === false ? 999 : $indice;
        });

        foreach ($ramos as $ramo) {
            $stats[] = Stat::make("Jovens - {$ramo->nome}", $ramo->jovens_count);
        }

        return $stats;
    }

    /**
     * Quantidade de itens aguardando avaliação do adulto — só conta jovens
     * das equipes que o usuário logado tem acesso (todas, se for admin).
     */
    private function statAvaliacoesPendentes(): Stat
    {
        $user = auth()->user();

        $jovensQuery = Jovem::query();

        if (! $user?->isAdmin()) {
            $jovensQuery->whereIn('equipe_id', $user?->equipes()->pluck('equipes.id') ?? []);
        }

        $jovemIds = $jovensQuery->pluck('id');

        $total = ProgressoAntigo::query()->whereIn('jovem_id', $jovemIds)->where('solicitado_pelo_jovem', true)->where('concluido', false)->count()
            + ProgressoNovo::query()->whereIn('jovem_id', $jovemIds)->where('solicitado_pelo_jovem', true)->where('concluido', false)->count()
            + ProgressoPersonalizado::query()->whereIn('jovem_id', $jovemIds)->where('solicitado_pelo_jovem', true)->where('concluido', false)->count();

        return Stat::make('Itens Aguardando Avaliação', $total)
            ->description($user?->isAdmin() ? 'Em todas as equipes' : 'Nas suas equipes')
            ->color($total > 0 ? 'warning' : 'success');
    }
}
