<?php

namespace App\Filament\Resources\CompetenciaAntigas;

use App\Filament\Resources\CompetenciaAntigas\Pages\CreateCompetenciaAntiga;
use App\Filament\Resources\CompetenciaAntigas\Pages\EditCompetenciaAntiga;
use App\Filament\Resources\CompetenciaAntigas\Pages\ListCompetenciaAntigas;
use App\Filament\Resources\CompetenciaAntigas\RelationManagers\ItensRelationManager;
use App\Filament\Resources\CompetenciaAntigas\Schemas\CompetenciaAntigaForm;
use App\Filament\Resources\CompetenciaAntigas\Tables\CompetenciaAntigasTable;
use App\Models\CompetenciaAntiga;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CompetenciaAntigaResource extends Resource
{
    protected static ?string $model = CompetenciaAntiga::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'Competência';

    protected static ?string $pluralModelLabel = 'Competências';

    protected static ?string $slug = 'competencias-antigas';

    public static function form(Schema $schema): Schema
    {
        return CompetenciaAntigaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompetenciaAntigasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItensRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompetenciaAntigas::route('/'),
            'create' => CreateCompetenciaAntiga::route('/create'),
            'edit' => EditCompetenciaAntiga::route('/{record}/edit'),
        ];
    }
}
