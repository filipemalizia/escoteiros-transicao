<?php

namespace App\Concerns\Portal;

use App\Livewire\Portal\Catalogo;
use App\Livewire\Portal\EixoDetalhe;
use App\Livewire\Portal\QueroFazer;
use App\Models\EspecialidadeDistintivoItem;
use App\Models\ItemPersonalizado;
use App\Models\ProgressoEspecialidade;
use App\Models\ProgressoNovo;
use App\Models\ProgressoPersonalizado;
use App\Services\Portal\SessaoJovemService;
use Illuminate\Database\Eloquent\Model;

/**
 * Marcação pessoal do jovem "quero fazer este item" — diferente de
 * "solicitar avaliação" ({@see AutenticaJovemNoPortal} não cobre isso, é só
 * autenticação): aqui o jovem só sinaliza pra si mesmo o que pretende fazer
 * em seguida, sem pedir confirmação de nenhum adulto. Por isso só é usada
 * pelos componentes do portal do jovem — nunca por `VerProgresso` (adulto),
 * que só exibe a marcação em modo leitura.
 */
trait MarcaQueroFazer
{
    /**
     * Mesma checagem de posse pro ramo do jovem que
     * {@see Catalogo::solicitarEspecialidade()} já faz.
     */
    public function toggleQueroFazerEspecialidade(int $especialidadeDistintivoItemId, SessaoJovemService $sessao): void
    {
        if (! $sessao->sessaoValida()) {
            $this->redirect(route('portal.login.mostrar'));

            return;
        }

        $disponivelParaORamo = EspecialidadeDistintivoItem::query()
            ->where('id', $especialidadeDistintivoItemId)
            ->whereHas(
                'grupo.especialidadeDistintivo',
                fn ($query) => $query->paraRamo($this->jovem()->ramo_atual_id),
            )
            ->exists();

        abort_unless($disponivelParaORamo, 403);

        $this->alternarMarcacaoQueroFazer(ProgressoEspecialidade::class, 'especialidade_distintivo_item_id', $especialidadeDistintivoItemId);
    }

    public function toggleQueroFazerNovo(int $itemNovoId, SessaoJovemService $sessao): void
    {
        if (! $sessao->sessaoValida()) {
            $this->redirect(route('portal.login.mostrar'));

            return;
        }

        $this->alternarMarcacaoQueroFazer(ProgressoNovo::class, 'item_novo_id', $itemNovoId);
    }

    /**
     * Mesma checagem de posse que
     * {@see EixoDetalhe::solicitarItemPersonalizado()} já faz.
     */
    public function toggleQueroFazerPersonalizado(int $itemPersonalizadoId, SessaoJovemService $sessao): void
    {
        if (! $sessao->sessaoValida()) {
            $this->redirect(route('portal.login.mostrar'));

            return;
        }

        $pertenceAoJovem = ItemPersonalizado::query()
            ->where('id', $itemPersonalizadoId)
            ->whereHas('jovens', fn ($query) => $query->where('jovens.id', $this->jovemId))
            ->exists();

        abort_unless($pertenceAoJovem, 403);

        $this->alternarMarcacaoQueroFazer(ProgressoPersonalizado::class, 'item_personalizado_id', $itemPersonalizadoId);
    }

    /**
     * Dispatcher usado pela lista agregada "Quero Fazer"
     * ({@see QueroFazer}), que mistura os 3 tipos.
     */
    public function toggleQueroFazer(string $tipo, int $itemId, SessaoJovemService $sessao): void
    {
        match ($tipo) {
            'novo' => $this->toggleQueroFazerNovo($itemId, $sessao),
            'personalizado' => $this->toggleQueroFazerPersonalizado($itemId, $sessao),
            'especialidade' => $this->toggleQueroFazerEspecialidade($itemId, $sessao),
            default => null,
        };
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function alternarMarcacaoQueroFazer(string $modelClass, string $coluna, int $itemId): void
    {
        $progresso = $modelClass::query()->firstOrNew([
            'jovem_id' => $this->jovemId,
            $coluna => $itemId,
        ]);

        if ($progresso->concluido) {
            return;
        }

        $progresso->marcado_para_fazer = ! $progresso->marcado_para_fazer;
        $progresso->marcado_para_fazer_em = $progresso->marcado_para_fazer ? now() : null;
        $progresso->save();
    }
}
