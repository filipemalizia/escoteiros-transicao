<?php

namespace App\Filament\Resources\EquivalenciaEspecialidades\Pages;

use App\Filament\Resources\EquivalenciaEspecialidades\EquivalenciaEspecialidadeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEquivalenciaEspecialidade extends EditRecord
{
    protected static string $resource = EquivalenciaEspecialidadeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
