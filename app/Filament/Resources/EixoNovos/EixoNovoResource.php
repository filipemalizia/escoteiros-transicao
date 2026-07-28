<?php

namespace App\Filament\Resources\EixoNovos;

use App\Filament\Resources\EixoNovos\Pages\CreateEixoNovo;
use App\Filament\Resources\EixoNovos\Pages\EditEixoNovo;
use App\Filament\Resources\EixoNovos\Pages\ListEixoNovos;
use App\Filament\Resources\EixoNovos\Schemas\EixoNovoForm;
use App\Filament\Resources\EixoNovos\Tables\EixoNovosTable;
use App\Models\EixoNovo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EixoNovoResource extends Resource
{
    protected static ?string $model = EixoNovo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'Eixo';

    protected static ?string $pluralModelLabel = 'Eixos';

    protected static ?string $slug = 'eixos-novos';

    public static function form(Schema $schema): Schema
    {
        return EixoNovoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EixoNovosTable::configure($table);
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
            'index' => ListEixoNovos::route('/'),
            'create' => CreateEixoNovo::route('/create'),
            'edit' => EditEixoNovo::route('/{record}/edit'),
        ];
    }
}
