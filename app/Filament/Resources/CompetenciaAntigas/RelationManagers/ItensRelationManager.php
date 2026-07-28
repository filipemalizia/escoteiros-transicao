<?php

namespace App\Filament\Resources\CompetenciaAntigas\RelationManagers;

use App\Services\EtapaProgressaoService;
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
                        $etapas = EtapaProgressaoService::etapasAntigoPorRamo($this->ramoDaCompetencia()),
                        $etapas,
                    ))
                    ->visible(fn () => filled(EtapaProgressaoService::etapasAntigoPorRamo($this->ramoDaCompetencia())))
                    ->required(fn () => filled(EtapaProgressaoService::etapasAntigoPorRamo($this->ramoDaCompetencia()))),
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
                    ->visible(fn () => filled(EtapaProgressaoService::etapasAntigoPorRamo($this->ramoDaCompetencia()))),
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
