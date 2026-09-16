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

];
