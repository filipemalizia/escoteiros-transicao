<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Pendências — {{ $jovem->nome }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1f2937;
        }

        h1 {
            font-size: 16px;
            margin-bottom: 2px;
        }

        h2 {
            font-size: 13px;
            margin-top: 22px;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #d1d5db;
        }

        h3 {
            font-size: 11px;
            margin-top: 12px;
            margin-bottom: 4px;
        }

        .subtitulo {
            color: #6b7280;
            margin-bottom: 12px;
        }

        .resumo {
            background-color: #f3f4f6;
            padding: 8px 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .resumo p {
            margin: 2px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th, td {
            text-align: left;
            padding: 4px 6px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        th {
            background-color: #f9fafb;
            font-size: 10px;
            text-transform: uppercase;
            color: #6b7280;
        }

        .codigo {
            font-family: monospace;
            color: #6b7280;
            white-space: nowrap;
        }

        .vazio {
            color: #6b7280;
            font-style: italic;
        }

        .badge {
            display: inline-block;
            font-size: 9px;
            padding: 1px 6px;
            border-radius: 3px;
            color: #fff;
        }

        .badge-obrigatoria {
            background-color: #dc2626;
        }

        .badge-variavel {
            background-color: #d97706;
        }
    </style>
</head>
<body>
    <h1>Pendências — {{ $jovem->nome }}</h1>
    <p class="subtitulo">
        Ramo: {{ $jovem->ramoAtual->nome }} &nbsp;|&nbsp;
        Gerado em {{ now()->format('d/m/Y H:i') }}
    </p>

    <h2>Programa Antigo</h2>
    <div class="resumo">
        <p>Itens concluídos: {{ $resumoAntigo['concluidos'] }} de {{ $resumoAntigo['total'] }} ({{ $resumoAntigo['percentual'] }}%)</p>
        <p>Itens faltantes: {{ $resumoAntigo['total'] - $resumoAntigo['concluidos'] }}</p>
    </div>

    @if (empty($pendenciasAntigo))
        <p class="vazio">Nenhum item pendente — programa antigo concluído.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Item</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pendenciasAntigo as $item)
                    <tr>
                        <td class="codigo">{{ $item->codigo }}</td>
                        <td>{{ $item->descricao }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Programa Novo</h2>
    <div class="resumo">
        <p>Blocos concluídos: {{ $resumoNovo['blocos_concluidos'] }} de {{ $resumoNovo['blocos_total'] }}</p>
        <p>Ações Obrigatórias concluídas: {{ $resumoNovo['obrigatorias_concluidas'] }} de {{ $resumoNovo['obrigatorias_total'] }}</p>
        <p>Ações Variáveis (dentro do mínimo exigido): {{ $resumoNovo['variaveis_atingidas'] }} de {{ $resumoNovo['variaveis_minimas_total'] }}</p>
    </div>

    @if (empty($pendenciasNovo))
        <p class="vazio">Nenhum bloco pendente — programa novo concluído.</p>
    @else
        @foreach ($pendenciasNovo as $pendencia)
            <h3>{{ $pendencia['bloco']->eixo->nome }} — {{ $pendencia['bloco']->titulo }} ({{ $pendencia['detalhe'] }})</h3>

            @if (! empty($pendencia['obrigatorias_pendentes']))
                <table>
                    <thead>
                        <tr>
                            <th style="width: 70px">Tipo</th>
                            <th>Código</th>
                            <th>Ação pendente</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendencia['obrigatorias_pendentes'] as $item)
                            <tr>
                                <td><span class="badge badge-obrigatoria">Obrigatória</span></td>
                                <td class="codigo">{{ $item->codigo }}</td>
                                <td>{{ $item->descricao }}</td>
                            </tr>
                        @endforeach
                        @foreach ($pendencia['variaveis_pendentes'] as $item)
                            <tr>
                                <td><span class="badge badge-variavel">Variável</span></td>
                                <td class="codigo">{{ $item->codigo }}</td>
                                <td>{{ $item->descricao }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @elseif (! empty($pendencia['variaveis_pendentes']))
                <table>
                    <thead>
                        <tr>
                            <th style="width: 70px">Tipo</th>
                            <th>Código</th>
                            <th>Ação pendente</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendencia['variaveis_pendentes'] as $item)
                            <tr>
                                <td><span class="badge badge-variavel">Variável</span></td>
                                <td class="codigo">{{ $item->codigo }}</td>
                                <td>{{ $item->descricao }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach
    @endif
</body>
</html>
