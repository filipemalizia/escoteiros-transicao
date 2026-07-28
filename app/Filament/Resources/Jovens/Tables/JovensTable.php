<?php

namespace App\Filament\Resources\Jovens\Tables;

use App\Models\Ramo;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class JovensTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nome')
                    ->searchable(),
                TextColumn::make('data_nascimento')
                    ->date()
                    ->label('Data de nascimento'),
                TextColumn::make('ramoAtual.nome')
                    ->label('Ramo atual'),
            ])
            ->filters([
                SelectFilter::make('ramo_atual_id')
                    ->label('Ramo')
                    ->options(fn () => Ramo::pluck('nome', 'id')),
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
