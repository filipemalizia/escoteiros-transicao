<?php

namespace App\Filament\Resources\Jovens;

use App\Filament\Resources\Jovens\Pages\CreateJovem;
use App\Filament\Resources\Jovens\Pages\EditJovem;
use App\Filament\Resources\Jovens\Pages\ListJovens;
use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Filament\Resources\Jovens\Schemas\JovemForm;
use App\Filament\Resources\Jovens\Tables\JovensTable;
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

    protected static ?string $modelLabel = 'Jovem';

    protected static ?string $pluralModelLabel = 'Jovens';

    protected static ?string $slug = 'jovens';

    public static function form(Schema $schema): Schema
    {
        return JovemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JovensTable::configure($table);
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
            'index' => ListJovens::route('/'),
            'create' => CreateJovem::route('/create'),
            'edit' => EditJovem::route('/{record}/edit'),
            'progresso' => VerProgresso::route('/{record}/progresso'),
        ];
    }
}
