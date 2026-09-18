<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * O Reconhecimento (Lis de Ouro) do Escoteiro tinha 3 requisitos
 * complementares separados (`escoteiro_novo_desafio_pessoal_travessia`,
 * `escoteiro_novo_autoavaliacao`, `escoteiro_novo_avaliacao_corte_honra_escotistas`),
 * diferente dos outros 3 ramos que têm só 2 (Desafio pessoal + Avaliação
 * dos pares e autoavaliação) — confirmado com o usuário que era pra ser
 * igual aos outros. Esta migration junta os dois últimos numa chave só
 * (`escoteiro_novo_avaliacao_pares`, mesmo padrão de nome usado nos outros
 * ramos), mantendo `escoteiro_novo_desafio_pessoal_travessia` como está.
 *
 * Uma linha só é considerada satisfeita depois da fusão se AMBAS as
 * originais já estavam marcadas como concluídas — evitar marcar como feito
 * algo que só tinha metade do requisito cumprido.
 */
return new class extends Migration
{
    private const CHAVE_AUTOAVALIACAO = 'escoteiro_novo_autoavaliacao';

    private const CHAVE_CORTE_HONRA = 'escoteiro_novo_avaliacao_corte_honra_escotistas';

    private const CHAVE_CONSOLIDADA = 'escoteiro_novo_avaliacao_pares';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $autoavaliacoes = DB::table('jovem_requisitos_complementares')
            ->where('chave', self::CHAVE_AUTOAVALIACAO)
            ->get()
            ->keyBy('jovem_id');

        $corteHonra = DB::table('jovem_requisitos_complementares')
            ->where('chave', self::CHAVE_CORTE_HONRA)
            ->get()
            ->keyBy('jovem_id');

        $jovemIds = $autoavaliacoes->keys()->merge($corteHonra->keys())->unique();

        foreach ($jovemIds as $jovemId) {
            $ambosVerdadeiros = (bool) ($autoavaliacoes->get($jovemId)->valor_booleano ?? false)
                && (bool) ($corteHonra->get($jovemId)->valor_booleano ?? false);

            DB::table('jovem_requisitos_complementares')->updateOrInsert(
                ['jovem_id' => $jovemId, 'chave' => self::CHAVE_CONSOLIDADA],
                [
                    'tipo' => 'booleano',
                    'valor_booleano' => $ambosVerdadeiros,
                    'valor_numero' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        DB::table('jovem_requisitos_complementares')
            ->whereIn('chave', [self::CHAVE_AUTOAVALIACAO, self::CHAVE_CORTE_HONRA])
            ->delete();
    }

    /**
     * Reverse the migrations. Reversão é "com perdas" de propósito (não dá
     * pra recuperar os 2 valores originais a partir de 1 só) — recria as
     * duas chaves antigas com o mesmo valor da consolidada.
     */
    public function down(): void
    {
        $consolidadas = DB::table('jovem_requisitos_complementares')
            ->where('chave', self::CHAVE_CONSOLIDADA)
            ->get();

        foreach ($consolidadas as $linha) {
            foreach ([self::CHAVE_AUTOAVALIACAO, self::CHAVE_CORTE_HONRA] as $chaveAntiga) {
                DB::table('jovem_requisitos_complementares')->updateOrInsert(
                    ['jovem_id' => $linha->jovem_id, 'chave' => $chaveAntiga],
                    [
                        'tipo' => 'booleano',
                        'valor_booleano' => $linha->valor_booleano,
                        'valor_numero' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        DB::table('jovem_requisitos_complementares')
            ->where('chave', self::CHAVE_CONSOLIDADA)
            ->delete();
    }
};
