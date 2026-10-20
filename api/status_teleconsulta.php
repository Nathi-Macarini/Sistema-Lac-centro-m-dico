<?php
// Silencia warnings que quebram o JSON
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../conexao.php';

// Headers anti-cache
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

function resp($dados, $codigo = 200) {
    if (ob_get_level() > 0) ob_end_clean();
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados);
    exit;
}

if (!isset($_SESSION['usuario_id']) && !souMedico()) {
    resp(['erro' => 'não autenticado'], 401);
}

$cid = (int)($_GET['consulta'] ?? 0);
if ($cid <= 0) {
    resp(['erro' => 'consulta inválida'], 400);
}

// Verifica acesso (sem UPDATE — só validação)
$acesso = acessoConsulta($conn, $cid);
if (!$acesso) {
    resp(['erro' => 'sem acesso'], 403);
}

// Busca SEMPRE fresco do banco
$st = $conn->prepare("
    SELECT c.id, c.status, c.medico_id,
           m.nome AS medico_nome,
           m.especialidade AS medico_especialidade
    FROM consultas c
    LEFT JOIN medicos m ON m.id = c.medico_id
    WHERE c.id = ?
");
$st->bind_param('i', $cid);
$st->execute();
$row = $st->get_result()->fetch_assoc();

if (!$row) {
    resp(['erro' => 'consulta não encontrada'], 404);
}

resp([
    'id'                   => (int)$row['id'],
    'status'               => $row['status'],
    'medico_id'            => $row['medico_id'] ? (int)$row['medico_id'] : null,
    'medico_nome'          => $row['medico_nome'],
    'medico_especialidade' => $row['medico_especialidade'],
    'ts'                   => time(),
]);