<?php

namespace App\Filament\Resources\EspecialidadeDistintivos\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GruposRelationManager extends RelationManager
{
    protected static string $relationship = 'grupos';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('chave')
                    ->label('Nome do grupo (ex.: itens, conhecer, fazer, compartilhar)')
                    ->required(),
                TextInput::make('quantidade_minima')
                    ->label('Quantidade mínima')
                    ->numeric()
                    ->helperText('Deixe em branco se todos os itens do grupo forem obrigatórios pra conquista.'),
                TextInput::make('ordem')
                    ->numeric(),
                Repeater::make('itens')
                    ->relationship()
                    ->defaultItems(0)
                    ->schema([
                        Textarea::make('texto')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('ordem')
                            ->numeric(),
                    ])
                    ->columnSpanFull()
                    ->collapsed()
                    ->itemLabel(fn (array $state): ?string => $state['texto'] ?? null)
                    ->reorderable('ordem')
                    ->columns(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('chave')
            ->columns([
                TextColumn::make('chave'),
                TextColumn::make('quantidade_minima')
                    ->label('Mínimo')
                    ->formatStateUsing(fn (?int $state) => $state ?? 'Todos'),
                TextColumn::make('itens_count')
                    ->label('Itens')
                    ->counts('itens'),
                TextColumn::make('ordem'),
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
