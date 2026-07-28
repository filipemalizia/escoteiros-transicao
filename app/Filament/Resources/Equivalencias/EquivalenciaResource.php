<?php

namespace App\Filament\Resources\Equivalencias;

use App\Filament\Resources\Equivalencias\Pages\CreateEquivalencia;
use App\Filament\Resources\Equivalencias\Pages\EditEquivalencia;
use App\Filament\Resources\Equivalencias\Pages\ListEquivalencias;
use App\Filament\Resources\Equivalencias\Schemas\EquivalenciaForm;
use App\Filament\Resources\Equivalencias\Tables\EquivalenciasTable;
use App\Models\Equivalencia;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EquivalenciaResource extends Resource
{
    protected static ?string $model = Equivalencia::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'Equivalência';

    protected static ?string $pluralModelLabel = 'Equivalências';

    public static function form(Schema $schema): Schema
    {
        return EquivalenciaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EquivalenciasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEquivalencias::route('/'),
            'create' => CreateEquivalencia::route('/create'),
            'edit' => EditEquivalencia::route('/{record}/edit'),
        ];
    }
}
