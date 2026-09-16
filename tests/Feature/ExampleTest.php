<?php

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('a pagina inicial mostra os botoes de acesso adm e jovem', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Acesso ADM')
        ->assertSee('Acesso Jovem')
        ->assertSee(route('filament.admin.auth.login'), false)
        ->assertSee(route('portal.login.mostrar'), false);
});
