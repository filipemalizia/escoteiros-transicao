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
users                          — padrão Laravel/Filament

ramos                          — id, nome
  (seed fixo: Lobinho, Escoteiro, Sênior, Pioneiro)

jovens                         — id, nome, data_nascimento, ramo_atual_id (FK ramos)

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
                                  Bloco em Lote". Cuidado: não vincular o
                                  mesmo item antigo aqui E como equivalência
                                  item-a-item pro mesmo bloco — contaria em
                                  dobro.

-- Progresso do jovem --
progresso_antigo               — id, jovem_id, item_antigo_id, concluido,
                                  data_conclusao, registrado_por_id
progresso_novo                 — id, jovem_id, item_novo_id, concluido,
                                  data_conclusao, registrado_por_id

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

## Interface (Filament)

- Resources de CRUD: Jovem, Área de Desenvolvimento Antiga, Competência
  Antiga (+ Itens), Eixo Novo, Bloco Novo (+ Itens), Especialidade/Distintivo,
  Equivalência
- Página de Importação (.xlsx/.csv), por Ramo + Sistema, com mapeamento de
  colunas por nome de cabeçalho (não por posição)
- Página "Progresso do Jovem" (2 abas: Antigo/Novo), com checkboxes de
  marcação (grava `data_conclusao` + `registrado_por_id` automaticamente),
  badges de status por Competência/Bloco, indicador de item concluído "via
  equivalência", % de conclusão, etapa atual (nome certo do ramo), e banner
  de elegibilidade ao Reconhecimento
- Formulário de Jovem: seção "Requisitos Complementares" dinâmica por ramo
  (Toggle pra booleanos, número pra contadores)

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
   botão na tela "Importar Planilha".

## Resolvido (não é mais pendência)

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
  `main`) — a cada push, a Hostinger dá `git pull` e roda o script de
  pós-deploy configurado no próprio hPanel (Websites → Avançado → Git →
  Deployment script), com o conteúdo de `deploy/hostinger-post-deploy.sh`
  (composer install, migrate --force, cache clear/cache, storage:link).
- **Assets do Vite (`public/build`)**: a Hostinger não tem Node/npm no SSH,
  então o build não roda lá. Fica separado do fluxo de git: o workflow
  `.github/workflows/deploy-assets.yml` builda os assets no GitHub Actions
  (a cada push em `main`) e envia só a pasta `public/build` direto pro
  servidor via `rsync` sobre SSH (porta 65002, usuário `u569700691`,
  host `89.116.115.13`), usando uma chave dedicada guardada nos Secrets do
  repositório (`HOSTINGER_SSH_KEY`, `HOSTINGER_SSH_HOST`,
  `HOSTINGER_SSH_PORT`, `HOSTINGER_SSH_USER`, `HOSTINGER_DEPLOY_PATH`).
  `public/build` continua fora do git (`.gitignore`) — nunca é commitado.
- Servidor: `/home/u569700691/domains/transicao.marciliodias.org.br/public_html/`.
- Cuidado: `migrate --force` roda sem confirmação a cada push na `main` —
  revisar migrations antes de mergear pra lá.
