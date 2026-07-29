<?php

namespace App\Filament\Resources\EquivalenciaBlocos\Pages;

use App\Filament\Resources\EquivalenciaBlocos\EquivalenciaBlocoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEquivalenciaBloco extends EditRecord
{
    protected static string $resource = EquivalenciaBlocoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
