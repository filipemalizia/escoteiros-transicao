<?php

namespace App\Filament\Resources\BlocoNovos\Schemas;

use App\Filament\Support\CategoriaImagemSelectField;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                CategoriaImagemSelectField::make(
                    tipo: 'bloco',
                    label: 'Imagem do bloco (compartilhada entre ramos)',
                    chaveLabel: 'Nome (normalmente igual ao título do bloco)',
                ),
            ]);
    }
}
