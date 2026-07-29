<?php

namespace App\Filament\Resources\EquivalenciaBlocos\Pages;

use App\Filament\Resources\EquivalenciaBlocos\EquivalenciaBlocoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEquivalenciaBlocos extends ListRecords
{
    protected static string $resource = EquivalenciaBlocoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
