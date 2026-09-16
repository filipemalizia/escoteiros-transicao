<?php

namespace App\Filament\Pages;

use App\Models\BlocoNovo;
use App\Models\ItemPersonalizado;
use App\Models\Jovem;
use App\Models\Ramo;
use App\Services\StatusProgressaoService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Tela central pra criar um Item Personalizado já direto pra vários jovens
 * de um Ramo/Bloco de uma vez, em vez de precisar abrir a ficha de cada
 * jovem — que continua existindo (`VerProgresso::criarItemPersonalizado()`)
 * pra quem só quer criar pro jovem que já está vendo.
 */
class CriarItemPersonalizado extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?int $navigationSort = -25;

    protected static ?string $navigationLabel = 'Item Personalizado';

    protected static ?string $title = 'Novo Item Personalizado';

    protected string $view = 'filament.pages.criar-item-personalizado';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Select::make('ramo_id')
                    ->label('Ramo')
                    ->options(fn () => Ramo::pluck('nome', 'id'))
                    ->live()
                    ->required()
                    ->afterStateUpdated(function (callable $set) {
                        $set('bloco_novo_id', null);
                        $set('jovens_ids', []);
                    })
                    ->helperText('O item fica ligado a um Bloco desse ramo - se o jovem trocar de ramo depois, ele deixa de aparecer na tela de progresso automaticamente.'),

                Select::make('bloco_novo_id')
                    ->label('Bloco')
                    ->options(fn (Get $get) => static::opcoesBlocos($get('ramo_id')))
                    ->live()
                    ->required()
                    ->disabled(fn (Get $get) => blank($get('ramo_id')))
                    ->searchable(),

                Textarea::make('descricao')
                    ->label('Descrição')
                    ->required()
                    ->columnSpanFull(),

                Select::make('jovens_ids')
                    ->label('Jovens')
                    ->multiple()
                    ->options(fn (Get $get) => static::opcoesJovens($get('ramo_id')))
                    ->disabled(fn (Get $get) => blank($get('ramo_id')))
                    ->required()
                    ->searchable()
                    ->helperText('Só aparecem jovens do ramo escolhido que você tem acesso.'),
            ]);
    }

    /**
     * @return array<int, string>
     */
    protected static function opcoesBlocos(?int $ramoId): array
    {
        if (blank($ramoId)) {
            return [];
        }

        return BlocoNovo::query()
            ->whereHas('eixo', fn ($query) => $query->where('ramo_id', $ramoId))
            ->with('eixo')
            ->get()
            ->sortBy([['eixo.nome', 'asc'], ['titulo', 'asc']])
            ->mapWithKeys(fn (BlocoNovo $bloco) => [$bloco->id => "{$bloco->eixo->nome} - {$bloco->titulo}"])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected static function opcoesJovens(?int $ramoId): array
    {
        if (blank($ramoId)) {
            return [];
        }

        return Jovem::query()
            ->where('ramo_atual_id', $ramoId)
            ->when(
                ! auth()->user()?->isAdmin(),
                fn ($query) => $query->whereIn('equipe_id', auth()->user()?->equipes()->pluck('equipes.id') ?? [])
            )
            ->orderBy('nome')
            ->pluck('nome', 'id')
            ->all();
    }

    public function criar(): void
    {
        // `$this->form->getState()` já revalida cada `jovens_ids` contra o
        // `options()` atual (recalculado no servidor a partir do usuário
        // logado) e rejeita a submissão inteira se algum ID não fizer mais
        // parte da lista permitida — não precisa de filtragem manual aqui.
        $data = $this->form->getState();

        $item = ItemPersonalizado::create([
            'bloco_novo_id' => $data['bloco_novo_id'],
            'descricao' => $data['descricao'],
            'criado_por_id' => auth()->id(),
        ]);

        $item->jovens()->attach($data['jovens_ids']);

        app(StatusProgressaoService::class)->limparCache();

        Notification::make()
            ->title('Item personalizado criado para '.count($data['jovens_ids']).' jovem(ns)')
            ->success()
            ->send();

        $this->form->fill();
    }
}
