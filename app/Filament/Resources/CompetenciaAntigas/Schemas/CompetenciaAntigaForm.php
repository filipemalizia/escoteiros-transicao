<?php

namespace App\Filament\Resources\CompetenciaAntigas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CompetenciaAntigaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('area_desenvolvimento_id')
                    ->relationship('areaDesenvolvimento', 'nome')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->ramo->nome} / {$record->nome}")
                    ->searchable()
                    ->required(),
                Textarea::make('descricao')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
