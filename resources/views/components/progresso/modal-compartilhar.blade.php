{{--
    Modal de "compartilhar conquista", global à página (uma instância só,
    incluída no layout/página, não por item) — abre em resposta ao evento
    `abrir-cartao-conquista` disparado por qualquer `x-progresso.botao-
    compartilhar` da tela, sem depender do Livewire (não tem estado de
    servidor pra guardar: o desenho inteiro acontece num canvas, no browser).

    O `<canvas>` visível AQUI é o mesmo PNG que sai no compartilhamento —
    nunca gera uma versão "bonita" na tela e outra pra exportar.
--}}
<div
    x-data="{
        show: false,
        gerando: false,
        podeCompartilharArquivo: false,
        dados: null,
        init() {
            this.podeCompartilharArquivo = !!(window.navigator.canShare && window.navigator.canShare({ files: [new File(['x'], 'x.png', { type: 'image/png' })] }));
            window.addEventListener('abrir-cartao-conquista', (evento) => this.abrir(evento.detail));
        },
        async abrir(dados) {
            this.dados = dados;
            this.show = true;
            this.gerando = true;
            await this.$nextTick();
            await window.desenharCartaoConquista(this.$refs.canvas, dados);
            this.gerando = false;
        },
        fechar() {
            this.show = false;
        },
        nomeArquivo() {
            const base = (this.dados?.titulo || 'conquista').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-');

            return `conquista-${base}.png`;
        },
        baixar(blob) {
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = this.nomeArquivo();
            link.click();
            URL.revokeObjectURL(url);
        },
        compartilhar() {
            this.$refs.canvas.toBlob(async (blob) => {
                if (! blob) {
                    return;
                }

                const arquivo = new File([blob], this.nomeArquivo(), { type: 'image/png' });

                if (this.podeCompartilharArquivo && window.navigator.canShare({ files: [arquivo] })) {
                    try {
                        await window.navigator.share({
                            files: [arquivo],
                            title: 'Conquista Escoteira',
                            text: `${this.dados.jovemNome} conquistou ${this.dados.titulo}!`,
                        });

                        return;
                    } catch (erro) {
                        if (erro?.name === 'AbortError') {
                            return;
                        }
                    }
                }

                this.baixar(blob);
            }, 'image/png');
        },
    }"
    x-show="show"
    x-cloak
    x-on:keydown.escape.window="fechar"
    class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 sm:items-center sm:p-4"
    x-on:click.self="fechar"
>
    <div class="relative w-full max-w-sm rounded-t-2xl bg-white p-4 shadow-xl dark:bg-gray-900 sm:rounded-2xl">
        <button
            type="button"
            x-on:click="fechar"
            class="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-300"
        >
            <x-filament::icon icon="heroicon-o-x-mark" class="h-5 w-5" />
        </button>

        <h3 class="mb-3 pr-8 text-base font-semibold text-gray-950 dark:text-white">Compartilhar conquista</h3>

        <div class="relative overflow-hidden rounded-xl bg-gray-100 dark:bg-white/5" style="aspect-ratio: 4 / 5">
            <canvas x-ref="canvas" width="1080" height="1350" class="h-full w-full"></canvas>

            <div x-show="gerando" class="absolute inset-0 flex items-center justify-center bg-black/10 text-sm font-medium text-white">
                Gerando...
            </div>
        </div>

        <button
            type="button"
            x-on:click="compartilhar"
            x-bind:disabled="gerando"
            class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-500 disabled:opacity-50"
        >
            <x-filament::icon icon="heroicon-o-share" class="h-4 w-4" />
            <span x-text="podeCompartilharArquivo ? 'Compartilhar' : 'Baixar imagem'"></span>
        </button>
    </div>
</div>
