<?php
/**
 * Backup automático do banco de dados.
 * 
 * Uso:
 *   php scripts/backup_automatico.php
 * 
 * Cron (Linux — diário às 03h):
 *   0 3 * * * /usr/bin/php /var/www/lac/scripts/backup_automatico.php
 * 
 * Windows Task Scheduler:
 *   Programa: C:\xampp\php\php.exe
 *   Argumentos: C:\caminho\projeto\scripts\backup_automatico.php
 *   Frequência: diária às 03:00
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../conexao.php';

use App\Backup;
use App\LGPD;

$isCli = (php_sapi_name() === 'cli');

// Verifica se backup auto está ativo
$autoAtivo = LGPD::config($conn, 'backup_auto_ativo', '1') === '1';
if (!$autoAtivo) {
    $msg = "Backup automático desativado nas configurações.";
    if ($isCli) { echo $msg . PHP_EOL; exit(0); }
    else { http_response_code(200); echo $msg; exit; }
}

// Executa backup
$resultado = Backup::executar($conn, 0, 'automatico');

if ($isCli) {
    echo "=== BACKUP AUTOMÁTICO ===\n\n";
    echo "Status:  " . ($resultado['ok'] ? '✅ OK' : '❌ ERRO') . "\n";
    echo "Arquivo: " . ($resultado['arquivo'] ?? '—') . "\n";
    echo "Tamanho: " . Backup::formatarTamanho($resultado['tamanho']) . "\n";
    echo "Duração: " . $resultado['duracao'] . "s\n";
    if ($resultado['erro']) echo "Erro:    {$resultado['erro']}\n";
    exit($resultado['ok'] ? 0 : 1);
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}