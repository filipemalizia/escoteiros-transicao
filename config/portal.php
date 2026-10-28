<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Duração da sessão do portal público do jovem
    |--------------------------------------------------------------------------
    |
    | Depois de validar registro + data de nascimento, o jovem fica "logado"
    | no portal por esse tempo (em minutos), sem precisar reenviar os dados
    | a cada clique.
    |
    */

    'sessao_minutos' => (int) env('PORTAL_SESSAO_MINUTOS', 240),

    /*
    |--------------------------------------------------------------------------
    | Bloqueio por tentativas erradas no login do portal
    |--------------------------------------------------------------------------
    |
    | Registro + data de nascimento é uma credencial fraca, e o limite por
    | minuto sozinho não impede uma tentativa lenta de adivinhar a data.
    | Depois de `login_max_falhas` erros para o mesmo registro dentro de
    | `login_janela_minutos`, esse registro fica bloqueado até a janela
    | passar (um acerto zera a contagem) ou um chefe liberar o acesso na tela
    | de progresso do jovem.
    |
    */

    'login_max_falhas' => (int) env('PORTAL_LOGIN_MAX_FALHAS', 5),

    'login_janela_minutos' => (int) env('PORTAL_LOGIN_JANELA_MINUTOS', 60),

];
