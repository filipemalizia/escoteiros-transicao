<x-layouts.portal title="Bem-vindo">
    <div class="flex min-h-[70vh] flex-col items-center justify-center text-center">
        <img src="{{ asset('images/logo.svg') }}" alt="GEMar Marcílio Dias" class="h-20 w-20">

        <h1 class="mt-4 text-2xl font-bold text-gray-950 dark:text-white">Ferramenta de Transição</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">GEMar Marcílio Dias - 02BA</p>

        <div class="mt-8 flex w-full max-w-xs flex-col gap-3">
            <a
                href="{{ route('filament.admin.auth.login') }}"
                class="w-full rounded-lg bg-[#2E3192] px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-[#252878]"
            >
                Acesso ADM
            </a>
            <a
                href="{{ route('portal.login.mostrar') }}"
                class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-white/5"
            >
                Acesso Jovem
            </a>
        </div>
    </div>
</x-layouts.portal>
