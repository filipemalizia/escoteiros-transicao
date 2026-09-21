@props(['modalidade'])

@if ($modalidade === 'Mar')
    <span title="Modalidade Mar" class="inline-flex shrink-0 items-center text-blue-500 dark:text-blue-400">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
            <circle cx="12" cy="5.5" r="2" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5v13" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 11h8" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 14c0 3.5 3 6.5 7 6.5s7-3 7-6.5" />
        </svg>
        <span class="sr-only">Modalidade Mar</span>
    </span>
@endif
