<?php
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../conexao.php';

use App\ActivityLogger;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function resp($dados, $codigo = 200) {
    if (ob_get_level() > 0) ob_end_clean();
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    resp(['erro' => 'método não permitido'], 405);
}

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body) || empty($body['aceitos']) || empty($body['acao'])) {
    resp(['erro' => 'payload inválido'], 400);
}

$acoesValidas = ['aceitou_todos', 'rejeitou_opcionais', 'personalizou', 'revogou'];
if (!in_array($body['acao'], $acoesValidas, true)) {
    resp(['erro' => 'ação inválida'], 400);
}

$aceitos  = json_encode($body['aceitos'], JSON_UNESCAPED_UNICODE);
$versao   = $body['versao'] ?? 'v1.0';
$uid      = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;
$sessao   = session_id() ?: null;

$ip = $_SERVER['REMOTE_ADDR'] ?? null;
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
}
$ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

$st = $conn->prepare("
    INSERT INTO cookies_consentimentos
        (usuario_id, session_id, aceitos, versao_politica, ip_address, user_agent, acao)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$st->bind_param(
    'issssss',
    $uid, $sessao, $aceitos, $versao, $ip, $ua, $body['acao']
);

if (!$st->execute()) {
    resp(['erro' => 'falha ao salvar'], 500);
}

// Log de auditoria
if (class_exists('App\\ActivityLogger')) {
    ActivityLogger::log(
        action: 'cookies_' . $body['acao'],
        entity: 'cookie',
        entityId: (int)$conn->insert_id,
        description: "Consentimento de cookies registrado: " . $body['acao'],
        metadata: [
            'aceitos' => $body['aceitos'],
            'versao'  => $versao,
        ],
        tags: ['lgpd', 'cookies']
    );
}

resp(['ok' => true, 'id' => (int)$conn->insert_id]);