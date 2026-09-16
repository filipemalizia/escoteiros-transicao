<?php

namespace App\Filament\Resources\Equipes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EquipeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('ramo_id')
                    ->label('Ramo')
                    ->relationship('ramo', 'nome')
                    ->required(),
                TextInput::make('nome')
                    ->required(),
                Select::make('usuarios')
                    ->label('Usuários responsáveis')
                    ->relationship('usuarios', 'name')
                    ->multiple()
                    ->preload(),
            ]);
    }
}
