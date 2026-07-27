<?php

namespace App\Filament\Resources\Jovems\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class JovemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome')
                    ->required(),
                DatePicker::make('data_nascimento')
                    ->required(),
                Select::make('ramo_atual_id')
                    ->relationship('ramoAtual', 'nome')
                    ->required(),
            ]);
    }
}
