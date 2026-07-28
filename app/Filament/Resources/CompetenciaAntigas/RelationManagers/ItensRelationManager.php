<?php

namespace App\Filament\Resources\CompetenciaAntigas\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItensRelationManager extends RelationManager
{
    protected static string $relationship = 'itens';

    protected const ETAPAS_POR_RAMO = [
        'Lobinho' => ['Pata Tenra', 'Saltador', 'Rastreador', 'Caçador'],
        'Escoteiro' => ['Pista', 'Trilha', 'Rumo', 'Travessia'],
    ];

    protected function ramoDaCompetencia(): string
    {
        return $this->getOwnerRecord()->areaDesenvolvimento->ramo->nome;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('codigo')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('etapa')
                    ->options(fn () => array_combine(
                        self::ETAPAS_POR_RAMO[$this->ramoDaCompetencia()] ?? [],
                        self::ETAPAS_POR_RAMO[$this->ramoDaCompetencia()] ?? [],
                    ))
                    ->visible(fn () => array_key_exists($this->ramoDaCompetencia(), self::ETAPAS_POR_RAMO))
                    ->required(fn () => array_key_exists($this->ramoDaCompetencia(), self::ETAPAS_POR_RAMO)),
                Textarea::make('descricao')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('codigo')
            ->columns([
                TextColumn::make('codigo')
                    ->searchable(),
                TextColumn::make('etapa')
                    ->visible(fn () => array_key_exists($this->ramoDaCompetencia(), self::ETAPAS_POR_RAMO)),
                TextColumn::make('descricao')
                    ->limit(60),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
