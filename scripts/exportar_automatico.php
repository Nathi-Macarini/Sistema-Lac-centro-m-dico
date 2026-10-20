<?php
/**
 * Script para gerar exportações automáticas.
 * 
 * Uso:
 *   php scripts/exportar_automatico.php --tipo=logs --periodo=semanal
 *   php scripts/exportar_automatico.php --tipo=consultas --periodo=mensal
 * 
 * Argumentos:
 *   --tipo      logs | consultas | usuarios | atendimentos | documentos
 *   --periodo   semanal | mensal | trimestral
 * 
 * O script cria um registro em `exportacoes` com gerado_por = NULL
 * (indica que foi gerado automaticamente).
 */

// Detecta se está rodando via CLI ou HTTP
$isCli = (php_sapi_name() === 'cli');

if ($isCli) {
    // Quando CLI, o cwd costuma ser a pasta do script; ajusta para a raiz
    chdir(dirname(__DIR__));
}

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../conexao.php';

use App\ExportadorCSV;

// ============================================================
// PARSE DE ARGUMENTOS
// ============================================================
$tipo    = null;
$periodo = null;

if ($isCli) {
    $opts = getopt('', ['tipo:', 'periodo:']);
    $tipo    = $opts['tipo']    ?? null;
    $periodo = $opts['periodo'] ?? null;
} else {
    // Permitir via HTTP também (útil para testes)
    $tipo    = $_GET['tipo']    ?? null;
    $periodo = $_GET['periodo'] ?? null;
}

if (!$tipo || !array_key_exists($tipo, ExportadorCSV::TIPOS)) {
    $msg = "Tipo inválido ou ausente. Use: --tipo=logs|consultas|usuarios|atendimentos|documentos";
    if ($isCli) { fwrite(STDERR, $msg . PHP_EOL); exit(1); }
    else { http_response_code(400); echo $msg; exit; }
}

if (!$periodo || !in_array($periodo, ['semanal', 'mensal', 'trimestral'], true)) {
    $msg = "Período inválido ou ausente. Use: --periodo=semanal|mensal|trimestral";
    if ($isCli) { fwrite(STDERR, $msg . PHP_EOL); exit(1); }
    else { http_response_code(400); echo $msg; exit; }
}

// ============================================================
// GERA
// ============================================================
try {
    [$ini, $fim] = ExportadorCSV::intervaloPeriodo($periodo);
    $resultado = ExportadorCSV::gerar($tipo, $ini, $fim);

    // Registra no banco (gerado_por = NULL → automático)
    $st = $conn->prepare("
        INSERT INTO exportacoes
            (tipo, periodo, periodo_inicio, periodo_fim, arquivo, tamanho_bytes, total_linhas, gerado_por)
        VALUES (?, ?, ?, ?, ?, ?, ?, NULL)
    ");
    $st->bind_param(
        'sssssii',
        $tipo, $periodo, $resultado['periodo_inicio'], $resultado['periodo_fim'],
        $resultado['arquivo'], $resultado['tamanho'], $resultado['linhas']
    );
    $st->execute();

    $linhaLog = sprintf(
        "[%s] Export automático gerado — tipo=%s periodo=%s linhas=%d arquivo=%s",
        date('Y-m-d H:i:s'),
        $tipo,
        $periodo,
        $resultado['linhas'],
        $resultado['arquivo']
    );

    if ($isCli) {
        echo $linhaLog . PHP_EOL;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'log' => $linhaLog, 'arquivo' => $resultado['arquivo']]);
    }

} catch (\Throwable $e) {
    $erro = "[ERRO] " . $e->getMessage();
    if ($isCli) {
        fwrite(STDERR, $erro . PHP_EOL);
        exit(1);
    } else {
        http_response_code(500);
        echo $erro;
    }
}