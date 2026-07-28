<?php

namespace App\Filament\Resources\BlocoNovos\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class BlocoNovoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('eixo_id')
                    ->relationship('eixo', 'nome')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->ramo->nome} / {$record->nome}")
                    ->searchable()
                    ->required(),
                TextInput::make('titulo')
                    ->required(),
                Textarea::make('descricao')
                    ->columnSpanFull(),
                TextInput::make('quantidade_minima_variaveis')
                    ->numeric(),
            ]);
    }
}
