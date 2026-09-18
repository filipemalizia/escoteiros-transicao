<?php

use App\Models\EspecialidadeDistintivo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('usa a imagem de nivel 1 quando nenhum nivel foi atingido', function () {
    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade', 'estrutura' => 'itens_niveis']);
    $especialidade->addMedia(pixelPngFile())->toMediaCollection('imagem_nivel_1');

    expect($especialidade->urlImagemParaNivel(0))->not->toBeNull();
});

it('usa a imagem de nivel 1 quando o nivel atingido e 1', function () {
    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade', 'estrutura' => 'itens_niveis']);
    $especialidade->addMedia(pixelPngFile())->toMediaCollection('imagem_nivel_1');
    $especialidade->addMedia(pixelPngFile())->toMediaCollection('imagem_nivel_2');

    expect($especialidade->urlImagemParaNivel(1))->toBe($especialidade->getFirstMediaUrl('imagem_nivel_1'));
});

it('usa a imagem de nivel 2 quando o nivel atingido e 2 e ela existe', function () {
    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade', 'estrutura' => 'itens_niveis']);
    $especialidade->addMedia(pixelPngFile())->toMediaCollection('imagem_nivel_1');
    $especialidade->addMedia(pixelPngFile())->toMediaCollection('imagem_nivel_2');

    expect($especialidade->urlImagemParaNivel(2))->toBe($especialidade->getFirstMediaUrl('imagem_nivel_2'));
});

it('cai pra imagem de nivel 1 no nivel 2 quando nao existe imagem de nivel 2 (caso Insignia/atividades_temas)', function () {
    $insignia = EspecialidadeDistintivo::create(['nome' => 'Mensageiros da Paz', 'tipo' => 'Insígnia', 'estrutura' => 'atividades_temas']);
    $insignia->addMedia(pixelPngFile())->toMediaCollection('imagem_nivel_1');

    expect($insignia->urlImagemParaNivel(1))->toBe($insignia->getFirstMediaUrl('imagem_nivel_1'));
});

it('retorna null quando nao ha nenhuma imagem', function () {
    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade', 'estrutura' => 'itens_niveis']);

    expect($especialidade->urlImagemParaNivel(0))->toBeNull();
});

it('componente imagem-badge mostra a imagem colorida ou apagada conforme a prop', function () {
    $html = (string) $this->blade(
        '<x-progresso.imagem-badge url="https://exemplo.test/imagem.png" :colorida="$colorida" alt="Acampamento" />',
        ['colorida' => true]
    );

    expect($html)->toContain('imagem.png')->not->toContain('grayscale');

    $htmlApagada = (string) $this->blade(
        '<x-progresso.imagem-badge url="https://exemplo.test/imagem.png" :colorida="$colorida" alt="Acampamento" />',
        ['colorida' => false]
    );

    expect($htmlApagada)->toContain('grayscale');
});

it('componente imagem-badge mostra um icone generico quando nao ha url', function () {
    $html = (string) $this->blade('<x-progresso.imagem-badge :url="null" alt="Sem imagem" />');

    expect($html)->toContain('<svg')->not->toContain('<img');
});
