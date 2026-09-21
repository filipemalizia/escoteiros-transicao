<?php

namespace App\Filament\Resources\EquivalenciaEspecialidades;

use App\Filament\Resources\EquivalenciaEspecialidades\Pages\CreateEquivalenciaEspecialidade;
use App\Filament\Resources\EquivalenciaEspecialidades\Pages\EditEquivalenciaEspecialidade;
use App\Filament\Resources\EquivalenciaEspecialidades\Pages\ListEquivalenciaEspecialidades;
use App\Filament\Resources\EquivalenciaEspecialidades\Schemas\EquivalenciaEspecialidadeForm;
use App\Filament\Resources\EquivalenciaEspecialidades\Tables\EquivalenciaEspecialidadesTable;
use App\Models\EquivalenciaEspecialidade;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EquivalenciaEspecialidadeResource extends Resource
{
    protected static ?string $model = EquivalenciaEspecialidade::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'Ferramentas';

    protected static ?int $navigationSort = 60;

    protected static ?string $modelLabel = 'Equivalência de Especialidade';

    protected static ?string $pluralModelLabel = 'Equivalências de Especialidade';

    public static function form(Schema $schema): Schema
    {
        return EquivalenciaEspecialidadeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EquivalenciaEspecialidadesTable::configure($table);
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
            'index' => ListEquivalenciaEspecialidades::route('/'),
            'create' => CreateEquivalenciaEspecialidade::route('/create'),
            'edit' => EditEquivalenciaEspecialidade::route('/{record}/edit'),
        ];
    }
}
