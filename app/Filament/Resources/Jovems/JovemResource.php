<?php

namespace App\Filament\Resources\Jovems;

use App\Filament\Resources\Jovems\Pages\CreateJovem;
use App\Filament\Resources\Jovems\Pages\EditJovem;
use App\Filament\Resources\Jovems\Pages\ListJovems;
use App\Filament\Resources\Jovems\Schemas\JovemForm;
use App\Filament\Resources\Jovems\Tables\JovemsTable;
use App\Models\Jovem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class JovemResource extends Resource
{
    protected static ?string $model = Jovem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return JovemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JovemsTable::configure($table);
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
            'index' => ListJovems::route('/'),
            'create' => CreateJovem::route('/create'),
            'edit' => EditJovem::route('/{record}/edit'),
        ];
    }
}
