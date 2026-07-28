<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
});

it('cria um usuario com a senha ja hasheada', function () {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Novo Dirigente',
            'email' => 'dirigente@example.com',
            'password' => 'senha-segura-123',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $usuario = User::where('email', 'dirigente@example.com')->first();

    expect($usuario)->not->toBeNull()
        ->and(Hash::check('senha-segura-123', $usuario->password))->toBeTrue();
});

it('mantem a senha atual ao editar o usuario sem preencher uma nova senha', function () {
    $usuario = User::factory()->create();
    $hashOriginal = $usuario->password;

    Livewire::test(EditUser::class, ['record' => $usuario->getKey()])
        ->fillForm(['name' => 'Nome Atualizado'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($usuario->fresh()->name)->toBe('Nome Atualizado')
        ->and($usuario->fresh()->password)->toBe($hashOriginal);
});

it('atualiza a senha quando uma nova e preenchida na edicao', function () {
    $usuario = User::factory()->create();

    Livewire::test(EditUser::class, ['record' => $usuario->getKey()])
        ->fillForm(['password' => 'outra-senha-456'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Hash::check('outra-senha-456', $usuario->fresh()->password))->toBeTrue();
});

it('esconde a acao de excluir quando o usuario esta editando a propria conta', function () {
    Livewire::test(EditUser::class, ['record' => $this->admin->getKey()])
        ->assertActionHidden('delete');
});

it('mostra a acao de excluir ao editar a conta de outro usuario', function () {
    $outroUsuario = User::factory()->create();

    Livewire::test(EditUser::class, ['record' => $outroUsuario->getKey()])
        ->assertActionVisible('delete');
});
