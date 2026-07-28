<?php

namespace App\Filament\Resources\EixoNovos\Pages;

use App\Filament\Resources\EixoNovos\EixoNovoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEixoNovo extends EditRecord
{
    protected static string $resource = EixoNovoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
