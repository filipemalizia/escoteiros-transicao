@props(['marcado' => false])

@if ($marcado)
    <x-filament::icon
        icon="heroicon-s-star"
        title="Jovem marcou como 'quero fazer'"
        {{ $attributes->class(['h-4 w-4 shrink-0 text-amber-500']) }}
    />
@endif
