<?php

namespace App\Services;

use App\Models\Jovem;
use App\Models\ProgressoAcolhida;
use App\Models\Ramo;
use Illuminate\Support\Collection;

/**
 * Período de Acolhida: o que o jovem novo precisa cumprir antes da Promessa
 * Escoteira. Não faz parte das etapas de progressão e não bloqueia a marcação
 * de nada: só o distintivo físico espera a Promessa (ver
 * {@see Jovem::promessaRealizada()}).
 */
class AcolhidaService
{
    /**
     * Itens de cada ramo, na ordem em que aparecem. A posição na lista
     * (começando em 1) identifica o item no banco, então novos itens entram
     * só no fim e nenhum deve ser removido ou reordenado.
     *
     * @var array<string, array<int, string>>
     */
    public const ITENS_POR_RAMO = [
        'Lobinho' => [
            'Participar ativamente das atividades da sua alcateia, fazer parte de uma matilha e utilizar o seu distintivo, conhecer também as outras matilhas da alcateia e os Velhos Lobos.',
            'Utilizar o lenço escoteiro e saber o seu significado, sabendo o nome da sua Unidade Escoteira Local e de sua alcateia.',
            'Saber fazer as formações e atender às vozes de comando orientadas pelos Velhos Lobos.',
            'Ouvir a história "Os irmãos de Mowgli" do Livro da Jângal.',
            'Usar o Lema do Lobinho, a Saudação e o Aperto de Mão e já ter participado do Grande Uivo, sabendo o que significam.',
            'Saber como conquistar os distintivos do Ramo Lobinho e conhecer o uniforme ou vestuário escoteiro usado na sua alcateia.',
            'Conhecer a Lei do Lobinho e a Promessa, conversar com o Velho Lobo sobre a sua importância e sentir-se pronto para assumir esse compromisso.',
        ],
        'Escoteiro' => [
            'Participar ativamente das atividades com sua patrulha, com sua tropa e conhecer a sua estrutura.',
            'Utilizar o lenço escoteiro, saber o seu significado e conhecer a história do seu grupo escoteiro e do Escotismo.',
            'Reconhecer e responder aos apitos e sinais manuais próprios do ramo.',
            'Fazer parte de uma patrulha, participar do grito da patrulha, usar suas cores e símbolos, utilizar seu distintivo, saber quem é seu monitor e desempenhar um encargo na patrulha.',
            'Entender e usar o Lema do Escoteiro, o Sinal, a Saudação e o Aperto de Mão.',
            'Saber quais são e como conquistar os distintivos do Ramo Escoteiro e conhecer o uniforme ou vestuário escoteiro usado na sua tropa.',
            'Conhecer a Lei e Promessa Escoteira, sendo capaz de explicar para o seu monitor e escotista a sua importância e valores. Sentir-se pronto para assumir esse compromisso.',
        ],
        'Sênior' => [
            'Participar das atividades com sua patrulha e com a tropa, conhecendo suas estruturas e seu funcionamento.',
            'Utilizar o lenço escoteiro sabendo o seu significado e conhecer a história da sua UEL (Unidade Escoteira Local) e do Escotismo.',
            'Reconhecer e responder aos apitos e sinais manuais próprios do ramo.',
            'Fazer parte de uma patrulha, participar do seu grito, usar suas cores e símbolos, utilizar o distintivo de patrulha, saber quem é seu monitor e desempenhar um encargo.',
            'Entender e usar o lema do escoteiro, o sinal, a saudação e o aperto de mão.',
            'Saber quais são e como conquistar os distintivos do Ramo Sênior e conhecer o uniforme ou vestuário escoteiro usado na tropa.',
            'Conhecer a Lei e Promessa Escoteira, sendo capaz de explicar para o seu monitor e para um escotista a sua importância e valores. Sentir-se pronto para assumir esse compromisso.',
        ],
        'Pioneiro' => [
            'Participar das atividades do clã, conhecendo sua estrutura e funcionamento, bem como os principais aspectos da história do Ramo Pioneiro.',
            'Usar o lenço escoteiro, sabendo seu significado, e conhecer a história da sua Unidade Escoteira Local (UEL) e do Escotismo.',
            'Conhecer o Sistema de Progressão do Ramo Pioneiro e saber como desenvolvê-lo.',
            'Conhecer a Lei e a Promessa Escoteira, identificando-se com seus valores e expressando junto ao clã a relação desses valores com sua vida pessoal. Sentir-se pronto para assumir esse compromisso.',
        ],
    ];

    /**
     * @return array<int, string> ordem (a partir de 1) => texto
     */
    public function itensDoRamo(?string $ramoNome): array
    {
        $textos = self::ITENS_POR_RAMO[$ramoNome ?? ''] ?? [];

        return $textos === [] ? [] : array_combine(range(1, count($textos)), $textos);
    }

    public function textoDoItem(int $ramoId, int $ordem): ?string
    {
        return $this->itensDoRamo(Ramo::find($ramoId)?->nome)[$ordem] ?? null;
    }

    /**
     * Progresso do jovem no ramo atual, indexado pela ordem do item.
     *
     * @return Collection<int, ProgressoAcolhida>
     */
    public function progressoDoJovem(Jovem $jovem): Collection
    {
        return ProgressoAcolhida::query()
            ->where('jovem_id', $jovem->id)
            ->where('ramo_id', $jovem->ramo_atual_id)
            ->get()
            ->keyBy('ordem');
    }

    /**
     * @return array{total: int, concluidos: int, percentual: int, completo: bool}
     */
    public function resumo(Jovem $jovem): array
    {
        $total = count($this->itensDoRamo($jovem->ramoAtual?->nome));
        $concluidos = $this->progressoDoJovem($jovem)
            ->filter(fn (ProgressoAcolhida $progresso) => $progresso->concluido && $progresso->ordem <= $total)
            ->count();

        return [
            'total' => $total,
            'concluidos' => $concluidos,
            'percentual' => $total > 0 ? (int) round($concluidos / $total * 100) : 0,
            'completo' => $total > 0 && $concluidos >= $total,
        ];
    }

    /**
     * Itens que o jovem marcou como feitos e aguardam o adulto.
     */
    public function contagemAguardandoAvaliacao(Jovem $jovem): int
    {
        return ProgressoAcolhida::query()
            ->where('jovem_id', $jovem->id)
            ->where('ramo_id', $jovem->ramo_atual_id)
            ->where('solicitado_pelo_jovem', true)
            ->where('concluido', false)
            ->count();
    }
}
