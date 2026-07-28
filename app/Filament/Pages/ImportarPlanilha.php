<?php

namespace App\Filament\Pages;

use App\Models\Ramo;
use App\Services\Importacao\ImportadorAntigoService;
use App\Services\Importacao\ImportadorNovoService;
use App\Services\Importacao\RawSheetImport;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ImportarPlanilha extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?string $navigationLabel = 'Importar Planilha';

    protected static ?string $title = 'Importar Planilha';

    protected string $view = 'filament.pages.importar-planilha';

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
                Select::make('sistema')
                    ->label('Sistema')
                    ->options([
                        'antigo' => 'Antigo',
                        'novo' => 'Novo',
                    ])
                    ->required(),
                Select::make('ramo_id')
                    ->label('Ramo')
                    ->options(fn () => Ramo::pluck('nome', 'id'))
                    ->required(),
                FileUpload::make('arquivo')
                    ->label('Planilha (.xlsx)')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->storeFiles(false)
                    ->required(),
            ]);
    }

    public function importar(): void
    {
        $data = $this->form->getState();

        $ramo = Ramo::findOrFail($data['ramo_id']);

        /** @var TemporaryUploadedFile $arquivo */
        $arquivo = $data['arquivo'];

        $rawImport = new RawSheetImport;

        try {
            Excel::import($rawImport, $arquivo->getRealPath());
        } catch (Throwable $e) {
            Log::error('Falha ao ler planilha de importação', ['erro' => $e->getMessage()]);

            Notification::make()
                ->title('Não foi possível ler o arquivo. Confirme que é um .xlsx válido.')
                ->danger()
                ->send();

            return;
        }

        $servico = $data['sistema'] === 'antigo'
            ? new ImportadorAntigoService
            : new ImportadorNovoService;

        $resumo = $servico->importar($rawImport->linhas ?? collect(), $ramo);
        $this->resumo = $resumo->toArray();

        Notification::make()
            ->title('Importação concluída')
            ->body("{$resumo->itensCriados} itens criados, {$resumo->itensIgnorados} ignorados.")
            ->success()
            ->send();
    }
}
