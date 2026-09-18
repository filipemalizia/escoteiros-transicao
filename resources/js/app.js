import confetti from 'canvas-confetti';

document.addEventListener('livewire:init', () => {
    Livewire.on('conquista-nova', () => {
        confetti({
            particleCount: 150,
            spread: 70,
            origin: { y: 0.6 },
        });
    });
});

// Trilha de etapas (tela Início do portal): quando a trilha ultrapassa a
// largura da tela e rola horizontalmente, começa centralizada no
// distintivo "atual" em vez de sempre do primeiro — pra um jovem já
// avançado não precisar rolar pra achar onde está.
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('trilha-etapa-atual')?.scrollIntoView({
        behavior: 'auto',
        inline: 'center',
        block: 'nearest',
    });
});
