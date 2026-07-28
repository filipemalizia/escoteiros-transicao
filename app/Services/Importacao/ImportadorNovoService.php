<?php

namespace App\Services\Importacao;

use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\EspecialidadeDistintivo;
use App\Models\ItemNovo;
use App\Models\Ramo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportadorNovoService
{
    use LeitorPlanilha;

    protected const COLUNAS = [
        'eixo' => 'Eixo',
        'bloco' => 'Bloco',
        'intencionalidade' => 'Intencionalidade Educativa',
        'codigo' => 'Código',
        'tipo_acao' => 'Tipo de Ação',
        'acao' => 'Ação',
        'modalidade' => 'Modalid.',
        'requisitos' => 'Requisitos/Realizar',
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
        $acao = $this->valor($linha, $cabecalho, 'acao');

        if (blank($codigo) || blank($acao)) {
            $resumo->registrarErro($numeroLinha, 'Código ou Ação em branco.');

            return;
        }

        $eixoNome = $this->valor($linha, $cabecalho, 'eixo');

        if (blank($eixoNome)) {
            $resumo->registrarErro($numeroLinha, 'Eixo em branco.');

            return;
        }

        $blocoTitulo = $this->valor($linha, $cabecalho, 'bloco');

        if (blank($blocoTitulo)) {
            $resumo->registrarErro($numeroLinha, 'Bloco em branco.');

            return;
        }

        $tipoAcao = $this->normalizarTipoAcao($this->valor($linha, $cabecalho, 'tipo_acao'));

        if ($tipoAcao === null) {
            $resumo->registrarErro($numeroLinha, 'Tipo de Ação não reconhecido.');

            return;
        }

        $intencionalidade = $this->valor($linha, $cabecalho, 'intencionalidade');
        $modalidade = $this->valor($linha, $cabecalho, 'modalidade') ?: 'Geral';
        $observacao = $this->valor($linha, $cabecalho, 'requisitos');
        $quantidadeMinima = $this->extrairQuantidadeMinima($tipoAcao, $observacao);
        $especialidadeInfo = $this->extrairEspecialidade($tipoAcao, $acao);

        try {
            DB::transaction(function () use (
                $eixoNome, $blocoTitulo, $intencionalidade, $codigo, $acao, $tipoAcao,
                $modalidade, $observacao, $quantidadeMinima, $especialidadeInfo, $ramo, $resumo, $numeroLinha
            ) {
                $eixo = EixoNovo::firstOrCreate([
                    'ramo_id' => $ramo->id,
                    'nome' => $eixoNome,
                ]);

                if ($eixo->wasRecentlyCreated) {
                    $resumo->gruposCriados++;
                }

                $bloco = BlocoNovo::firstOrCreate([
                    'eixo_id' => $eixo->id,
                    'titulo' => $blocoTitulo,
                ], [
                    'descricao' => $intencionalidade,
                ]);

                if ($bloco->wasRecentlyCreated) {
                    $resumo->subgruposCriados++;
                }

                if ($quantidadeMinima !== null) {
                    if ($bloco->quantidade_minima_variaveis === null) {
                        $bloco->update(['quantidade_minima_variaveis' => $quantidadeMinima]);
                    } elseif ($bloco->quantidade_minima_variaveis !== $quantidadeMinima) {
                        $resumo->registrarAviso(
                            $numeroLinha,
                            "Quantidade mínima de variáveis diferente da já registrada para o bloco '{$bloco->titulo}' ({$bloco->quantidade_minima_variaveis}); valor {$quantidadeMinima} ignorado."
                        );
                    }
                }

                if (ItemNovo::where('codigo', $codigo)->exists()) {
                    throw new ImportLinhaException('Código duplicado, linha ignorada.');
                }

                $especialidadeId = null;

                if ($especialidadeInfo !== null) {
                    $especialidade = EspecialidadeDistintivo::firstOrCreate([
                        'nome' => $especialidadeInfo['nome'],
                    ], [
                        'tipo' => $especialidadeInfo['tipo'],
                    ]);

                    $especialidadeId = $especialidade->id;
                }

                ItemNovo::create([
                    'bloco_id' => $bloco->id,
                    'codigo' => $codigo,
                    'descricao' => $acao,
                    'tipo_acao' => $tipoAcao,
                    'modalidade' => $modalidade,
                    'especialidade_id' => $especialidadeId,
                    'observacao' => $observacao,
                ]);

                $resumo->itensCriados++;
            });
        } catch (ImportLinhaException $e) {
            $resumo->registrarErro($numeroLinha, $e->getMessage());
        }
    }

    protected function normalizarTipoAcao(?string $valor): ?string
    {
        if (blank($valor)) {
            return null;
        }

        $normalizado = Str::of($valor)->trim()->lower()->ascii()->toString();

        return match (true) {
            in_array($normalizado, ['obrigatoria', 'fixa']) => 'Obrigatória',
            $normalizado === 'variavel' => 'Variável',
            in_array($normalizado, ['substitutiva', 'substitui variavel']) => 'Substitutiva',
            default => null,
        };
    }

    protected function extrairQuantidadeMinima(string $tipoAcao, ?string $requisitos): ?int
    {
        if ($tipoAcao !== 'Variável' || blank($requisitos)) {
            return null;
        }

        if (preg_match('/Realizar\s*(\d+)/i', $requisitos, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * @return array{tipo: string, nome: string}|null
     */
    protected function extrairEspecialidade(string $tipoAcao, string $acao): ?array
    {
        if ($tipoAcao !== 'Substitutiva') {
            return null;
        }

        if (! preg_match('/(Especialidade|Ins[ií]gnia)\s*:\s*(.+)/iu', $acao, $matches)) {
            return null;
        }

        $tipo = Str::of($matches[1])->lower()->ascii()->toString() === 'especialidade'
            ? 'Especialidade'
            : 'Insígnia';

        return [
            'tipo' => $tipo,
            'nome' => trim($matches[2]),
        ];
    }
}
