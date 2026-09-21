<?php

namespace App\Filament\Pages;

use App\Models\CategoriaImagem;
use App\Models\EspecialidadeDistintivo;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class GaleriaDeImagens extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Ferramentas';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Galeria de Imagens';

    protected static ?string $title = 'Galeria de Imagens';

    protected string $view = 'filament.pages.galeria-de-imagens';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    /**
     * @return Collection<int, CategoriaImagem>
     */
    public function getCategoriasEixo(): Collection
    {
        return CategoriaImagem::query()
            ->where('tipo', 'eixo')
            ->with('eixosNovos')
            ->orderBy('chave')
            ->get();
    }

    /**
     * @return Collection<int, CategoriaImagem>
     */
    public function getCategoriasBloco(): Collection
    {
        return CategoriaImagem::query()
            ->where('tipo', 'bloco')
            ->with('blocosNovos')
            ->orderBy('chave')
            ->get();
    }

    /**
     * @return Collection<int, EspecialidadeDistintivo>
     */
    public function getEspecialidadesDistintivos(): Collection
    {
        return EspecialidadeDistintivo::query()
            ->orderBy('tipo')
            ->orderBy('nome')
            ->get();
    }
}
