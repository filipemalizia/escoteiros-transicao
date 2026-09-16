<?php

use App\Filament\Resources\Equipes\EquipeResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('impede que um usuario comum acesse a listagem de usuarios', function () {
    $lider = User::factory()->create();
    $this->actingAs($lider);

    expect(UserResource::canViewAny())->toBeFalse();

    $this->get(UserResource::getUrl('index'))->assertForbidden();
});

it('permite que um admin acesse a listagem de usuarios', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    expect(UserResource::canViewAny())->toBeTrue();

    $this->get(UserResource::getUrl('index'))->assertOk();
});

it('impede que um usuario comum acesse a listagem de equipes', function () {
    $lider = User::factory()->create();
    $this->actingAs($lider);

    expect(EquipeResource::canViewAny())->toBeFalse();

    $this->get(EquipeResource::getUrl('index'))->assertForbidden();
});

it('permite que um admin acesse a listagem de equipes', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    expect(EquipeResource::canViewAny())->toBeTrue();

    $this->get(EquipeResource::getUrl('index'))->assertOk();
});
