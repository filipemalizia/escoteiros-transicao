<?php

namespace App\Filament\Pages;

use App\Models\Ramo;
use App\Services\Importacao\ImportadorAntigoService;
use App\Services\Importacao\ImportadorNovoService;
use App\Services\Importacao\RawSheetImport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
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

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('baixarModeloAntigo')
                ->label('Modelo Antigo (.csv)')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->action(fn () => $this->baixarModelo('modelo-importacao-antigo.csv', ImportadorAntigoService::COLUNAS)),

            Action::make('baixarModeloNovo')
                ->label('Modelo Novo (.csv)')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->action(fn () => $this->baixarModelo('modelo-importacao-novo.csv', ImportadorNovoService::COLUNAS)),
        ];
    }

    /**
     * @param  array<string, string>  $colunas
     */
    protected function baixarModelo(string $nomeArquivo, array $colunas): StreamedResponse
    {
        return response()->streamDownload(function () use ($colunas) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_values($colunas), ';');
            fclose($handle);
        }, $nomeArquivo, ['Content-Type' => 'text/csv']);
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
                    ->label('Planilha (.xlsx ou .csv)')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'text/csv',
                        'text/plain',
                        'application/csv',
                        'application/vnd.ms-excel',
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

        $extensao = Str::lower($arquivo->getClientOriginalExtension());
        $tipoLeitor = $extensao === 'csv' ? ExcelFormat::CSV : ExcelFormat::XLSX;

        $rawImport = new RawSheetImport;
        $rawImport->setCaminhoArquivo($arquivo->getRealPath());

        try {
            Excel::import($rawImport, $arquivo->getRealPath(), null, $tipoLeitor);
        } catch (Throwable $e) {
            Log::error('Falha ao ler planilha de importação', ['erro' => $e->getMessage()]);

            Notification::make()
                ->title('Não foi possível ler o arquivo. Confirme que é um .xlsx ou .csv válido.')
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
