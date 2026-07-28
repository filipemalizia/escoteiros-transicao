<?php

namespace App\Filament\Resources\BlocoNovos\Pages;

use App\Filament\Resources\BlocoNovos\BlocoNovoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBlocoNovos extends ListRecords
{
    protected static string $resource = BlocoNovoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
