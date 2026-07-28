<?php

namespace App\Filament\Resources\Equivalencias\Schemas;

use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EquivalenciaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('item_antigo_id')
                    ->label('Item (Antigo)')
                    ->relationship('itemAntigo', 'codigo')
                    ->getOptionLabelFromRecordUsing(fn (ItemAntigo $item) => sprintf(
                        '%s / %s / %s / %s: %s',
                        $item->competencia->areaDesenvolvimento->ramo->nome,
                        $item->competencia->areaDesenvolvimento->nome,
                        Str::limit($item->competencia->descricao, 30),
                        $item->codigo,
                        Str::limit($item->descricao, 40),
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
                Select::make('tipo_equivalencia')
                    ->label('Tipo de equivalência')
                    ->options([
                        '1-1' => '1 para 1',
                        'N-1' => 'N para 1 (vários antigos → um novo)',
                        '1-N' => '1 para N (um antigo → vários novos)',
                    ])
                    ->required(),
                Textarea::make('observacao')
                    ->columnSpanFull(),
            ]);
    }
}
