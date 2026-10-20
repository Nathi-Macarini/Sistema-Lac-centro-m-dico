<?php
// Silencia warnings que quebram o JSON
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/termo_texto.php';

use App\ActivityLogger;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function resp($dados, $codigo = 200) {
    if (ob_get_level() > 0) ob_end_clean();
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados);
    exit;
}

// Só paciente autenticado
if (!isset($_SESSION['usuario_id'])) {
    resp(['erro' => 'não autenticado'], 401);
}

$uid  = (int)$_SESSION['usuario_id'];
$cid  = (int)($_POST['consulta_id'] ?? 0);
$tipo = $_POST['tipo'] ?? 'teleconsulta_agendada';

if (!in_array($tipo, ['teleconsulta_agendada', 'teleconsulta_pa'], true)) {
    resp(['erro' => 'tipo inválido'], 400);
}

if ($cid > 0) {
    $st = $conn->prepare("SELECT id FROM consultas WHERE id = ? AND usuario_id = ?");
    $st->bind_param('ii', $cid, $uid);
    $st->execute();
    if (!$st->get_result()->fetch_assoc()) {
        resp(['erro' => 'consulta não pertence a você'], 403);
    }
}

$ok = registrarTermo($conn, $uid, $tipo, $cid ?: null);

if ($ok) {
    ActivityLogger::log(
        action: 'termo_aceito',
        entity: 'consulta',
        entityId: $cid ?: null,
        description: "Paciente {$_SESSION['nome_usuario']} aceitou o termo de consentimento ({$tipo} v" . TERMO_VERSAO . ")",
        metadata: [
            'consulta_id'  => $cid ?: null,
            'termo_tipo'   => $tipo,
            'termo_versao' => TERMO_VERSAO,
        ],
        tags: ['termo', 'consentimento']
    );

    resp(['ok' => true]);
}

resp(['erro' => 'falha ao registrar'], 500);