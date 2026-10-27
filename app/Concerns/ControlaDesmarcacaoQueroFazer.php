<?php

namespace App\Concerns;

/**
 * Regra de desmarcar o "quero fazer" no Ramo Pioneiro: nas primeiras
 * {@see HORAS_JANELA_DESMARCAR} horas o pioneiro desmarca livremente (pode
 * ter marcado sem querer ao revisar o Projeto de Vida); depois disso ele só
 * pede pra desmarcar e o chefe decide ({@see $desmarcar_solicitado_em}).
 * Nos outros ramos a marcação continua livre o tempo todo.
 *
 * Usado por `ProgressoNovo` e `ProgressoPersonalizado`.
 */
trait ControlaDesmarcacaoQueroFazer
{
    public const HORAS_JANELA_DESMARCAR = 24;

    public static function bootControlaDesmarcacaoQueroFazer(): void
    {
        // Pedido de desmarcar só faz sentido enquanto o item segue marcado.
        static::saving(function ($progresso) {
            if (! $progresso->marcado_para_fazer) {
                $progresso->desmarcar_solicitado_em = null;
            }
        });
    }

    public function initializeControlaDesmarcacaoQueroFazer(): void
    {
        $this->casts['desmarcar_solicitado_em'] = 'datetime';
        $this->fillable[] = 'desmarcar_solicitado_em';
    }

    public function dentroDaJanelaParaDesmarcar(): bool
    {
        return $this->marcado_para_fazer_em === null
            || $this->marcado_para_fazer_em->gt(now()->subHours(self::HORAS_JANELA_DESMARCAR));
    }

    public function desmarcarFoiSolicitado(): bool
    {
        return $this->marcado_para_fazer && $this->desmarcar_solicitado_em !== null;
    }

    /**
     * O pioneiro não pode mais desmarcar sozinho: precisa pedir ao chefe.
     */
    public function exigeConfirmacaoParaDesmarcar(): bool
    {
        return $this->marcado_para_fazer
            && ! $this->concluido
            && ! $this->dentroDaJanelaParaDesmarcar()
            && (bool) $this->jovem?->usaProjetoDeVida();
    }

    public function diasMarcadoParaFazer(): ?int
    {
        return $this->marcado_para_fazer && $this->marcado_para_fazer_em
            ? (int) $this->marcado_para_fazer_em->startOfDay()->diffInDays(now()->startOfDay())
            : null;
    }
}
