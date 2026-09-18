<?php

namespace App\Filament\Pages;

use App\Services\Importacao\ImportadorEspecialidadesService;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;
use UnitEnum;

/**
 * Upload manual do catálogo de Especialidades/Insígnias (JSON extraído pelo
 * paxtu-scraper, ferramenta separada deste repositório) — sem passar pelo
 * git, já que o repositório é público e esse arquivo é dado raspado de
 * terceiros. O catálogo é fixo (sem rotina de atualização automatizada por
 * enquanto), então essa tela cobre bem o caso de uso: uma ação manual,
 * pontual, feita pelo admin logado.
 */
class ImportarEspecialidades extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static string|UnitEnum|null $navigationGroup = 'Ferramentas';

    protected static ?int $navigationSort = 35;

    protected static ?string $navigationLabel = 'Importar Especialidades';

    protected static ?string $title = 'Importar Especialidades';

    protected string $view = 'filament.pages.importar-especialidades';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    /** @var array<string, mixed> */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $resumo = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                FileUpload::make('arquivo')
                    ->label('Catálogo de Especialidades (.json, gerado pelo paxtu-scraper)')
                    ->acceptedFileTypes(['application/json', 'text/plain'])
                    ->storeFiles(false)
                    ->required(),
                Toggle::make('dry_run')
                    ->label('Simular (não grava nada, só mostra o que aconteceria)')
                    ->default(false),
            ]);
    }

    public function importar(ImportadorEspecialidadesService $servico): void
    {
        $data = $this->form->getState();

        /** @var TemporaryUploadedFile $arquivo */
        $arquivo = $data['arquivo'];

        $catalogo = json_decode(file_get_contents($arquivo->getRealPath()), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Notification::make()
                ->title('Não foi possível ler o arquivo. Confirme que é um .json válido.')
                ->danger()
                ->send();

            return;
        }

        $dryRun = (bool) ($data['dry_run'] ?? false);

        try {
            $resumo = $servico->importar($catalogo, $dryRun);
        } catch (Throwable $e) {
            Log::error('Falha ao importar catálogo de especialidades', ['erro' => $e->getMessage()]);

            Notification::make()
                ->title('Falha ao importar: '.$e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->resumo = [
            'criadas' => $resumo->criadas,
            'atualizadas' => $resumo->atualizadas,
            'adotadas' => $resumo->adotadas,
            'eixos_novos_criados' => $resumo->eixosNovosCriados,
            'imagens_baixadas' => $resumo->imagensBaixadas,
            'imagens_puladas' => $resumo->imagensPuladas,
            'imagens_com_erro' => $resumo->imagensComErro,
            'imagens_simuladas' => $resumo->imagensSimuladas,
            'erros' => $resumo->erros,
            'dry_run' => $dryRun,
        ];

        $notification = Notification::make()
            ->body("{$resumo->criadas} criadas, {$resumo->atualizadas} atualizadas, {$resumo->adotadas} adotadas.");

        match (true) {
            filled($resumo->erros) => $notification
                ->title('Importação concluída com pendências - confira os erros abaixo')
                ->warning(),
            default => $notification
                ->title($dryRun ? 'Simulação concluída' : 'Importação concluída')
                ->success(),
        };

        $notification->send();
    }
}
