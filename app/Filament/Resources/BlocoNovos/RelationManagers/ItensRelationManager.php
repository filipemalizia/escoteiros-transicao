<?php

namespace App\Filament\Resources\BlocoNovos\RelationManagers;

use App\Models\ItemNovo;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

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
                DeleteAction::make()
                    ->before(function (ItemNovo $record, DeleteAction $action) {
                        if ($record->possuiDadosVinculados()) {
                            Notification::make()
                                ->title('Não é possível excluir este item')
                                ->body('Existe progresso registrado por algum jovem, ou equivalências cadastradas, vinculados a este item. Remova essas dependências antes de excluir.')
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (Collection $records, DeleteBulkAction $action) {
                            if ($records->contains(fn (ItemNovo $item) => $item->possuiDadosVinculados())) {
                                Notification::make()
                                    ->title('Não é possível excluir um ou mais itens selecionados')
                                    ->body('Existe progresso registrado por algum jovem, ou equivalências cadastradas, vinculados a algum dos itens selecionados. Remova essas dependências antes de excluir.')
                                    ->danger()
                                    ->send();

                                $action->halt();
                            }
                        }),
                ]),
            ]);
    }
}
