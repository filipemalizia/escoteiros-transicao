<?php

namespace App\Filament\Resources\EquivalenciaEspecialidades\Schemas;

use App\Models\EspecialidadeDistintivo;
use App\Models\ItemNovo;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EquivalenciaEspecialidadeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('especialidade_distintivo_id')
                    ->label('Especialidade/Insígnia')
                    ->relationship('especialidadeDistintivo', 'nome')
                    ->getOptionLabelFromRecordUsing(fn (EspecialidadeDistintivo $especialidade) => sprintf(
                        '%s: %s',
                        $especialidade->tipo,
                        $especialidade->nome,
                    ))
                    ->searchable()
                    ->required(),
                Select::make('item_novo_id')
                    ->label('Item (Novo)')
                    ->relationship('itemNovo', 'codigo')
                    ->getOptionLabelFromRecordUsing(fn (ItemNovo $item) => sprintf(
                        '%s / %s / %s / %s: %s',
                        $item->bloco->eixo->ramo->nome,
                        $item->bloco->eixo->nome,
                        $item->bloco->titulo,
                        $item->codigo,
                        Str::limit($item->descricao, 40),
                    ))
                    ->searchable()
                    ->required(),
                Textarea::make('observacao')
                    ->columnSpanFull(),
            ]);
    }
}
