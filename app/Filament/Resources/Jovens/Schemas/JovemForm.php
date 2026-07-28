<?php

namespace App\Filament\Resources\Jovens\Schemas;

use App\Models\Ramo;
use App\Services\EtapaProgressaoService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
                    ->label('Ramo')
                    ->relationship('ramoAtual', 'nome')
                    ->live()
                    ->required(),

                Section::make('Requisitos Complementares')
                    ->description('Requisitos que não vêm do checklist de Itens (contadores, insígnias, recomendações).')
                    ->visible(fn (Get $get) => filled($get('ramo_atual_id')))
                    ->schema(fn (Get $get) => static::secoesRequisitos($get('ramo_atual_id')))
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    protected static function secoesRequisitos(?int $ramoId): array
    {
        $ramo = blank($ramoId) ? null : Ramo::find($ramoId);

        if (! $ramo) {
            return [];
        }

        $service = new EtapaProgressaoService;

        return [
            Section::make('Programa Antigo')
                ->schema(static::camposParaChaves($service->chavesComplementaresAntigo($ramo->nome), 'antigo'))
                ->columns(2),
            Section::make('Programa Novo')
                ->schema(static::camposParaChaves($service->chavesComplementaresNovo($ramo->nome), 'novo'))
                ->columns(2),
        ];
    }

    /**
     * @param  array<int, array{chave: string, tipo: string, label: string, meta?: int}>  $chaves
     * @return array<int, Component>
     */
    protected static function camposParaChaves(array $chaves, string $sistema): array
    {
        return array_map(
            fn (array $definicao) => $definicao['tipo'] === 'contador'
                ? TextInput::make("requisitos_{$sistema}.{$definicao['chave']}")
                    ->label($definicao['label'].(isset($definicao['meta']) ? " (meta: {$definicao['meta']})" : ''))
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                : Toggle::make("requisitos_{$sistema}.{$definicao['chave']}")
                    ->label($definicao['label']),
            $chaves
        );
    }
}
