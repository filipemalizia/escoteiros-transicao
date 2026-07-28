<?php

namespace App\Filament\Resources\EspecialidadeDistintivos;

use App\Filament\Resources\EspecialidadeDistintivos\Pages\CreateEspecialidadeDistintivo;
use App\Filament\Resources\EspecialidadeDistintivos\Pages\EditEspecialidadeDistintivo;
use App\Filament\Resources\EspecialidadeDistintivos\Pages\ListEspecialidadeDistintivos;
use App\Filament\Resources\EspecialidadeDistintivos\Schemas\EspecialidadeDistintivoForm;
use App\Filament\Resources\EspecialidadeDistintivos\Tables\EspecialidadeDistintivosTable;
use App\Models\EspecialidadeDistintivo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EspecialidadeDistintivoResource extends Resource
{
    protected static ?string $model = EspecialidadeDistintivo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'Especialidade/Distintivo';

    protected static ?string $pluralModelLabel = 'Especialidades/Distintivos';

    protected static ?string $slug = 'especialidades-distintivos';

    public static function form(Schema $schema): Schema
    {
        return EspecialidadeDistintivoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EspecialidadeDistintivosTable::configure($table);
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
            'index' => ListEspecialidadeDistintivos::route('/'),
            'create' => CreateEspecialidadeDistintivo::route('/create'),
            'edit' => EditEspecialidadeDistintivo::route('/{record}/edit'),
        ];
    }
}
