<?php
/**
 * Monitor de expiração do certificado SSL/TLS.
 * 
 * Uso:
 *   php scripts/check_certificado.php --domain=app.laccentromedico.com.br
 * 
 * Cron recomendado (diário às 08h):
 *   0 8 * * * /usr/bin/php /var/www/lac/scripts/check_certificado.php
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../conexao.php';

use App\Certificado;
use App\LGPD;

$isCli = (php_sapi_name() === 'cli');
$opts  = $isCli ? getopt('', ['domain:', 'porta::']) : $_GET;

$domain = $opts['domain'] ?? null;
if (!$domain) {
    $domain = getenv('APP_DOMAIN') ?: null;
}

if (!$domain) {
    $msg = "Domínio não informado. Use --domain=app.laccentromedico.com.br";
    if ($isCli) { fwrite(STDERR, $msg . PHP_EOL); exit(1); }
    else { http_response_code(400); echo $msg; exit; }
}

$porta = (int)($opts['porta'] ?? 443);

// ============================================================
// COLETA
// ============================================================
$resultado = Certificado::coletar($domain, $porta);

if (!$resultado) {
    $msg = "Falha ao conectar em {$domain}:{$porta} ou obter certificado.";
    if ($isCli) { fwrite(STDERR, $msg . PHP_EOL); exit(2); }
    else { http_response_code(500); echo $msg; exit; }
}

// ============================================================
// SALVA + ALERTA
// ============================================================
Certificado::salvar($conn, $resultado);
Certificado::logarAlerta($resultado);

// ============================================================
// SAÍDA
// ============================================================
if ($isCli) {
    echo "=== VERIFICAÇÃO DE CERTIFICADO SSL ===\n\n";
    echo "Domínio:        {$resultado['dominio']}:{$resultado['porta']}\n";
    echo "Emissor:        {$resultado['emissor']} ({$resultado['organizacao']})\n";
    echo "Subject:        {$resultado['subject']}\n";
    echo "Válido de:      {$resultado['valido_de']}\n";
    echo "Válido até:     {$resultado['valido_ate']}\n";
    echo "Dias restantes: {$resultado['dias_restantes']}\n\n";

    if ($resultado['expirado']) {
        echo "❌ CERTIFICADO EXPIRADO!\n";
        exit(3);
    } elseif ($resultado['alerta']) {
        echo "⚠️  ATENÇÃO: expira em menos de 30 dias. Renove agora.\n";
        exit(1);
    } else {
        echo "✅ Certificado válido.\n";
        exit(0);
    }
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}