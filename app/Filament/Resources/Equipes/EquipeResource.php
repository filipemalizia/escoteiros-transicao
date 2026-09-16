<?php

namespace App\Filament\Resources\Equipes;

use App\Filament\Resources\Equipes\Pages\CreateEquipe;
use App\Filament\Resources\Equipes\Pages\EditEquipe;
use App\Filament\Resources\Equipes\Pages\ListEquipes;
use App\Filament\Resources\Equipes\RelationManagers\JovensRelationManager;
use App\Filament\Resources\Equipes\Schemas\EquipeForm;
use App\Filament\Resources\Equipes\Tables\EquipesTable;
use App\Models\Equipe;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EquipeResource extends Resource
{
    protected static ?string $model = Equipe::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = -20;

    protected static ?string $modelLabel = 'Equipe';

    protected static ?string $pluralModelLabel = 'Equipes';

    protected static ?string $slug = 'equipes';

    public static function form(Schema $schema): Schema
    {
        return EquipeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EquipesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            JovensRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEquipes::route('/'),
            'create' => CreateEquipe::route('/create'),
            'edit' => EditEquipe::route('/{record}/edit'),
        ];
    }
}
