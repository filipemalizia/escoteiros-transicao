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
