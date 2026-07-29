<?php

namespace App\Filament\Resources\EquivalenciaBlocos\Schemas;

use App\Models\BlocoNovo;
use App\Models\ItemAntigo;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EquivalenciaBlocoForm
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
                Select::make('bloco_novo_id')
                    ->label('Bloco (Novo)')
                    ->relationship('blocoNovo', 'titulo')
                    ->getOptionLabelFromRecordUsing(fn (BlocoNovo $bloco) => sprintf(
                        '%s / %s / %s',
                        $bloco->eixo->ramo->nome,
                        $bloco->eixo->nome,
                        $bloco->titulo,
                    ))
                    ->searchable()
                    ->required(),
                Textarea::make('observacao')
                    ->columnSpanFull(),
            ]);
    }
}
