<?php

namespace App\Filament\Resources\EquivalenciaBlocos;

use App\Filament\Resources\EquivalenciaBlocos\Pages\CreateEquivalenciaBloco;
use App\Filament\Resources\EquivalenciaBlocos\Pages\EditEquivalenciaBloco;
use App\Filament\Resources\EquivalenciaBlocos\Pages\ListEquivalenciaBlocos;
use App\Filament\Resources\EquivalenciaBlocos\Schemas\EquivalenciaBlocoForm;
use App\Filament\Resources\EquivalenciaBlocos\Tables\EquivalenciaBlocosTable;
use App\Models\EquivalenciaBloco;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EquivalenciaBlocoResource extends Resource
{
    protected static ?string $model = EquivalenciaBloco::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare3Stack3d;

    protected static ?string $modelLabel = 'Equivalência de Bloco';

    protected static ?string $pluralModelLabel = 'Equivalências de Bloco';

    public static function form(Schema $schema): Schema
    {
        return EquivalenciaBlocoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EquivalenciaBlocosTable::configure($table);
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
            'index' => ListEquivalenciaBlocos::route('/'),
            'create' => CreateEquivalenciaBloco::route('/create'),
            'edit' => EditEquivalenciaBloco::route('/{record}/edit'),
        ];
    }
}
