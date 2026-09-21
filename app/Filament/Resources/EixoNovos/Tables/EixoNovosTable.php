<?php

namespace App\Filament\Resources\EixoNovos\Tables;

use App\Filament\Support\AtribuirImagemBulkAction;
use App\Models\Ramo;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EixoNovosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('categoriaImagem.imagem')
                    ->label('Imagem')
                    ->state(fn ($record) => $record->categoriaImagem?->getFirstMediaUrl('imagem') ?: null)
                    ->size(40),
                TextColumn::make('ramo.nome')
                    ->label('Ramo')
                    ->searchable(),
                TextColumn::make('nome')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('ramo_id')
                    ->label('Ramo')
                    ->options(fn () => Ramo::pluck('nome', 'id')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    AtribuirImagemBulkAction::make('eixo', 'Nome do eixo'),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
