<?php

namespace App\Filament\Resources\AreaDesenvolvimentoAntigas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AreaDesenvolvimentoAntigaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('ramo_id')
                    ->relationship('ramo', 'nome')
                    ->required(),
                TextInput::make('nome')
                    ->required(),
            ]);
    }
}
