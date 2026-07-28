<?php

namespace App\Filament\Resources\CompetenciaAntigas\Pages;

use App\Filament\Resources\CompetenciaAntigas\CompetenciaAntigaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompetenciaAntigas extends ListRecords
{
    protected static string $resource = CompetenciaAntigaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
