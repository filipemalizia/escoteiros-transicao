<?php

namespace App\Filament\Resources\AreaDesenvolvimentoAntigas;

use App\Filament\Resources\AreaDesenvolvimentoAntigas\Pages\CreateAreaDesenvolvimentoAntiga;
use App\Filament\Resources\AreaDesenvolvimentoAntigas\Pages\EditAreaDesenvolvimentoAntiga;
use App\Filament\Resources\AreaDesenvolvimentoAntigas\Pages\ListAreaDesenvolvimentoAntigas;
use App\Filament\Resources\AreaDesenvolvimentoAntigas\Schemas\AreaDesenvolvimentoAntigaForm;
use App\Filament\Resources\AreaDesenvolvimentoAntigas\Tables\AreaDesenvolvimentoAntigasTable;
use App\Models\AreaDesenvolvimentoAntiga;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AreaDesenvolvimentoAntigaResource extends Resource
{
    protected static ?string $model = AreaDesenvolvimentoAntiga::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Programa Antigo';

    protected static ?string $modelLabel = 'Área de Desenvolvimento';

    protected static ?string $pluralModelLabel = 'Áreas de Desenvolvimento';

    protected static ?string $slug = 'areas-desenvolvimento-antigas';

    public static function form(Schema $schema): Schema
    {
        return AreaDesenvolvimentoAntigaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AreaDesenvolvimentoAntigasTable::configure($table);
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
            'index' => ListAreaDesenvolvimentoAntigas::route('/'),
            'create' => CreateAreaDesenvolvimentoAntiga::route('/create'),
            'edit' => EditAreaDesenvolvimentoAntiga::route('/{record}/edit'),
        ];
    }
}
