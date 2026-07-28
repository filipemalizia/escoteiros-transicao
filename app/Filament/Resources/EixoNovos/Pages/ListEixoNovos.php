<?php

namespace App\Filament\Resources\EixoNovos\Pages;

use App\Filament\Resources\EixoNovos\EixoNovoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEixoNovos extends ListRecords
{
    protected static string $resource = EixoNovoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
