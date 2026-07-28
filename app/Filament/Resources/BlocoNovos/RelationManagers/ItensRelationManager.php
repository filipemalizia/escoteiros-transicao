<?php

namespace App\Filament\Resources\BlocoNovos\RelationManagers;

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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('codigo')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Textarea::make('descricao')
                    ->required()
                    ->columnSpanFull(),
                Select::make('tipo_acao')
                    ->label('Tipo')
                    ->options([
                        'Obrigatória' => 'Obrigatória',
                        'Variável' => 'Variável',
                        'Substitutiva' => 'Substitutiva',
                    ])
                    ->required(),
                Select::make('modalidade')
                    ->options([
                        'Geral' => 'Geral',
                        'Ar' => 'Ar',
                        'Mar' => 'Mar',
                    ])
                    ->default('Geral'),
                Select::make('especialidade_id')
                    ->label('Especialidade/Distintivo')
                    ->relationship('especialidade', 'nome')
                    ->searchable()
                    ->createOptionForm([
                        TextInput::make('nome')
                            ->required(),
                        Select::make('tipo')
                            ->options([
                                'Especialidade' => 'Especialidade',
                                'Insígnia' => 'Insígnia',
                            ])
                            ->required(),
                    ]),
                TextInput::make('observacao')
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
                TextColumn::make('descricao')
                    ->limit(60),
                TextColumn::make('tipo_acao')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('modalidade'),
                TextColumn::make('especialidade.nome')
                    ->label('Especialidade/Distintivo'),
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
