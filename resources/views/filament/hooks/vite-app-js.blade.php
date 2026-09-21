{{--
    O painel do chefe só carrega o CSS do tema do Filament (`viteTheme()`)
    — o `resources/js/app.js` do app "normal" (confetti, e as funções de
    desenho do cartão de conquista usadas por `x-progresso.modal-
    compartilhar`) nunca era incluído aqui, só no layout do portal público.
    Sem isso, `window.desenharCartaoConquista` não existe quando o chefe
    marca um item como concluído e o popup tenta abrir.
--}}
@vite('resources/js/app.js')
