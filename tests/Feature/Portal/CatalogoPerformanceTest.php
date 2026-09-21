<?php

use App\Livewire\Portal\Catalogo;
use App\Models\EixoNovo;
use App\Models\EspecialidadeDistintivo;
use App\Models\Jovem;
use App\Models\Ramo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Trava a mesma otimização de N+1 feita em StatusProgressaoService::
 * itemEspecialidadeConcluido() (bulk-load memoizado por jovem) — sem ela,
 * cada item de cada Especialidade/Insígnia da lista rodava um exists()
 * próprio, sem cache nenhum, a cada interação (abrir/fechar o modal,
 * digitar na busca). Não é pra ser um número exato e frágil, só um teto
 * generoso que estoura se o N+1 voltar.
 */
it('renderiza o catalogo com um numero razoavel de queries mesmo com varias especialidades e itens', function () {
    $ramo = Ramo::create(['nome' => 'Lobinho']);
    $jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'registro' => '123456',
        'data_nascimento' => '2015-05-20',
        'ramo_atual_id' => $ramo->id,
    ]);

    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Meio Ambiente']);

    for ($e = 1; $e <= 30; $e++) {
        $especialidade = EspecialidadeDistintivo::create([
            'nome' => "Especialidade {$e}",
            'tipo' => 'Especialidade',
            'estrutura' => 'itens_niveis',
        ]);
        $especialidade->eixosNovos()->attach($eixo->id);

        $grupo = $especialidade->grupos()->create(['chave' => 'itens']);

        for ($i = 1; $i <= 5; $i++) {
            $grupo->itens()->create(['texto' => "Item {$i} da especialidade {$e}"]);
        }
    }

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2015-05-20',
    ]);

    DB::enableQueryLog();

    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])->assertOk();

    expect(count(DB::getQueryLog()))->toBeLessThan(100);
});
