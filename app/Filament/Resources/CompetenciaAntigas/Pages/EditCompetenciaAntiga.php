<?php

namespace App\Filament\Resources\CompetenciaAntigas\Pages;

use App\Filament\Resources\CompetenciaAntigas\CompetenciaAntigaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompetenciaAntiga extends EditRecord
{
    protected static string $resource = CompetenciaAntigaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
