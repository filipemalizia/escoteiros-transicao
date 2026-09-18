<?php

namespace App\Services\Importacao;

use App\Models\EixoNovo;
use App\Models\EspecialidadeDistintivo;
use App\Models\Ramo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Importa o catálogo de Especialidades extraído pelo paxtu-scraper
 * (data/especialidades.json) pro schema de EspecialidadeDistintivo.
 *
 * Idempotente: pode ser rodado de novo sempre que o scraper gerar um JSON
 * atualizado, sem duplicar registros (upsert por fonte_specialty_id).
 */
class ImportadorEspecialidadesService
{
    // Modelo pedagógico do PAXTU -> ramos que compartilham esse catálogo.
    private const RAMOS_POR_MODELO = [
        'lobinho_escoteiro' => ['Lobinho', 'Escoteiro'],
        'senior_pioneiro' => ['Sênior', 'Pioneiro'],
    ];

    /**
     * @param  array{especialidades?: array<int, array<string, mixed>>}  $catalogo
     * @param  bool  $dryRun  Roda tudo dentro de uma transação e desfaz no
     *                        final — nada é gravado (nem imagens são
     *                        baixadas). Use pra conferir o que aconteceria
     *                        antes de rodar de verdade em produção.
     */
    public function importar(array $catalogo, bool $dryRun = false): EspecialidadeImportResumo
    {
        $resumo = new EspecialidadeImportResumo;

        DB::beginTransaction();

        foreach ($catalogo['especialidades'] ?? [] as $dados) {
            try {
                $this->importarEspecialidade($dados, $resumo, $dryRun);
            } catch (Throwable $e) {
                $resumo->registrarErro($dados['id'] ?? '?', $dados['nome'] ?? '?', $e->getMessage());
            }
        }

        $dryRun ? DB::rollBack() : DB::commit();

        return $resumo;
    }

    /**
     * Cada especialidade é importada dentro da própria transação (savepoint,
     * já que `importar()` já abriu uma transação externa): se qualquer etapa
     * falhar (ex.: ramo não encontrado), a especialidade inteira é revertida
     * — nunca fica meio-importada — e as outras especialidades do catálogo
     * continuam sendo processadas normalmente.
     */
    private function importarEspecialidade(array $dados, EspecialidadeImportResumo $resumo, bool $dryRun): void
    {
        DB::transaction(function () use ($dados, $resumo, $dryRun) {
            $especialidade = EspecialidadeDistintivo::where('fonte_specialty_id', $dados['id'])->first();
            $adotada = false;

            if (! $especialidade) {
                // O importador de planilha antigo cria EspecialidadeDistintivo
                // casando só por nome+tipo, sem fonte_specialty_id. Adota esse
                // registro em vez de criar uma linha duplicada — preserva os
                // ItemNovo que já apontam pra ele.
                $especialidade = EspecialidadeDistintivo::whereNull('fonte_specialty_id')
                    ->where('nome', $dados['nome'])
                    ->where('tipo', 'Especialidade')
                    ->first();

                $adotada = (bool) $especialidade;
            }

            $existia = (bool) $especialidade;
            $especialidade ??= new EspecialidadeDistintivo;

            $especialidade->fill([
                'nome' => $dados['nome'],
                'tipo' => 'Especialidade',
                'descricao' => $dados['descricao'] ?? null,
                'estrutura' => isset($dados['itens']) ? 'itens_niveis' : 'atividades_temas',
                'regra_niveis' => $dados['regra_niveis'] ?? null,
                'minimo_nivel_1' => $dados['minimo_nivel_1'] ?? null,
                'minimo_nivel_2' => $dados['minimo_nivel_2'] ?? null,
                'sugestao_temas' => $dados['sugestao_temas'] ?? null,
                'fonte_specialty_id' => $dados['id'],
            ])->save();

            $this->sincronizarEixos($especialidade, $dados, $resumo);
            $this->sincronizarGrupos($especialidade, $dados);
            $this->sincronizarImagem($especialidade, 'imagem_nivel_1', $dados['imagem_nivel_1'] ?? null, $resumo, $dryRun);
            $this->sincronizarImagem($especialidade, 'imagem_nivel_2', $dados['imagem_nivel_2'] ?? null, $resumo, $dryRun);

            // Só conta como sucesso se chegou até aqui sem lançar exceção.
            match (true) {
                $adotada => $resumo->adotadas++,
                $existia => $resumo->atualizadas++,
                default => $resumo->criadas++,
            };
        });
    }

    private function sincronizarEixos(EspecialidadeDistintivo $especialidade, array $dados, EspecialidadeImportResumo $resumo): void
    {
        $ramosNomes = self::RAMOS_POR_MODELO[$dados['modelo']] ?? null;

        if (! $ramosNomes) {
            throw new RuntimeException("Modelo desconhecido: {$dados['modelo']}");
        }

        $eixoIds = [];

        foreach ($ramosNomes as $ramoNome) {
            $ramo = Ramo::where('nome', $ramoNome)->first();

            if (! $ramo) {
                throw new RuntimeException("Ramo \"{$ramoNome}\" não encontrado — rode o RamosSeeder antes de importar.");
            }

            foreach ($dados['eixos'] ?? [] as $eixoDados) {
                $eixo = EixoNovo::firstOrCreate([
                    'ramo_id' => $ramo->id,
                    'nome' => $eixoDados['nome'],
                ]);

                if ($eixo->wasRecentlyCreated) {
                    $resumo->eixosNovosCriados++;
                }

                $eixoIds[] = $eixo->id;
            }
        }

        $especialidade->eixosNovos()->sync($eixoIds);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function sincronizarGrupos(EspecialidadeDistintivo $especialidade, array $dados): void
    {
        $grupos = isset($dados['itens'])
            ? ['itens' => $dados['itens']]
            : [
                'conhecer' => $dados['atividades']['conhecer'] ?? [],
                'fazer' => $dados['atividades']['fazer'] ?? [],
                'compartilhar' => $dados['atividades']['compartilhar'] ?? [],
            ];

        // Remove grupos que não fazem mais sentido pra essa especialidade
        // (ex.: reimport que mudou de estrutura).
        $especialidade->grupos()->whereNotIn('chave', array_keys($grupos))->delete();

        $ordem = 0;

        foreach ($grupos as $chave => $itensDados) {
            $ordem++;

            $grupo = $especialidade->grupos()->updateOrCreate(
                ['chave' => $chave],
                ['ordem' => $ordem],
            );

            // Sem dado de progresso individual vinculado ainda nesta fase —
            // apagar e recriar é seguro e mais simples que fazer diff.
            $grupo->itens()->delete();

            foreach ($itensDados as $indice => $itemDados) {
                $grupo->itens()->create([
                    'fonte_item_id' => $itemDados['id'] ?? null,
                    'texto' => $itemDados['texto'],
                    'ordem' => $indice + 1,
                ]);
            }
        }
    }

    private function sincronizarImagem(
        EspecialidadeDistintivo $especialidade,
        string $collection,
        ?string $url,
        EspecialidadeImportResumo $resumo,
        bool $dryRun,
    ): void {
        if (! $url) {
            if (! $dryRun) {
                $especialidade->clearMediaCollection($collection);
            }

            return;
        }

        $mediaAtual = $especialidade->getFirstMedia($collection);

        if ($mediaAtual && $mediaAtual->getCustomProperty('fonte_url') === $url) {
            $resumo->imagensPuladas++;

            return;
        }

        if ($dryRun) {
            // Não baixa de verdade: o arquivo salvo em disco não seria
            // desfeito pelo rollback da transação, só a linha na tabela `media`.
            $resumo->imagensSimuladas++;

            return;
        }

        try {
            $especialidade->addMediaFromUrl($url)
                ->withCustomProperties(['fonte_url' => $url])
                ->toMediaCollection($collection);

            $resumo->imagensBaixadas++;
        } catch (Throwable $e) {
            // Falha em baixar uma imagem não deve derrubar a importação da
            // especialidade inteira — só registra e segue.
            Log::warning("Falha ao baixar imagem [{$collection}] de {$url}: {$e->getMessage()}");
            $resumo->imagensComErro++;
        }
    }
}
