<?php
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../conexao.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function resp($dados, $codigo = 200) {
    if (ob_get_level() > 0) ob_end_clean();
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$inicio = microtime(true);
$checks = [];
$status = 'ok';

// ============================================================
// 1. Banco de dados
// ============================================================
try {
    $pdo = \App\Database::conn();
    $pdo->query("SELECT 1")->fetchColumn();
    $checks['banco'] = ['status' => 'ok', 'ms' => round((microtime(true) - $inicio) * 1000, 2)];
} catch (\Throwable $e) {
    $checks['banco'] = ['status' => 'erro'];
    $status = 'degraded';
}

// ============================================================
// 2. Storage (gravável)
// ============================================================
$storageDir = __DIR__ . '/../storage';
$checks['storage'] = [
    'status' => is_dir($storageDir) && is_writable($storageDir) ? 'ok' : 'erro',
];
if ($checks['storage']['status'] === 'erro') $status = 'degraded';

// ============================================================
// 3. HTTPS
// ============================================================
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443)
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

$checks['https'] = ['status' => $isHttps ? 'ok' : 'aviso'];

// ============================================================
// 4. Versão PHP
// ============================================================
$phpOk = version_compare(PHP_VERSION, '8.0.0', '>=');
$checks['php'] = [
    'status'  => $phpOk ? 'ok' : 'aviso',
    'version' => PHP_VERSION,
];

// ============================================================
// Resposta
// ============================================================
resp([
    'status'    => $status,
    'timestamp' => date('c'),
    'checks'    => $checks,
    'duracao_ms'=> round((microtime(true) - $inicio) * 1000, 2),
]);