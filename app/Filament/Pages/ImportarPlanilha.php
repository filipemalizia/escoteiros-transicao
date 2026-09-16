<?php

namespace App\Filament\Pages;

use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\Ramo;
use App\Services\EtapaProgressaoService;
use App\Services\Importacao\ImportadorAntigoService;
use App\Services\Importacao\ImportadorNovoService;
use App\Services\Importacao\RawSheetImport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use UnitEnum;

class ImportarPlanilha extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|UnitEnum|null $navigationGroup = 'Ferramentas';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Importar Planilha';

    protected static ?string $title = 'Importar Planilha';

    protected string $view = 'filament.pages.importar-planilha';

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

            Action::make('baixarProgressaoAntiga')
                ->label('Baixar Programa Antigo do Ramo (.csv)')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->action(fn () => $this->baixarProgressaoCadastrada('antigo')),

            Action::make('baixarProgressaoNovo')
                ->label('Baixar Programa Novo do Ramo (.csv)')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->action(fn () => $this->baixarProgressaoCadastrada('novo')),
        ];
    }

    /**
     * Reverso do `importar()`: exporta os itens já cadastrados do Ramo
     * selecionado no formulário, no mesmo formato de colunas usado na
     * importação — dá pra editar num Excel/planilha e reimportar depois,
     * ou levar os dados de um ambiente pro outro sem depender do arquivo
     * original.
     */
    protected function baixarProgressaoCadastrada(string $sistema): ?StreamedResponse
    {
        $ramoId = $this->data['ramo_id'] ?? null;

        if (blank($ramoId)) {
            Notification::make()
                ->title('Selecione um Ramo antes de baixar.')
                ->warning()
                ->send();

            return null;
        }

        $ramo = Ramo::findOrFail($ramoId);

        return $sistema === 'antigo'
            ? $this->baixarProgressaoAntiga($ramo)
            : $this->baixarProgressaoNovo($ramo);
    }

    protected function baixarProgressaoAntiga(Ramo $ramo): StreamedResponse
    {
        $itens = ItemAntigo::query()
            ->whereHas('competencia.areaDesenvolvimento', fn ($query) => $query->where('ramo_id', $ramo->id))
            ->with('competencia.areaDesenvolvimento')
            ->orderBy('codigo')
            ->get();

        return response()->streamDownload(function () use ($itens) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_values(ImportadorAntigoService::COLUNAS), ';');

            foreach ($itens as $item) {
                [$itemTexto, $observacao] = static::separarObservacao($item->descricao);

                fputcsv($handle, [
                    $item->competencia->areaDesenvolvimento->nome,
                    $item->competencia->descricao,
                    '',
                    $item->codigo,
                    $itemTexto,
                    $item->etapa,
                    $item->introdutorio ? 'Sim' : '',
                    $observacao,
                ], ';');
            }

            fclose($handle);
        }, "progressao-antiga-{$ramo->nome}.csv", ['Content-Type' => 'text/csv']);
    }

    protected function baixarProgressaoNovo(Ramo $ramo): StreamedResponse
    {
        $itens = ItemNovo::query()
            ->whereHas('bloco.eixo', fn ($query) => $query->where('ramo_id', $ramo->id))
            ->with(['bloco.eixo', 'especialidade'])
            ->orderBy('codigo')
            ->get();

        return response()->streamDownload(function () use ($itens) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_values(ImportadorNovoService::COLUNAS), ';');

            foreach ($itens as $item) {
                $acao = $item->especialidade
                    ? "{$item->especialidade->tipo}: {$item->especialidade->nome}"
                    : $item->descricao;

                fputcsv($handle, [
                    $item->bloco->eixo->nome,
                    $item->bloco->titulo,
                    $item->bloco->descricao,
                    $item->codigo,
                    $item->tipo_acao,
                    $acao,
                    $item->modalidade,
                    $item->observacao,
                ], ';');
            }

            fclose($handle);
        }, "progressao-novo-{$ramo->nome}.csv", ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected static function separarObservacao(string $descricao): array
    {
        if (Str::contains($descricao, "\n\nObservação: ")) {
            return explode("\n\nObservação: ", $descricao, 2);
        }

        return [$descricao, ''];
    }

    protected static function guiaColunas(?string $sistema, ?int $ramoId): ?HtmlString
    {
        if (blank($sistema) || blank($ramoId)) {
            return null;
        }

        $ramo = Ramo::find($ramoId);

        if (! $ramo) {
            return null;
        }

        if ($sistema === 'antigo') {
            $linhas = [
                'Área de Desenvolvimento - <strong>obrigatória</strong>.',
                'Competência / Descrição da Competência - pelo menos uma das duas é <strong>obrigatória</strong>.',
                'Código - <strong>obrigatória</strong> (precisa ser único em todo o sistema).',
                'Item - <strong>obrigatória</strong>.',
            ];

            $etapas = EtapaProgressaoService::etapasAntigoPorRamo($ramo->nome);

            $linhas[] = filled($etapas)
                ? 'Etapa - <strong>obrigatória</strong> para o ramo '.$ramo->nome.'. Valores aceitos: '.implode(', ', $etapas).'.'
                : 'Etapa - não se aplica ao ramo '.$ramo->nome.' (pode deixar em branco).';

            if ($ramo->nome === 'Lobinho') {
                $linhas[] = 'Introdutório - opcional. Marque "Sim" para os itens do Período Introdutório (obrigatórios pra 1ª etapa da piscina "Pata Tenra e Saltador").';
            }

            $linhas[] = 'Observação/Requisito - opcional.';
        } else {
            $linhas = [
                'Eixo - <strong>obrigatória</strong>.',
                'Bloco - <strong>obrigatória</strong>.',
                'Código - <strong>obrigatória</strong> (precisa ser único em todo o sistema).',
                'Tipo de Ação - <strong>obrigatória</strong>. Valores aceitos: Obrigatória, Variável, Substitutiva.',
                'Ação - <strong>obrigatória</strong>.',
                'Intencionalidade Educativa - opcional.',
                'Modalid. - opcional (padrão: Geral).',
                'Requisitos/Realizar - opcional.',
            ];
        }

        return new HtmlString('• '.implode('<br>• ', $linhas));
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
                    ->live()
                    ->required(),
                Select::make('ramo_id')
                    ->label('Ramo')
                    ->options(fn () => Ramo::pluck('nome', 'id'))
                    ->live()
                    ->required(),
                Callout::make('Colunas esperadas na planilha')
                    ->info()
                    ->description(fn (Get $get) => static::guiaColunas($get('sistema'), $get('ramo_id')))
                    ->visible(fn (Get $get) => filled($get('sistema')) && filled($get('ramo_id'))),
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

        $notification = Notification::make()
            ->body("{$resumo->itensCriados} itens criados, {$resumo->itensIgnorados} ignorados.");

        match (true) {
            $resumo->itensCriados === 0 && $resumo->itensIgnorados > 0 => $notification
                ->title('Nenhum item foi importado')
                ->danger(),
            $resumo->itensIgnorados > 0 => $notification
                ->title('Importação concluída com pendências - confira as linhas ignoradas abaixo')
                ->warning(),
            default => $notification
                ->title('Importação concluída')
                ->success(),
        };

        $notification->send();
    }
}
