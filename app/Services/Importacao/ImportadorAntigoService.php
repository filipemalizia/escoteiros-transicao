<?php

namespace App\Services\Importacao;

use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\CompetenciaAntiga;
use App\Models\ItemAntigo;
use App\Models\Ramo;
use App\Services\EtapaProgressaoService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ImportadorAntigoService
{
    use LeitorPlanilha;

    public const COLUNAS = [
        'area' => 'Área de Desenvolvimento',
        'competencia' => 'Competência',
        'descricao_competencia' => 'Descrição da Competência',
        'codigo' => 'Código',
        'item' => 'Item',
        'etapa' => 'Etapa',
        'observacao' => 'Observação/Requisito',
    ];

    public function importar(Collection $linhas, Ramo $ramo): ImportResumo
    {
        $resumo = new ImportResumo;

        if ($linhas->isEmpty()) {
            return $resumo;
        }

        $cabecalho = $this->mapearCabecalho($linhas->first(), self::COLUNAS);
        $linhasDeDados = $linhas->slice(1);

        DB::transaction(function () use ($linhasDeDados, $cabecalho, $ramo, $resumo) {
            $numeroLinha = 1;

            foreach ($linhasDeDados as $linha) {
                $numeroLinha++;
                $this->processarLinha($linha, $cabecalho, $ramo, $resumo, $numeroLinha);
            }
        });

        return $resumo;
    }

    protected function processarLinha(Collection $linha, array $cabecalho, Ramo $ramo, ImportResumo $resumo, int $numeroLinha): void
    {
        $codigo = $this->valor($linha, $cabecalho, 'codigo');
        $item = $this->valor($linha, $cabecalho, 'item');

        if (blank($codigo) || blank($item)) {
            $resumo->registrarErro($numeroLinha, 'Código ou Item em branco.');

            return;
        }

        $areaNome = $this->valor($linha, $cabecalho, 'area');

        if (blank($areaNome)) {
            $resumo->registrarErro($numeroLinha, 'Área de Desenvolvimento em branco.');

            return;
        }

        $competencia = $this->valor($linha, $cabecalho, 'competencia');
        $descricaoCompetencia = $this->valor($linha, $cabecalho, 'descricao_competencia');

        if (blank($competencia) && blank($descricaoCompetencia)) {
            $resumo->registrarErro($numeroLinha, 'Competência e Descrição da Competência em branco (ao menos uma é obrigatória).');

            return;
        }

        $descricaoFinal = (filled($competencia) && filled($descricaoCompetencia))
            ? "{$competencia} — {$descricaoCompetencia}"
            : ($competencia ?: $descricaoCompetencia);

        $observacao = $this->valor($linha, $cabecalho, 'observacao');
        $descricaoItem = filled($observacao) ? "{$item}\n\nObservação: {$observacao}" : $item;

        $etapasValidas = EtapaProgressaoService::etapasAntigoPorRamo($ramo->nome);
        $etapa = $this->valor($linha, $cabecalho, 'etapa');

        if (filled($etapasValidas)) {
            if (blank($etapa)) {
                $resumo->registrarErro($numeroLinha, 'Etapa em branco (obrigatória para este ramo).');

                return;
            }

            if (! in_array($etapa, $etapasValidas, true)) {
                $resumo->registrarErro($numeroLinha, "Etapa '{$etapa}' não reconhecida para o ramo {$ramo->nome}.");

                return;
            }
        }

        try {
            DB::transaction(function () use ($areaNome, $descricaoFinal, $codigo, $descricaoItem, $etapa, $ramo, $resumo) {
                $area = AreaDesenvolvimentoAntiga::firstOrCreate([
                    'ramo_id' => $ramo->id,
                    'nome' => $areaNome,
                ]);

                if ($area->wasRecentlyCreated) {
                    $resumo->gruposCriados++;
                }

                $competencia = CompetenciaAntiga::firstOrCreate([
                    'area_desenvolvimento_id' => $area->id,
                    'descricao' => $descricaoFinal,
                ]);

                if ($competencia->wasRecentlyCreated) {
                    $resumo->subgruposCriados++;
                }

                if (ItemAntigo::where('codigo', $codigo)->exists()) {
                    throw new ImportLinhaException('Código duplicado, linha ignorada.');
                }

                ItemAntigo::create([
                    'competencia_id' => $competencia->id,
                    'codigo' => $codigo,
                    'descricao' => $descricaoItem,
                    'etapa' => $etapa,
                ]);

                $resumo->itensCriados++;
            });
        } catch (ImportLinhaException $e) {
            $resumo->registrarErro($numeroLinha, $e->getMessage());
        }
    }
}
