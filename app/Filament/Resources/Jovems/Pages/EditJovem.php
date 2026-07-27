<?php

namespace App\Filament\Resources\Jovems\Pages;

use App\Filament\Resources\Jovems\JovemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJovem extends EditRecord
{
    protected static string $resource = JovemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
