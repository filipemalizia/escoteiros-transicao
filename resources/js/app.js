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

/**
 * Textos do cartão de conquista por `tipo` de evento (mesmos tipos de
 * `ExibeProgressoDoJovem::getEventosLinhaDoTempo()`, mais 'reconhecimento')
 * — única fonte da frase "Conquistou a especialidade/insígnia X" ou "a
 * etapa X do Ramo Y", usada tanto no portal do jovem quanto no painel do
 * chefe. `ramoNome` só entra no texto de etapa/reconhecimento (Bloco/Eixo
 * já têm nome específico o bastante sem precisar do ramo). `nivel` só entra
 * pra especialidade/insígnia de estrutura `itens_niveis` (1 ou 2) — o nome
 * da conquista continua "Conquistou a especialidade/insígnia" e o nível vai
 * junto do título ("X - Nível N"), na mesma letra do nome, em vez de virar
 * uma frase própria.
 */
function fraseCartaoConquista(tipo, titulo, ramoNome, nivel) {
    switch (tipo) {
        case 'especialidade':
            return { caption: 'Conquistou a especialidade', titulo: nivel ? `${titulo} - Nível ${nivel}` : titulo };
        case 'insignia':
            return { caption: 'Conquistou a insígnia', titulo: nivel ? `${titulo} - Nível ${nivel}` : titulo };
        case 'etapa':
            return { caption: 'Conquistou a etapa', titulo: ramoNome ? `${titulo} do Ramo ${ramoNome}` : titulo };
        case 'reconhecimento':
            return { caption: 'Conquistou o distintivo', titulo: ramoNome ? `${titulo} do Ramo ${ramoNome}` : titulo };
        case 'bloco':
            return { caption: 'Concluiu o bloco', titulo };
        case 'eixo':
            return { caption: 'Concluiu o eixo', titulo };
        default:
            return { caption: 'Conquistou', titulo };
    }
}

function carregarImagemCartao(url) {
    return new Promise((resolve, reject) => {
        const imagem = new Image();
        imagem.crossOrigin = 'anonymous';
        imagem.onload = () => resolve(imagem);
        imagem.onerror = reject;
        imagem.src = url;
    });
}

/**
 * Troféu (heroicon "trophy", versão sólida) usado no lugar da imagem
 * quando a especialidade/insígnia/bloco/eixo/etapa ainda não tem imagem
 * cadastrada — um SVG embutido em vez de emoji, pra não depender de o
 * dispositivo de quem visualiza o cartão ter fonte de emoji instalada (sem
 * ela, o emoji simplesmente não aparece) e pra manter a cor consistente
 * com a marca em qualquer navegador/sistema.
 */
const TROFEU_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#F59E0B">'
    + '<path fill-rule="evenodd" d="M5.166 2.621v.858c-1.035.148-2.059.33-3.071.543a.75.75 0 0 0-.584.859 6.753 6.753 0 0 0 6.138 5.6 6.73 6.73 0 0 0 2.743 1.346A6.707 6.707 0 0 1 9.279 15H8.54c-1.036 0-1.875.84-1.875 1.875V19.5h-.75a2.25 2.25 0 0 0-2.25 2.25c0 .414.336.75.75.75h15a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-2.25-2.25h-.75v-2.625c0-1.036-.84-1.875-1.875-1.875h-.739a6.706 6.706 0 0 1-1.112-3.173 6.73 6.73 0 0 0 2.743-1.347 6.753 6.753 0 0 0 6.139-5.6.75.75 0 0 0-.585-.858 47.077 47.077 0 0 0-3.07-.543V2.62a.75.75 0 0 0-.658-.744 49.22 49.22 0 0 0-6.093-.377c-2.063 0-4.096.128-6.093.377a.75.75 0 0 0-.657.744Zm0 2.629c0 1.196.312 2.32.857 3.294A5.266 5.266 0 0 1 3.16 5.337a45.6 45.6 0 0 1 2.006-.343v.256Zm13.5 0v-.256c.674.1 1.343.214 2.006.343a5.265 5.265 0 0 1-2.863 3.207 6.72 6.72 0 0 0 .857-3.294Z" clip-rule="evenodd"/>'
    + '</svg>';

const TROFEU_DATA_URI = `data:image/svg+xml,${encodeURIComponent(TROFEU_SVG)}`;

/**
 * Posiciona a imagem centrada em (cx, cy), sem distorcer (`object-fit:
 * contain`) e sem nenhum recorte/moldura/fundo desenhado por cima — os
 * distintivos oficiais já vêm com a própria forma e borda prontas na
 * imagem, então aqui só posiciona e deixa a imagem "se cortar sozinha"
 * (a transparência do PNG mostra o fundo do cartão por trás dela).
 */
function desenharImagemPosicionada(ctx, imagem, cx, cy, larguraMaxima, alturaMaxima) {
    const escala = Math.min(larguraMaxima / imagem.width, alturaMaxima / imagem.height);
    const largura = imagem.width * escala;
    const altura = imagem.height * escala;

    ctx.drawImage(imagem, cx - largura / 2, cy - altura / 2, largura, altura);
}

/**
 * Quebra `texto` em várias linhas sem estourar `larguraMaxima` — usa a
 * fonte já configurada em `ctx` (chamar depois de ajustar `ctx.font`).
 */
function quebrarLinhas(ctx, texto, larguraMaxima) {
    const palavras = texto.split(' ');
    const linhas = [];
    let linhaAtual = '';

    for (const palavra of palavras) {
        const tentativa = linhaAtual ? `${linhaAtual} ${palavra}` : palavra;

        if (linhaAtual && ctx.measureText(tentativa).width > larguraMaxima) {
            linhas.push(linhaAtual);
            linhaAtual = palavra;
        } else {
            linhaAtual = tentativa;
        }
    }

    linhas.push(linhaAtual);

    return linhas;
}

/**
 * Desenha `linhas` (já quebradas por `quebrarLinhas()`) a partir do topo —
 * a 1ª linha sempre cai exatamente em `yTopo`, nunca sobe pra "abrir espaço"
 * pras linhas de baixo. Importante pro espaçamento do cartão: um título de
 * 1 linha e um de 2 linhas (ex.: nome de Bloco longo) precisam começar no
 * mesmo lugar, bem abaixo do texto anterior — se a 1ª linha subisse pra
 * centralizar o bloco (como uma versão anterior fazia), ela colava no texto
 * de cima sempre que o título quebrasse em mais de 1 linha.
 */
function desenharLinhasDoTopo(ctx, linhas, x, yTopo, alturaLinha) {
    linhas.forEach((linha, indice) => ctx.fillText(linha, x, yTopo + indice * alturaLinha));
}

/**
 * Pequenos traços de "brilho" ao redor do medalhão (efeito de distintivo
 * reluzente) — posições e tamanhos fixos (não aleatórios), pra imagem saída
 * sempre igual.
 */
function desenharBrilhos(ctx, cx, cy, meiaLargura, meiaAltura, cor) {
    const brilhos = [
        [20, 1.14, 1.34, 11],
        [65, 1.2, 1.3, 6],
        [115, 1.2, 1.3, 6],
        [160, 1.14, 1.34, 11],
        [200, 1.14, 1.34, 11],
        [245, 1.2, 1.3, 6],
        [295, 1.2, 1.3, 6],
        [340, 1.14, 1.34, 11],
    ];

    ctx.save();
    ctx.strokeStyle = cor;
    ctx.lineCap = 'round';

    for (const [graus, r1, r2, espessura] of brilhos) {
        const rad = (graus * Math.PI) / 180;
        const cosA = Math.cos(rad);
        const sinA = Math.sin(rad);

        ctx.lineWidth = espessura;
        ctx.beginPath();
        ctx.moveTo(cx + cosA * meiaLargura * r1, cy + sinA * meiaAltura * r1);
        ctx.lineTo(cx + cosA * meiaLargura * r2, cy + sinA * meiaAltura * r2);
        ctx.stroke();
    }

    ctx.restore();
}

/**
 * Divisor decorativo entre o título da conquista e o rodapé: 2 traços com
 * uma bolinha centralizada entre eles.
 */
function desenharDivisor(ctx, cx, y, largura, cor) {
    ctx.save();
    ctx.strokeStyle = cor;
    ctx.lineWidth = 4;

    ctx.beginPath();
    ctx.moveTo(cx - largura / 2, y);
    ctx.lineTo(cx - 20, y);
    ctx.stroke();

    ctx.beginPath();
    ctx.moveTo(cx + 20, y);
    ctx.lineTo(cx + largura / 2, y);
    ctx.stroke();

    ctx.fillStyle = cor;
    ctx.beginPath();
    ctx.arc(cx, y, 6, 0, Math.PI * 2);
    ctx.fill();
    ctx.restore();
}

/**
 * Desenha o cartão de conquista (especialidade/insígnia/distintivo de
 * etapa/bloco/eixo) num canvas 1080x1350 — proporção 4:5, boa tanto pro
 * feed do Instagram quanto pra visualização em chat do WhatsApp. Usado por
 * `x-progresso.modal-compartilhar`, que depois exporta esse canvas como PNG
 * pra compartilhar/baixar.
 *
 * Fundo em degradê azul bem claro (não um azul sólido/forte). A imagem do
 * distintivo é só posicionada (`object-fit: contain`, sem distorcer) —
 * sem recorte nem moldura desenhada por cima, já que o distintivo oficial
 * já vem com a própria forma/borda prontas na imagem.
 *
 * @param {HTMLCanvasElement} canvas
 * @param {{tipo: string, titulo: string, imagemUrl: ?string, jovemNome: string, ramoNome: ?string, nivel: ?number}} dados
 */
export async function desenharCartaoConquista(canvas, dados) {
    const ctx = canvas.getContext('2d');
    const W = canvas.width;
    const H = canvas.height;

    const AZUL_MARINHO = '#2E3192';
    const AZUL_MARINHO_ESCURO = '#12143E';
    const AZUL_CLARO = '#60A5FA';

    const [imagemPrincipal] = await Promise.all([
        dados.imagemUrl ? carregarImagemCartao(dados.imagemUrl).catch(() => null) : Promise.resolve(null),
        document.fonts.load('600 40px "Instrument Sans"'),
        document.fonts.load('600 64px "Instrument Sans"'),
        document.fonts.load('400 26px "Instrument Sans"'),
    ]);

    // Sem imagem cadastrada (ou que falhou ao carregar) — usa o troféu no
    // lugar, em vez do medalhão ficar vazio.
    const imagem = imagemPrincipal || (await carregarImagemCartao(TROFEU_DATA_URI).catch(() => null));

    // Fundo em degradê azul bem claro (pedido explícito: nada de azul
    // sólido/forte) — quase branco, só um toque de cor.
    const fundo = ctx.createLinearGradient(0, 0, 0, H);
    fundo.addColorStop(0, '#EAF2FF');
    fundo.addColorStop(1, '#F8FBFF');
    ctx.fillStyle = fundo;
    ctx.fillRect(0, 0, W, H);

    // Área do medalhão — só define onde/quão grande a imagem entra e onde
    // os brilhos decorativos ficam ao redor; a imagem em si não é recortada
    // nem ganha moldura desenhada aqui (o distintivo oficial já vem com a
    // própria forma/borda prontas na própria imagem).
    const cx = W / 2;
    const cy = 430;
    const areaMeiaLargura = 300;
    const areaMeiaAltura = 230;

    desenharBrilhos(ctx, cx, cy, areaMeiaLargura, areaMeiaAltura, 'rgba(96, 165, 250, 0.55)');

    if (imagem) {
        ctx.save();
        ctx.shadowColor = 'rgba(18, 20, 62, 0.2)';
        ctx.shadowBlur = 35;
        ctx.shadowOffsetY = 14;
        desenharImagemPosicionada(ctx, imagem, cx, cy, areaMeiaLargura * 2, areaMeiaAltura * 2);
        ctx.restore();
    }

    const frase = fraseCartaoConquista(dados.tipo, dados.titulo, dados.ramoNome, dados.nivel);

    ctx.textAlign = 'center';

    // Nome do jovem vem primeiro (antes do texto da conquista).
    ctx.fillStyle = AZUL_MARINHO_ESCURO;
    ctx.font = '600 64px "Instrument Sans", sans-serif';
    ctx.fillText(dados.jovemNome, cx, 810);

    ctx.fillStyle = AZUL_MARINHO;
    ctx.font = '600 34px "Instrument Sans", sans-serif';
    ctx.fillText(frase.caption.toUpperCase(), cx, 880);

    // O título (nome da conquista) começa sempre no mesmo lugar (952),
    // crescendo pra baixo quando quebra em mais de 1 linha — nunca sobe pra
    // "abrir espaço", senão colaria na legenda acima quando o texto for
    // longo (ex.: nome de Bloco/Eixo).
    ctx.fillStyle = AZUL_MARINHO_ESCURO;
    ctx.font = '600 48px "Instrument Sans", sans-serif';
    const alturaLinhaTitulo = 62;
    const yTituloTopo = 952;
    const linhasTitulo = quebrarLinhas(ctx, frase.titulo, W - 160);
    desenharLinhasDoTopo(ctx, linhasTitulo, cx, yTituloTopo, alturaLinhaTitulo);

    const yTituloFim = yTituloTopo + (linhasTitulo.length - 1) * alturaLinhaTitulo;
    desenharDivisor(ctx, cx, yTituloFim + 110, 260, AZUL_CLARO);

    ctx.fillStyle = '#6B7280';
    ctx.font = '600 24px "Instrument Sans", sans-serif';
    ctx.fillText('Ferramenta de Transição - GEMar Marcílio Dias - 02BA', cx, H - 88);

    ctx.fillStyle = '#9CA3AF';
    ctx.font = '400 22px "Instrument Sans", sans-serif';
    ctx.fillText('Progressão Escoteira', cx, H - 56);
}

window.desenharCartaoConquista = desenharCartaoConquista;

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
