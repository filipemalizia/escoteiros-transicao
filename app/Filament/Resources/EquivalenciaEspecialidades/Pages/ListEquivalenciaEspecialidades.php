<?php

namespace App\Filament\Resources\EquivalenciaEspecialidades\Pages;

use App\Filament\Resources\EquivalenciaEspecialidades\EquivalenciaEspecialidadeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEquivalenciaEspecialidades extends ListRecords
{
    protected static string $resource = EquivalenciaEspecialidadeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
