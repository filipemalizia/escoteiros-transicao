<?php

namespace App\Filament\Resources\Equivalencias\Pages;

use App\Filament\Resources\Equivalencias\EquivalenciaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEquivalencia extends EditRecord
{
    protected static string $resource = EquivalenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
