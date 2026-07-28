<?php

namespace App\Filament\Resources\BlocoNovos\Pages;

use App\Filament\Resources\BlocoNovos\BlocoNovoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlocoNovo extends EditRecord
{
    protected static string $resource = BlocoNovoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
