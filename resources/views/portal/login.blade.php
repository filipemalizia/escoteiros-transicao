<x-layouts.portal title="Entrar">
    <div class="mx-auto w-full max-w-md rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-col items-center text-center">
            <img src="{{ asset('images/logo.svg') }}" alt="GEMar Marcílio Dias" class="mb-3 h-31.25 w-31.25">

            @include('filament.components.brand-nome', ['align' => 'center', 'extraClass' => 'mb-3'])

            <h1 class="text-2xl font-bold text-gray-950 dark:text-white">Portal do Jovem</h1>

            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                Informe seu Registro Escoteiro e sua data de nascimento pra ver seus itens concluídos e pendentes.
            </p>
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-400/10 dark:text-red-400">
                @foreach ($errors->all() as $erro)
                    <p>{{ $erro }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('portal.login') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="registro" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Registro Escoteiro</label>
                <input
                    type="text"
                    inputmode="numeric"
                    id="registro"
                    name="registro"
                    value="{{ old('registro') }}"
                    required
                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-[#2E3192] focus:ring-[#2E3192] dark:border-gray-600 dark:bg-gray-800"
                />
            </div>

            <div>
                <label for="data_nascimento" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Data de nascimento</label>
                <input
                    type="date"
                    id="data_nascimento"
                    name="data_nascimento"
                    required
                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-[#2E3192] focus:ring-[#2E3192] dark:border-gray-600 dark:bg-gray-800"
                />
            </div>

            <button
                type="submit"
                class="w-full rounded-lg bg-[#2E3192] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#252878]"
            >
                Entrar
            </button>
        </form>
    </div>
</x-layouts.portal>
