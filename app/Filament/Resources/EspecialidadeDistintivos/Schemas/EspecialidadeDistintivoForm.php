<?php

namespace App\Filament\Resources\EspecialidadeDistintivos\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EspecialidadeDistintivoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome')
                    ->required(),
                Select::make('tipo')
                    ->options(['Especialidade' => 'Especialidade', 'Insígnia' => 'Insígnia'])
                    ->required(),
            ]);
    }
}
