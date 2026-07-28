<?php

namespace App\Services\Importacao;

use RuntimeException;

/**
 * Sinaliza que uma linha específica da planilha deve ser ignorada
 * (ex.: código duplicado), sem abortar a importação inteira.
 */
class ImportLinhaException extends RuntimeException {}
