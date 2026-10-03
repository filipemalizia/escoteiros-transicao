@props(['texto', 'busca' => ''])

{!! \App\Support\Destaque::html($texto, $busca) !!}
