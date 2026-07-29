<?php

namespace App\Filament\Resources\BlocoNovos;

use App\Filament\Resources\BlocoNovos\Pages\CreateBlocoNovo;
use App\Filament\Resources\BlocoNovos\Pages\EditBlocoNovo;
use App\Filament\Resources\BlocoNovos\Pages\ListBlocoNovos;
use App\Filament\Resources\BlocoNovos\RelationManagers\ItensRelationManager;
use App\Filament\Resources\BlocoNovos\Schemas\BlocoNovoForm;
use App\Filament\Resources\BlocoNovos\Tables\BlocoNovosTable;
use App\Models\BlocoNovo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BlocoNovoResource extends Resource
{
    protected static ?string $model = BlocoNovo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Programa Novo';

    protected static ?string $modelLabel = 'Bloco';

    protected static ?string $pluralModelLabel = 'Blocos';

    protected static ?string $slug = 'blocos-novos';

    public static function form(Schema $schema): Schema
    {
        return BlocoNovoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlocoNovosTable::configure($table);
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
            'index' => ListBlocoNovos::route('/'),
            'create' => CreateBlocoNovo::route('/create'),
            'edit' => EditBlocoNovo::route('/{record}/edit'),
        ];
    }
}
