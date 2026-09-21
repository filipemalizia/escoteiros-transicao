<?php

namespace App\Console\Commands;

use App\Services\Importacao\ImportadorEspecialidadesService;
use Illuminate\Console\Command;

class ImportEspecialidades extends Command
{
    protected $signature = 'import:especialidades
        {caminho : Caminho para o especialidades.json gerado pelo paxtu-scraper}
        {--dry-run : Simula a importação inteira e desfaz no final. Nada é gravado, nenhuma imagem é baixada}';

    protected $description = 'Importa o catálogo de Especialidades extraído pelo paxtu-scraper';

    public function handle(ImportadorEspecialidadesService $servico): int
    {
        $caminho = $this->argument('caminho');
        $dryRun = (bool) $this->option('dry-run');

        if (! file_exists($caminho)) {
            $this->error("Arquivo não encontrado: {$caminho}");

            return self::FAILURE;
        }

        $catalogo = json_decode(file_get_contents($caminho), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('JSON inválido: '.json_last_error_msg());

            return self::FAILURE;
        }

        $total = count($catalogo['especialidades'] ?? []);

        if ($dryRun) {
            $this->warn('MODO SIMULAÇÃO (--dry-run): nada será gravado, nenhuma imagem será baixada.');
        }

        $this->info("Importando {$total} especialidades de {$caminho}...");

        $resumo = $servico->importar($catalogo, $dryRun);

        $this->newLine();
        $this->info('Criadas: '.$resumo->criadas);
        $this->info('Atualizadas: '.$resumo->atualizadas);
        $this->info('Adotadas de registros legados (mesmo nome, sem fonte_specialty_id): '.$resumo->adotadas);
        $this->info('Eixos novos criados: '.$resumo->eixosNovosCriados);

        if ($dryRun) {
            $this->info('Imagens que seriam baixadas: '.$resumo->imagensSimuladas);
        } else {
            $this->info('Imagens baixadas: '.$resumo->imagensBaixadas);
        }
        $this->info('Imagens já atualizadas (puladas): '.$resumo->imagensPuladas);

        if ($resumo->imagensComErro > 0) {
            $this->warn("Imagens com erro no download: {$resumo->imagensComErro} (ver logs)");
        }

        if ($resumo->adotadas > 0) {
            $this->newLine();
            $this->comment(
                "Atenção: {$resumo->adotadas} especialidade(s) já existiam (criadas pelo importador de planilha) ".
                'e foram enriquecidas em vez de duplicadas. Vale conferir manualmente algumas no Filament.',
            );
        }

        if ($resumo->erros) {
            $this->newLine();
            $this->error(count($resumo->erros).' especialidade(s) com erro:');
            $this->table(
                ['ID', 'Nome', 'Motivo'],
                array_map(fn (array $e) => [$e['id'], $e['nome'], $e['motivo']], $resumo->erros),
            );
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('Simulação concluída. Nada foi gravado; rode sem --dry-run pra aplicar de verdade.');
        }

        return $resumo->erros ? self::FAILURE : self::SUCCESS;
    }
}
