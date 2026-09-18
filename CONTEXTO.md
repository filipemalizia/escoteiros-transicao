# Resumo do Sistema — `escoteiros-transicao`

Documento de referência consolidado do projeto. Mantenha atualizado quando
novas fases forem implementadas.

## Propósito

Ferramenta **temporária** (vida útil estimada: ~11 meses), de uso interno de
um Grupo Escoteiro, para acompanhar a migração dos jovens do **programa
educativo antigo** para o **novo programa educativo** dos Escoteiros do
Brasil, nos 4 ramos: Lobinho, Escoteiro, Sênior, Pioneiro.

## Stack

Laravel + Filament (painel admin, com autenticação nativa do Filament, sem
Breeze). `maatwebsite/excel` para importação de planilhas (.xlsx e .csv).
Hospedagem: Hostinger (SSH + autoinstalador Laravel).

**Nota de infraestrutura importante**: páginas customizadas do Filament (as
que não são Resources de CRUD padrão, ex. "Progresso do Jovem",
"Importar Planilha") só recebem CSS de classes Tailwind arbitrárias se o
painel tiver um **tema customizado registrado** (`php artisan
make:filament-theme` + `->viteTheme(...)` no Panel Provider). Sem isso, o
Tailwind escrito nessas views é compilado mas nunca carregado na página —
já foi causa de um bug real (Fase 6/revisão de UI).

## Schema atual (todas as fases já implementadas)

```
users                          — padrão Laravel/Filament, + is_admin (bool)

ramos                          — id, nome
  (seed fixo: Lobinho, Escoteiro, Sênior, Pioneiro)

equipes                        — id, ramo_id (FK ramos), nome
                                  Subdivisão dentro de um Ramo (ex: uma
                                  patrulha/seis/equipe específica). Usada pra
                                  controle de acesso: um usuário só vê/edita
                                  jovens das equipes a que está vinculado
                                  (exceto admins, que veem tudo).

equipe_user                    — pivot N:N (equipe_id, user_id). Um usuário
                                  pode estar em várias equipes.

jovens                         — id, nome, registro (string, nullable, unique —
                                  "Registro Escoteiro", normalmente numérico;
                                  usado no portal público junto com a data de
                                  nascimento; jovens antigos precisam ser
                                  preenchidos manualmente), data_nascimento,
                                  ramo_atual_id (FK ramos), equipe_id (FK
                                  equipes, nullable — jovem sem equipe só fica
                                  visível para admins)

especialidades_distintivos     — id, nome, tipo (Especialidade/Insígnia)
                                  (compartilhado entre ramos, sem ramo_id)

-- Programa ANTIGO --
areas_desenvolvimento_antigas  — id, ramo_id, nome
competencias_antigas           — id, area_desenvolvimento_id, descricao
itens_antigos                  — id, competencia_id, codigo (unique), descricao,
                                  etapa (nullable — só usado por Lobinho/Escoteiro,
                                  valores: Pata Tenra/Saltador/Rastreador/Caçador ou
                                  Pista/Trilha/Rumo/Travessia)

-- Programa NOVO --
eixos_novos                    — id, ramo_id, nome
blocos_novos                   — id, eixo_id, titulo, descricao,
                                  quantidade_minima_variaveis
itens_novos                    — id, bloco_id, codigo (unique), descricao,
                                  tipo_acao (Obrigatória/Variável/Substitutiva),
                                  modalidade (Geral/Ar/Mar, default Geral),
                                  especialidade_id (FK, nullable — só quando
                                  tipo_acao = Substitutiva), observacao

-- Equivalência entre os dois sistemas --
equivalencias                  — id, item_antigo_id (FK itens_antigos),
                                  item_novo_id (FK itens_novos),
                                  tipo_equivalencia (1-1/N-1/1-N/sem_equivalencia),
                                  observacao
                                  [ver "Pendência conhecida" sobre 'sem_equivalencia']

equivalencia_blocos            — id, item_antigo_id (FK itens_antigos),
                                  bloco_novo_id (FK blocos_novos), observacao
                                  Crédito de item antigo direto pro BLOCO (não
                                  pra um item novo específico) — usado quando o
                                  documento oficial lista "atividades do
                                  programa anterior que complementam as
                                  atividades variáveis" de um bloco, sem
                                  correspondência 1-a-1 com nenhum item novo.
                                  Cada item antigo vinculado e concluído soma
                                  +1 na cota de Ações Variáveis do bloco
                                  (`StatusProgressaoService::statusBloco()`).
                                  Cadastro em lote na tela "Equivalência de
                                  Bloco em Lote", e CRUD completo (ver/editar/
                                  excluir os já criados) no Resource
                                  "Equivalências de Bloco". Cuidado: não
                                  vincular o mesmo item antigo aqui E como equivalência
                                  item-a-item pro mesmo bloco — contaria em
                                  dobro.

-- Itens personalizados (avulsos, fora do catálogo oficial) --
itens_personalizados           — id, bloco_novo_id (FK blocos_novos),
                                  descricao, criado_por_id (FK users,
                                  nullOnDelete). Sempre tratado como Ação
                                  Variável do bloco — ver "Item
                                  Personalizado" pra regras completas.
item_personalizado_jovem       — pivot N:N (item_personalizado_id,
                                  jovem_id). Um item personalizado pode ser
                                  pra um jovem só ou pra vários de uma vez.
progresso_personalizado        — id, jovem_id, item_personalizado_id,
                                  concluido, data_conclusao,
                                  registrado_por_id, solicitado_pelo_jovem
                                  (bool), solicitado_em (timestamp) — mesma
                                  estrutura de progresso_antigo/novo.

-- Progresso do jovem --
progresso_antigo               — id, jovem_id, item_antigo_id, concluido,
                                  data_conclusao, registrado_por_id,
                                  solicitado_pelo_jovem (bool), solicitado_em
                                  (timestamp) — ver "Fluxo de aprovação"
progresso_novo                 — id, jovem_id, item_novo_id, concluido,
                                  data_conclusao, registrado_por_id,
                                  solicitado_pelo_jovem (bool), solicitado_em
                                  (timestamp) — ver "Fluxo de aprovação"

-- Requisitos complementares (Reconhecimento final / etapas especiais) --
jovem_requisitos_complementares — id, jovem_id, chave (string fixa por
                                  ramo/sistema), tipo (booleano/contador),
                                  valor_booleano, valor_numero
```

## Regras de negócio principais

### Conclusão de item individual

- **Item antigo**: concluído se marcado direto em `progresso_antigo`, **OU**
  por equivalência (item novo correspondente concluído, considerando
  1-1/N-1/1-N — ver `EquivalenciaCreditoService`/`itemAntigoConcluido()`).
- **Item novo**: mesma lógica espelhada (`itemNovoConcluido()`).
- Equivalência é **bidirecional**, com recursão protegida contra ciclo
  (limite de profundidade + rastro de itens já visitados na cadeia).

### Conclusão de Competência (antigo)

100% dos itens daquela Competência devem estar concluídos (direto ou por
equivalência). Sem meio-termo.

### Conclusão de Bloco (novo)

Todos os itens `Obrigatória` concluídos **E** (itens `Variável` concluídos
`>=` `quantidade_minima_variaveis` do bloco **OU** pelo menos 1 item
`Substitutiva` concluído).

### Etapas e Reconhecimento — por ramo (`EtapaProgressaoService`)

**Programa NOVO** (sempre 18 Blocos no total, cortes cumulativos por ramo):

| Ramo | Cortes de etapa (blocos concluídos) | Nome do Reconhecimento |
|---|---|---|
| Lobinho | 4 / 8 / 13 / 18 | Cruzeiro do Sul |
| Escoteiro | 4 / 8 / 13 / 18 | Lis de Ouro |
| Sênior | 6 / 12 / 18 | Escoteiro da Pátria |
| Pioneiro | 6 / 12 / 18 | Insígnia de B-P |

Requisitos complementares do Reconhecimento (novo), chaves em
`jovem_requisitos_complementares`: 2 para Lobinho/Sênior/Pioneiro
(`desafio_pessoal`, `avaliacao_pares`), 3 para Escoteiro (`desafio_pessoal_travessia`,
`autoavaliacao`, `avaliacao_corte_honra_escotistas`).

**Programa ANTIGO** (dois mecanismos diferentes):

- **Sênior e Pioneiro**: etapa por **fração do total de itens** do ramo
  (Sênior: 1/3, 2/3, 100%; Pioneiro: 10%*, 50%, 100% — com a etapa
  "Cidadania" do Pioneiro exigindo também 2 flags complementares além do
  50%. *O corte de 10% foi interpretado como parte de "Comprometimento"
  (sem nome próprio distinto) — ver pendência conhecida nº3).
- **Lobinho e Escoteiro**: os itens não têm 4 etapas individuais — são
  agrupados em **2 "piscinas"** (a coluna `etapa` grava o nome da piscina).
  Cada piscina cobre 2 etapas seguidas, indiferentes entre si:
  - Escoteiro: piscina "Pista e Trilha" (50% = Pista, 100% = Trilha) e
    piscina "Rumo e Travessia" (50% = Rumo, 100% = Travessia).
  - Lobinho: piscina "Pata Tenra e Saltador" (100% = Saltador) e piscina
    "Rastreador e Caçador" (50% = Rastreador, 100% = Caçador). A 1ª piscina
    do Lobinho tem uma regra especial: existe um **Período Introdutório**
    (subconjunto fixo de itens marcados `introdutorio = true` em
    `itens_antigos`) que precisa estar **100% concluído** — não uma fração
    qualquer — pra valer a etapa "Pata Tenra". Sem isso, mesmo com 50% da
    piscina concluído, o jovem ainda aparece em "Pata Tenra".
  - As duas piscinas de cada ramo são **independentes** na apuração (o
    cálculo da 2ª não depende da 1ª estar concluída). A "etapa atual"
    exibida, porém, ainda percorre as piscinas na ordem nominal e mostra a
    primeira que não estiver 100% — decisão de design registrada em
    `EtapaProgressaoService::etapaAntigoPorItem()`, a confirmar com o
    usuário se surgir um caso real de piscina 2 adiantada com piscina 1
    pendente.
  - Cadastro: campo "Piscina de etapas" (Select, opções vêm de
    `EtapaProgressaoService::etapasAntigoPorRamo()`) + campo "Introdutório"
    (Toggle, só aparece pra piscinas que usam essa regra) na aba Itens de
    Competência Antiga. Importação de planilha tem coluna opcional
    "Introdutório" (aceita Sim/S/1/X/Verdadeiro/True).

Requisitos complementares do Reconhecimento (antigo): 4 chaves para
Lobinho, 5 para Escoteiro, 5 para Sênior, 4 para Pioneiro (divididos entre
a etapa Cidadania e a Insígnia BP final).

### Controle de acesso por Equipe

- `App\Policies\JovemPolicy`: admin (`users.is_admin`) vê/gerencia qualquer
  jovem; usuário comum só vê/gerencia jovens cuja `equipe_id` esteja entre
  as equipes vinculadas a ele (`users.equipes()`, N:N via `equipe_user`).
  Jovem sem equipe atribuída só é visível para admins.
- `JovemResource::getEloquentQuery()` aplica o mesmo filtro na listagem e no
  binding de rota (`/painel/jovens/{id}/edit` e `/progresso`), então um
  usuário sem acesso recebe 404 ao tentar acessar direto pela URL.
- `App\Policies\UserPolicy` e `App\Policies\EquipePolicy`: todo método exige
  `is_admin` — só administradores gerenciam usuários e equipes (a
  autoedição de perfil do Filament, `->profile()`, continua liberada pra
  todos).
- **Bootstrap do primeiro admin**: a migration que adiciona `is_admin`
  promove automaticamente o usuário mais antigo já existente a admin (senão
  ninguém conseguiria promover o primeiro admin depois do deploy). Novos
  usuários criados depois disso precisam ser marcados como admin
  manualmente por um admin existente.

## Interface (Filament)

- **Marca**: logo oficial do grupo (`public/images/logo.svg`, copiado de
  `marciliodias/assets/images/logo.svg` no projeto `malizia-theme`) usado
  como favicon (painel via `->favicon()` no `AdminPanelProvider`; fora do
  painel via `<link rel="icon">` em `layouts/portal.blade.php`) e como
  `->brandLogo()` do painel (barra lateral + tela de login admin,
  comportamento padrão do Filament). Nome "Ferramenta de Transição /
  GEMar Marcílio Dias - 02BA" aparece em 2 formatos, via partial
  reutilizável `resources/views/filament/components/brand-nome.blade.php`
  (recebe `align` e `extraClass`, sem usar `@props`/`$attributes` porque é
  invocado tanto por `@include` quanto por `view(...)->render()` dentro de
  render hooks do Filament, não só como `<x-.../>`):
  - **Header interno do painel** (barra lateral + barra superior mobile):
    nome em 2 linhas empilhadas ao lado do logo, via render hooks
    `SIDEBAR_LOGO_AFTER`/`TOPBAR_LOGO_AFTER` registrados no
    `AdminPanelProvider`.
  - **Telas de login** (admin e jovem): logo grande + nome, dentro de um
    cartão — ver abaixo.
  - Cor da marca é o azul marinho `#2E3192` (cor dominante no próprio
    SVG). É a `primary` do painel, mas **não** via `Color::hex('#2E3192')`
    puro — o algoritmo de geração de paleta do Filament (OKLCH com
    lightness/chroma fixos por tom) não preserva a cor exata em nenhum
    tom, e o 600 (usado nos botões preenchidos) saía visivelmente mais
    claro que a marca (`#6472E2`, um azul lavado, em vez do navy real).
    A paleta em `AdminPanelProvider` é uma escada gerada manualmente em
    HSL a partir do mesmo hue/saturação da marca, com o tom 600 fixado no
    hex exato (confirmado batendo o OKLCH resultante contra o hex
    original). Fora do painel, os botões de ação principal (`welcome.
    blade.php`, `portal/login.blade.php`) usam a mesma cor via classe
    Tailwind arbitrária `bg-[#2E3192]` (poucos usos pontuais, não valeu a
    pena virar token no tema).

### Telas de login (admin e jovem) - layout espelhado

As duas seguem a mesma estrutura, de propósito, pra reforçar a marca:
logo grande (125px) → nome da ferramenta (`brand-nome`, vem logo depois
do logo) → nome do portal ("Portal Administrativo"/"Portal do Jovem") →
texto de apoio → formulário. Tudo dentro de um cartão (fundo
branco/`gray-900`, `ring-1`, `shadow-sm`, `rounded-2xl`) centralizado na
página, não mais soltos no topo.

- **Admin**: `Filament\Auth\Pages\Login` não é feito pra ficar sob
  `app/Filament/Pages/` - esse diretório é auto-descoberto por
  `->discoverPages()` como páginas de navegação normais, o que quebraria
  com uma page de auth. A classe customizada (`App\Filament\Auth\Login`)
  fica em `app/Filament/Auth/`, fora da descoberta, e é registrada
  explicitamente via `->login(Login::class)`. O header padrão do
  Filament só tem 2 fatias fixas (heading grande + subheading pequeno,
  nessa ordem) - como o pedido era o nome da ferramenta **antes** do nome
  do portal, as duas informações viram uma coisa só dentro do
  `getHeading()`: o partial `brand-nome` (menor) em cima, "Portal
  Administrativo" (span grande, faz as vezes de heading de verdade)
  embaixo, ambos dentro do mesmo `<h1>` que o Filament já desenha. O
  cartão em si **já vem de graça do Filament** (`.fi-simple-main` já tem
  `bg-white`/`ring-1`/`shadow-xs`/`rounded-xl` por padrão) - cheguei a
  duplicar essa mesma receita visual num `.fi-simple-page-content`
  aninhado por engano, o que criava um "cartão dentro do cartão"
  (bordas/sombra duplicadas); removido, `theme.css` só ajusta o tamanho
  do logo (`.fi-simple-header .fi-logo { height: 125px !important; }` -
  precisa do `!important` porque o tamanho normal do logo vem de um
  `style` inline gerado a partir do `brandLogoHeight()` do painel, que é
  compartilhado com a barra lateral - não dá pra ter um tamanho diferente
  só na tela de login via config do Filament, só via CSS mais
  específico).
- **Jovem** (`portal/login.blade.php`): cartão montado à mão em Tailwind
  puro (mesma receita visual do cartão do admin, logo também 125px via
  `h-31.25 w-31.25`, escala fracionária do Tailwind v4 - equivale a
  125px). O logo genérico que antes ficava solto no topo de toda página
  do portal (`layouts/portal.blade.php`) foi removido de lá - cada
  página agora é dona da própria composição de marca (`welcome.
  blade.php` tem seu próprio logo pequeno acima do título; a tela de
  progresso do jovem não tem logo, não foi pedido).
- `FilamentInfoWidget` (o card padrão do Filament com versão/link pra
  documentação/GitHub) foi removido do Painel - não fazia sentido pros
  usuários finais da ferramenta, só é útil em dev.
- Navegação em 2 blocos: sem grupo (Painel/Jovens/Equipe/Usuários, nessa
  ordem via `navigationSort` negativo) e grupo **Ferramentas** (Equivalência
  de Bloco em Lote, Nova Equivalência em Lote, Importar Planilha,
  Equivalências de Bloco, Equivalências, Especialidades/Distintivos) — a
  ordem entre grupos nomeados (Programa Antigo/Novo/Ferramentas) é
  controlada por `->navigationGroups([...])` no `AdminPanelProvider`
  (sem isso a ordem entre grupos não registrados explicitamente fica
  imprevisível). "Painel" é um `App\Filament\Pages\Dashboard` customizado
  (extends o Dashboard padrão do Filament só pra renomear/reordenar).
- Widget de estatísticas no Painel (`app/Filament/Widgets/EstatisticasOverview.php`):
  total de jovens, total de equipes, itens aguardando avaliação (ver
  abaixo) e jovens por ramo — os ramos seguem uma ordem fixa não
  alfabética (`ORDEM_RAMOS`: Lobinho, Escoteiro, Sênior, Pioneiro), mesmo
  padrão de `array_search`+fallback `999` já usado em
  `ExibeProgressoDoJovem::ORDEM_AREAS_ANTIGAS`. O card "Itens Aguardando
  Avaliação" soma pendências (`solicitado_pelo_jovem = true`,
  `concluido = false`) de `ProgressoAntigo`/`ProgressoNovo`/
  `ProgressoPersonalizado`, mas só dos jovens das equipes que o usuário
  logado tem acesso — admin vê o total de todas as equipes (mesma regra de
  escopo usada em `JovemResource::getEloquentQuery()`).
- Resources de CRUD: Jovem, Equipe, Usuário, Área de Desenvolvimento Antiga,
  Competência Antiga (+ Itens), Eixo Novo, Bloco Novo (+ Itens),
  Especialidade/Distintivo, Equivalência
- **Catálogo restrito a admin**: os grupos "Programa Antigo" (Área de
  Desenvolvimento Antiga, Competência Antiga + Itens), "Programa Novo"
  (Eixo Novo, Bloco Novo + Itens) e "Ferramentas" (as 6 páginas/resources
  de equivalência, importação e especialidades) só ficam visíveis/
  acessíveis pra `is_admin` — um usuário comum não pode mais alterar o
  catálogo compartilhado (só gerenciar seus próprios jovens/equipe). Pra
  Resources isso é via Policy (`app/Policies/*Policy.php`, um por model,
  todo método = `$user->isAdmin()`); pra páginas customizadas (as 3 em
  `app/Filament/Pages/`, sem model por trás) é via
  `public static function canAccess(): bool` sobrescrito direto na page.
- Página de Importação (.xlsx/.csv), por Ramo + Sistema, com mapeamento de
  colunas por nome de cabeçalho (não por posição)
- Página "Progresso do Jovem" (2 abas, **Novo é a padrão/primária**,
  Antigo é secundária), com checkboxes de marcação (grava `data_conclusao`
  + `registrado_por_id` automaticamente), badges de status por
  Competência/Bloco, indicador de item concluído "via equivalência", % de
  conclusão, etapa atual (nome certo do ramo), banner de elegibilidade ao
  Reconhecimento. **Não existe mais uma seção "Pendências" separada** — o
  detalhe do que falta (`StatusProgressaoService::pendenciasNovo()`)
  aparece direto dentro do acordeão do próprio bloco quando ele não está
  concluído, pra evitar ter a mesma informação em dois lugares da tela
  (a lista separada, com link pra rolar até o bloco, existiu brevemente
  e foi removida por causa disso). O PDF continua com a lista completa
  de pendências, já que serve pra impressão/compartilhamento.
- Indicador de "aguardando avaliação" (item solicitado pelo jovem, ainda
  sem confirmação do adulto) em 3 níveis, do mais amplo ao mais específico:
  contador na própria aba ("Programa Novo 🔶2"), banner no topo da aba
  ("🔔 2 itens aguardando sua avaliação"), e badge + borda âmbar no
  acordeão do bloco/competência específico que tem o item pendente — assim
  o adulto não precisa abrir bloco por bloco pra achar o que precisa
  avaliar. A badge do item em si diz apenas "Aguardando avaliação" (nome
  anterior, "Aguardando confirmação do jovem", foi trocado por ficar
  confuso — quem avalia é o adulto, não o jovem).
- Botão de PDF é um dropdown: "Baixar Todos" (os dois programas), "Baixar
  Novo Programa" ou "Baixar Programa Antigo" separadamente
  (`VerProgresso::baixarPendenciasPdf(string $programa)`)
- Tela de Equipe tem uma aba "Jovens" (RelationManager) pra associar um
  jovem já existente (filtrado pelo ramo da equipe) ou criar um jovem novo
  já vinculado, sem precisar editar jovem por jovem
- Blocos (novo) e Competências (antigo) são exibidos em acordeões
  fechados por padrão (`<x-progresso.accordion>`,
  `resources/views/components/progresso/accordion.blade.php`) — só nome,
  intencionalidade (`descricao`), status e (se pendente) o detalhe do que
  falta ficam visíveis até o usuário clicar pra abrir. Implementado com
  Alpine puro (sem plugin de animação), porque o mesmo componente é
  reaproveitado no portal público do jovem, que não carrega o bundle de
  JS do Filament. Os getters somente-leitura usados por essa tela (e pelo
  portal) vivem em `app/Concerns/ExibeProgressoDoJovem.php`, não mais
  direto na page `VerProgresso`.
- Formulário de Jovem: seção "Requisitos Complementares" dinâmica por ramo
  (Toggle pra booleanos, número pra contadores)
- Listagem de Jovens (`JovensTable`): ícone de alerta (▲, cor `warning`) na
  primeira coluna quando o jovem tem pelo menos um item aguardando
  avaliação, em qualquer um dos três programas
  (`ProgressoAntigo`/`ProgressoNovo`/`ProgressoPersonalizado`). Pra evitar
  N+1 na listagem inteira, a query da tabela usa
  `->modifyQueryUsing(...)` com `withExists([...])` (uma flag booleana por
  relação, calculada em SQL) em vez de carregar as relações e checar em
  PHP por linha.

### Cuidado: `theme.css` do painel escaneia só pastas específicas

`resources/css/filament/admin/theme.css` (o tema Vite do painel) só tem
Tailwind das classes usadas em `app/Filament/**` e
`resources/views/filament/**` (`@source` explícito — o Filament exige isso
pra temas customizados, a detecção automática do Tailwind não cobre tudo
sozinha aqui). **Qualquer Blade component compartilhado fora dessas duas
pastas** (ex.: `resources/views/components/progresso/*`, usado tanto no
painel quanto no portal) **precisa de um `@source` próprio nesse arquivo**,
senão as classes Tailwind usadas nele saem sem estilo dentro do painel
(já aconteceu — badges e acordeões ficaram sem CSS até adicionar
`@source '../../../../resources/views/components/**/*';`). O portal
público usa só `resources/css/app.css`, que não tem essa restrição.
Depois de mexer em CSS/Blade, rodar `npm run build` (ou manter `npm run
dev` ligado) — `public/build` não é versionado no git.

## Portal público do jovem

Fora do painel Filament (rotas simples em `routes/web.php`, prefixo
`/portal`), pra o próprio jovem consultar seu progresso sem precisar de
conta de usuário:

- `GET /` (`resources/views/welcome.blade.php`) — página inicial simples
  com 2 botões: "Acesso ADM" (`/painel/login`) e "Acesso Jovem"
  (`/portal/entrar`), no lugar da tela padrão do Laravel.
- `GET /portal/entrar` — formulário (registro + data de nascimento).
- `POST /portal/entrar` — valida (`AutenticacaoController::autenticar`),
  com rate limit (`RateLimiter::for('portal-login', ...)` em
  `AppServiceProvider::boot()`, por IP e por registro — evita força bruta
  já que registro+nascimento é uma "senha" fraca).
- Sessão leve via `App\Services\Portal\SessaoJovemService` — **não** é uma
  conta `User`/guard de Auth, só duas chaves na sessão padrão do Laravel
  (`portal_jovem_id`, `portal_autenticado_em`), válidas por
  `config('portal.sessao_minutos')` (padrão 240min, `.env`:
  `PORTAL_SESSAO_MINUTOS`). Regenera o ID da sessão no login/logout.
  **Cuidado**: `diffInMinutes()` no Carbon 3 retorna valor **com sinal**
  (não mais absoluto como no Carbon 2) — a checagem de expiração usa
  `abs()` de propósito; removê-lo faz a sessão nunca expirar.
- `GET /portal` (rota `portal.progresso`) — Livewire full-page
  `App\Livewire\Portal\Progresso`, protegida pelo middleware
  `portal.auth` (`EnsurePortalJovemAutenticado`). Usa a mesma trait
  `ExibeProgressoDoJovem` e o mesmo componente de acordeão do painel; não
  tem os métodos `toggleAntigo`/`toggleNovo` do painel (que marcam
  concluído direto) — só `solicitarNovo`, que pede confirmação de um
  adulto (ver "Fluxo de aprovação"). **Só mostra o Programa Novo** — o
  Antigo não fica visível pro jovem (nem o método `solicitarAntigo`
  existe no componente do portal, só no do painel). Mostra Reconhecimento,
  Requisitos Complementares (só leitura) e a lista de "itens do Programa
  Antigo que também contam como Ação Variável" de cada bloco
  (`equivalenciasBloco`) — mesma informação que o painel, sem os campos
  editáveis.
- Layout próprio (`resources/views/components/layouts/portal.blade.php`,
  `<x-layouts.portal>`) com Tailwind puro — **não** usa `<x-filament::*>`
  (exceto `<x-filament::icon>`, que só renderiza SVG inline e não depende
  do CSS do painel). Por isso os badges de status têm um componente
  próprio (`<x-progresso.badge>`, cores base do Tailwind — green/amber/
  red/sky/gray — em vez dos tokens semânticos do Filament como
  primary/success/warning/danger, que só existem dentro do tema do
  painel). **Cuidado**: já apareceu duas vezes um botão sem estilo em
  view fora do painel (`bg-primary-600`, `bg-danger-50` etc.) — essas
  classes não existem no Tailwind puro, só dentro do tema do Filament.
  Em qualquer view de `resources/views/portal/` ou `welcome.blade.php`,
  usar cores base do Tailwind (amber/green/red/...), nunca os tokens
  semânticos do Filament.
- Depende de `jovens.registro` estar preenchido — jovens cadastrados antes
  dessa fase não conseguem entrar até um líder preencher esse campo.
- **Cuidado com `redirect()` dentro de uma action Livewire**: dentro de um
  componente, `redirect()->route(...)` retorna um
  `Livewire\Features\SupportRedirects\Redirector`, não um
  `Illuminate\Http\RedirectResponse` — declarar o retorno do método como
  `RedirectResponse` estritamente causa um `TypeError` silencioso (o botão
  "não faz nada" do ponto de vista do usuário). Use `$this->redirect($url)`
  (do próprio Livewire) e retorno `void`, como em
  `Progresso::sair()`/`solicitarNovo()`.

## Fluxo de aprovação (jovem solicita, adulto confirma)

O jovem, pelo portal público, pode "avisar" que fez um item — mas isso
**não** marca `concluido` direto (só um adulto tem essa autoridade).

- Colunas `solicitado_pelo_jovem` (bool) + `solicitado_em` (timestamp) em
  `progresso_antigo`/`progresso_novo`, aditivas — `concluido` continua
  sendo a **única** coluna lida por `StatusProgressaoService` e
  `EquivalenciaCreditoService`. Um item solicitado mas não confirmado não
  conta pra nenhum cálculo de status/percentual/etapa.
- Jovem (`Portal\Progresso::solicitarNovo` — só existe pro Programa Novo,
  já que o Antigo não é visível no portal): grava
  `solicitado_pelo_jovem = true` + `solicitado_em = now()`, sem tocar em
  `concluido`. Vira botão "Marcar como feito" → badge "Aguardando
  confirmação" (sem poder reenviar). Não faz nada se o item já está
  `concluido`.
- Adulto (`VerProgresso::confirmarAntigo/confirmarNovo` — o painel
  continua cobrindo os dois programas): igual ao `toggleAntigo/toggleNovo`
  mas fixando `concluido = true` e limpando os campos de solicitação.
  `rejeitarAntigo/rejeitarNovo`: só limpa os campos de solicitação, sem
  tocar em `concluido` — **sem histórico persistido de rejeição**
  (decisão deliberada de manter simples; dá pra
  adicionar auditoria depois se precisar).
- `toggleAntigo`/`toggleNovo` (o clique direto do adulto no checkbox)
  também limpam os campos de solicitação, como rede de segurança caso
  exista uma solicitação pendente esquecida.
- UI no painel: badge amarela "Aguardando avaliação" + botões Confirmar/
  Rejeitar ao lado do checkbox (`ver-progresso.blade.php`). Indicador em 3
  níveis pra facilitar achar o que precisa avaliar sem abrir bloco por
  bloco: contador na aba, banner no topo da aba, e badge + borda âmbar no
  acordeão do bloco/competência específico.

### Tela central "Novo Item Personalizado" (`app/Filament/Pages/CriarItemPersonalizado.php`)

Além de criar direto na ficha de um jovem específico
(`VerProgresso::criarItemPersonalizado()`), qualquer adulto também pode
criar um item personalizado numa tela própria (fora de "Ferramentas", que
é admin-only — essa aparece no bloco sem grupo, logo depois de "Jovens"),
escolhendo **Ramo → Bloco → múltiplos Jovens** de uma vez. O campo Ramo é
só pra filtrar os selects seguintes (Bloco e Jovens) — o item em si não
guarda `ramo_id` (não existe essa coluna); o ramo dele fica implícito via
`bloco_novo_id → eixo_id → ramo_id`, igual explicado na seção seguinte.
A lista de Jovens é filtrada por `ramo_atual_id` do ramo escolhido **e**
pelas equipes do adulto logado (todas, se admin) — mesma regra de acesso
de sempre. Reaproveita a mesma revalidação de segurança que o Select
"multiple" do Filament já faz sozinho: `options()` é recalculado no
servidor a cada submit a partir do `Get('ramo_id')` atual, e se algum ID
enviado não estiver mais na lista de opções válidas (equipe trocou,
sessão adulterada etc.), o Filament **rejeita a submissão inteira**
(nenhum item é criado, nem parcialmente) — não precisa de filtragem
manual extra no método `criar()`.

## Item Personalizado (ação avulsa criada por um adulto)

Qualquer adulto (não só admin) pode criar, dentro de um Bloco do Programa
Novo, um item avulso — sempre tratado como Ação Variável — pra um jovem
específico ou pra vários de uma vez. Não faz parte do catálogo oficial
(`ItemNovo`), por isso vive em tabelas próprias
(`itens_personalizados`/`item_personalizado_jovem`/
`progresso_personalizado` — ver models `ItemPersonalizado`/
`ProgressoPersonalizado`).

- **Quem pode criar**: qualquer adulto com acesso ao jovem (mesma regra de
  `JovemPolicy` — equipe do jovem, ou admin). Ao criar, o jovem da página
  atual entra automaticamente; o formulário oferece opcionalmente "também
  aplicar para" outros jovens, mas só lista os que esse adulto já pode
  gerenciar (`VerProgresso::getJovensDisponiveisParaItemPersonalizado()`)
  — e o método de criação **revalida isso no servidor**
  (`criarItemPersonalizado()` faz `intersect()` com a lista permitida),
  já que os IDs marcados num `<select multiple>` vêm do cliente e não são
  confiáveis por si só.
- **Ligado ao ramo do bloco, não tem coluna própria de ramo**: o item fica
  "preso" ao ramo do `BlocoNovo` escolhido no momento da criação
  (`bloco_novo_id → eixo_id → ramo_id`). Se o jovem depois for promovido
  (`ramo_atual_id` muda), o item **desaparece automaticamente** da tela de
  progresso — não é uma regra escrita em código pra isso, é consequência
  de `ExibeProgressoDoJovem::getEixosNovos()` já filtrar
  `EixoNovo::where('ramo_id', $jovem->ramo_atual_id)`: os blocos do ramo
  antigo simplesmente param de ser buscados, e como o item personalizado
  só é exibido a partir do bloco a que pertence (não existe uma listagem
  independente "todos os itens do jovem X"), ele nunca mais é iterado nem
  aparece na UI. O registro em si continua no banco (não é apagado),
  "órfão" da UI até o jovem eventualmente voltar pra esse ramo.
- **Quem pode editar/excluir/confirmar/rejeitar depois de criado**:
  qualquer adulto com acesso a **pelo menos um** dos jovens vinculados ao
  item — não precisa ser quem criou (`ItemPersonalizadoPolicy`, mesmo
  espírito do resto do sistema: acesso é por equipe, não por "dono do
  registro").
- **Conta na cota de Ações Variáveis do bloco** igual a um item Variável
  do catálogo — `StatusProgressaoService::statusBloco()` soma os itens
  personalizados concluídos daquele jovem naquele bloco
  (`variaveis_concluidas_via_personalizado` no array de retorno).
- **Mesmo fluxo de aprovação** que os itens do catálogo: jovem "marca como
  feito" (`Portal\Progresso::solicitarItemPersonalizado`) → fica
  "Aguardando avaliação" → adulto confirma/rejeita
  (`VerProgresso::confirmarItemPersonalizado`/`rejeitarItemPersonalizado`).
  O portal revalida que o item pertence mesmo ao jovem logado antes de
  aceitar a solicitação (`abort_unless`, 403 se não pertencer).
- **UI**: aparece dentro do próprio acordeão do bloco (painel e portal),
  com uma badge "Personalizado" pra diferenciar do catálogo oficial. O
  painel tem um botão "+ Item personalizado" por bloco, que abre um modal
  simples (HTML puro, não usa o sistema de Actions do Filament) com
  descrição + seletor opcional de outros jovens.
- **Não tem interface de gerenciamento própria/global** — só existe no
  contexto da tela de progresso de um jovem específico (criar, ver,
  confirmar, excluir tudo a partir de lá). Se um dia precisar de uma visão
  "todos os itens personalizados de todas as equipes", isso ainda não
  existe.

## Performance: cache de `EquivalenciaCreditoService`/`StatusProgressaoService`

Renderizar a tela de progresso (painel ou portal) chama
`itemAntigoConcluido`/`itemNovoConcluido`/`statusBloco`/`statusCompetencia`
uma vez pra cada item/bloco/competência, e várias telas/métodos diferentes
(resumo, percentual, pendências, etapa, elegibilidade ao Reconhecimento)
recalculam os mesmos blocos de novo. Sem cache, isso virou um N+1 severo —
chegava a **2000+ queries** pra renderizar os 18 blocos do programa novo.

- Os dois serviços (`app/Services/EquivalenciaCreditoService.php`,
  `app/Services/StatusProgressaoService.php`) são registrados como
  **singleton** no container (`AppServiceProvider::register()`) e
  memoizam resultado por `(jovem_id, item/bloco/competência_id)` — reduziu
  pra ~270 queries no mesmo cenário (18 blocos, 90 itens).
- **Isso exige disciplina**: como o singleton vive pela duração da
  requisição inteira, qualquer código que grave uma mudança em `concluido`
  precisa chamar `app(StatusProgressaoService::class)->limparCache()`
  logo depois de salvar (já feito em `VerProgresso::toggleAntigo/
  toggleNovo/confirmarAntigo/confirmarNovo`) — senão, se a mesma
  requisição re-renderizar a página depois da mudança (é exatamente o que
  o Livewire faz depois de uma action), o resultado antigo fica em cache
  e mostra desatualizado até o próximo carregamento de página. Isso
  **já causou uma regressão de verdade** nos testes que mutam e
  reconferem no mesmo teste (`EquivalenciaCreditoServiceTest`,
  `StatusProgressaoServiceTest`) — os helpers desses testes
  (`marcarAntigo`/`marcarNovo`/`marcarConcluido`) recebem o serviço e
  chamam `limparCache()` de propósito.
- `rejeitarAntigo`/`rejeitarNovo`/`solicitarNovo` **não** precisam limpar
  cache — só mexem em `solicitado_pelo_jovem`/`solicitado_em`, que não
  entram no cálculo de conclusão.
- Também vale notar: `App\Livewire\Portal\Progresso::jovem()` memoiza o
  `Jovem` numa propriedade privada (não sincronizada pelo Livewire) — sem
  isso, o Jovem autenticado era buscado do banco mais de 100 vezes numa
  única renderização (`ExibeProgressoDoJovem` chama `$this->jovem()` em
  quase todo getter).
- Teste de regressão: `tests/Feature/Portal/ProgressoPerformanceTest.php`
  (teto de 500 queries pra 18 blocos — não é number exato, só trava se o
  N+1 voltar).

## Pendências conhecidas (ainda não resolvidas)

1. **Corte de 10% na etapa "Comprometimento" do Pioneiro (antigo)** — a
   tabela original de cortes listava um corte em 10% separado do de 50%
   ("Cidadania"), mas a regra especial detalhada só menciona o de 50%. Foi
   implementado tratando tudo abaixo de 50% como "Comprometimento" (sem
   distinguir um estágio intermediário nos 10%). Confirmar se está correto
   ou se falta um nome de etapa entre 10% e 50%.
2. Importação de dados reais de Lobinho, Escoteiro e Pioneiro (antigo, novo
   e equivalência) ainda não feita — só o Ramo Sênior tem dados completos
   importados até o momento. Modelos de planilha (.csv) disponíveis via
   botão na tela "Importar Planilha", que também tem o inverso: "Baixar
   Programa Antigo/Novo do Ramo (.csv)" exporta os itens já cadastrados
   do Ramo selecionado no mesmo formato de colunas do import (dá pra
   editar e reimportar, ou levar pra outro ambiente sem depender do
   arquivo original). É best-effort num sentido: a importação funde
   "Competência"+"Descrição da Competência" num só `descricao`, e
   "Item"+"Observação/Requisito" num só `descricao` do item (separados
   por `"\n\nObservação: "`) — a exportação tenta desfazer essa fusão
   (`ImportarPlanilha::separarObservacao()`), mas só funciona de verdade
   pra itens que vieram exatamente desse fluxo; um item editado à mão
   depois pode não ter mais esse padrão exato.
3. Não existe uma tela "todos os itens personalizados" (por equipe, por
   ramo, etc.) — hoje só dá pra ver/gerenciar um item personalizado a
   partir da tela de progresso do jovem específico a que ele pertence.

## Resolvido (não é mais pendência)

- Itens personalizados pendentes agora aparecem na lista detalhada de
  pendências (`StatusProgressaoService::pendenciasNovo()`'s
  `variaveis_pendentes`, usada no PDF e no detalhe do bloco), junto com os
  itens Variáveis do catálogo — antes só entravam no total agregado de
  `variaveis_concluidas`. Extraído em
  `itensPersonalizadosDoBloco()`/`itemPersonalizadoConcluido()`/
  `itensPersonalizadosPendentes()` (privados em `StatusProgressaoService`)
  pra reaproveitar a mesma query já usada em `calcularStatusBloco()`.
  `ItemPersonalizado` ganhou um pseudo-`codigo` (`Attribute` computado,
  `'PERS-'.$this->id`) só pra poder aparecer nas mesmas listas/PDF que
  `ItemAntigo`/`ItemNovo` (que têm `codigo` de catálogo de verdade) sem
  `@if` espalhado pelas views checando o tipo do item.
- Nome do Reconhecimento do Pioneiro (novo): confirmado como **"Insígnia de
  B-P"**, não "Escoteiro da Pátria" (que era um possível erro de
  transcrição do documento de origem).
- Cadastro de Equivalência em lote (1-1/N-1/1-N), enum `tipo_equivalencia`
  sem o valor `sem_equivalencia`, contadores de resumo (antigo e novo) na
  tela de Progresso, badge de etapa por item (Lobinho/Escoteiro), edição de
  Requisitos Complementares direto na tela de Progresso, coluna `etapa`
  agora capturada e validada na importação do programa antigo,
  CRUD de Usuários + edição do próprio perfil (`->profile()` do Filament).

## Deploy

- **Código PHP**: Hostinger (Git auto-deploy no hPanel, observando a branch
  `main`) — a cada push, a Hostinger só dá `git pull` sozinha. **O plano
  usado aqui não tem o campo "Deployment script" no Git do hPanel**
  (descoberto na prática — não é só falta de configurar, a opção não
  aparece), então nada roda automaticamente depois do pull: nem
  `composer install`, nem migration, nem cache. `deploy/
  hostinger-post-deploy.sh` guarda esses comandos, mas só é executado
  manualmente, via a action `.github/workflows/
  rodar-migrations-producao.yml` (aba Actions do GitHub → Run workflow),
  que SSHa no servidor e roda o script. Ou seja: depois de um push com
  migration nova, é preciso disparar essa action manualmente — não é
  automático.
- **Assets do Vite (`public/build`)**: a Hostinger não tem Node/npm no SSH,
  então o build não roda lá. Fica separado do fluxo de git: o workflow
  `.github/workflows/deploy-assets.yml` builda os assets no GitHub Actions
  (a cada push em `main`) e envia só a pasta `public/build` direto pro
  servidor via `rsync` sobre SSH, usando uma chave dedicada guardada nos
  Secrets do repositório (`HOSTINGER_SSH_KEY`, `HOSTINGER_SSH_HOST`,
  `HOSTINGER_SSH_PORT`, `HOSTINGER_SSH_USER`, `HOSTINGER_DEPLOY_PATH`) —
  a action manual de migrations reaproveita esses mesmos secrets
  (`HOSTINGER_DEPLOY_PATH` é a raiz da aplicação, não uma subpasta de
  assets). `public/build` continua fora do git (`.gitignore`) — nunca é
  commitado.
- Host/porta/usuário/caminho do servidor (dados sensíveis, repositório é
  público): ver `DEPLOY-PRIVADO.md` (local, fora do git) ou os Secrets do
  GitHub acima.
- `migrate --force` só aplica migrations que ainda não rodaram (checa a
  tabela `migrations`) — nunca dá `DROP`/recria tabela ou coluna
  existente. Mesmo assim, vale revisar migrations antes de mergear pra
  `main`, já que quem dispara a action de migrar é uma pessoa, não mais
  um gatilho automático de push.
