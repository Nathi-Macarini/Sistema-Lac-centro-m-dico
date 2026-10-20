<?php
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../conexao.php';

use App\ActivityLogger;
use App\LGPD;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function resp($dados, $codigo = 200) {
    if (ob_get_level() > 0) ob_end_clean();
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    resp(['erro' => 'não autenticado'], 401);
}

$uid  = (int)$_SESSION['usuario_id'];
$acao = $_REQUEST['acao'] ?? '';

// ============================================================
// EXPORTAR DADOS
// ============================================================
if ($acao === 'exportar') {
    try {
        $dados = LGPD::exportarDados($conn, $uid);

        ActivityLogger::log(
            action: 'lgpd_exportacao',
            entity: 'usuario',
            entityId: $uid,
            description: "Usuário {$_SESSION['nome_usuario']} exportou seus dados",
            tags: ['lgpd', 'direito_titular']
        );

        // Registra a solicitação como concluída automaticamente
        LGPD::criarSolicitacao($conn, $uid, 'acesso', 'Exportação via painel (concluída automaticamente)');

        $nome = 'meus-dados-' . date('Ymd-His') . '.json';
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nome . '"');
        echo json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    } catch (\Throwable $e) {
        resp(['erro' => 'Falha ao exportar: ' . $e->getMessage()], 500);
    }
}

// ============================================================
// SOLICITAR EXCLUSÃO / REVOGAÇÃO / etc.
// ============================================================
if ($acao === 'solicitar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo      = $_POST['tipo'] ?? '';
    $descricao = trim($_POST['descricao'] ?? '');

    if (!array_key_exists($tipo, LGPD::TIPOS)) {
        resp(['erro' => 'Tipo inválido'], 400);
    }

    if (LGPD::temPendente($conn, $uid, $tipo)) {
        resp(['erro' => 'Você já tem uma solicitação pendente deste tipo.'], 400);
    }

    try {
        $res = LGPD::criarSolicitacao($conn, $uid, $tipo, $descricao ?: null);

        ActivityLogger::log(
            action: 'lgpd_solicitacao_criada',
            entity: 'usuario',
            entityId: $uid,
            description: "Usuário {$_SESSION['nome_usuario']} solicitou: " . LGPD::TIPOS[$tipo],
            metadata: ['protocolo' => $res['protocolo'], 'tipo' => $tipo],
            tags: ['lgpd', 'solicitacao']
        );

        resp([
            'ok'        => true,
            'protocolo' => $res['protocolo'],
            'prazo'     => $res['prazo'],
        ]);
    } catch (\Throwable $e) {
        resp(['erro' => 'Falha ao registrar: ' . $e->getMessage()], 500);
    }
}

// ============================================================
// LISTAR SOLICITAÇÕES
// ============================================================
if ($acao === 'listar') {
    $lista = LGPD::listarSolicitacoes($conn, $uid);
    resp(['solicitacoes' => $lista]);
}

resp(['erro' => 'ação inválida'], 400);