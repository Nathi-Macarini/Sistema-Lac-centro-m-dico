<?php
// API de sinalização da teleconsulta (WebRTC): só troca as mensagens de conexão;
// o vídeo e o áudio vão direto entre médico e paciente.
require __DIR__ . '/../conexao.php';
header('Content-Type: application/json; charset=utf-8');

function resp($dados, $codigo = 200) { http_response_code($codigo); echo json_encode($dados); exit; }

if (!isset($_SESSION['usuario_id']) && !souMedico()) resp(['erro' => 'não autenticado'], 401);

$cid  = (int)($_REQUEST['consulta'] ?? 0);
$acao = $_REQUEST['acao'] ?? '';

$acesso = acessoConsulta($conn, $cid);
if (!$acesso) resp(['erro' => 'sem acesso a esta consulta'], 403);
$papel = $acesso['papel'];
$consulta = $acesso['consulta'];

if ($acao === 'entrar') {
    // Quando o médico entra numa consulta Agendada, ela vira "Em andamento"
    if ($papel === 'medico' && $consulta['status'] === 'Agendada') {
        $mid = (int)$consulta['medico_id'];
        $up = $conn->prepare("UPDATE consultas SET status = 'Em andamento' WHERE id = ? AND medico_id = ? AND status = 'Agendada'");
        $up->bind_param('ii', $cid, $mid);
        $up->execute();
    }
    $st = $conn->prepare("DELETE FROM sinalizacao WHERE consulta_id = ? AND (papel = ? OR tipo <> 'entrou')");
    $st->bind_param('is', $cid, $papel);
    $st->execute();
    $st = $conn->prepare("INSERT INTO sinalizacao (consulta_id, papel, tipo, payload) VALUES (?, ?, 'entrou', NULL)");
    $st->bind_param('is', $cid, $papel);
    $st->execute();
    resp(['ok' => true, 'papel' => $papel]);
}

if ($acao === 'enviar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? '';
    if (!in_array($tipo, ['offer', 'answer', 'candidate', 'saiu'], true)) resp(['erro' => 'tipo inválido'], 400);
    $payload = $_POST['payload'] ?? '';
    if (strlen($payload) > 200000) resp(['erro' => 'payload grande demais'], 400);
    $st = $conn->prepare("INSERT INTO sinalizacao (consulta_id, papel, tipo, payload) VALUES (?, ?, ?, ?)");
    $st->bind_param('isss', $cid, $papel, $tipo, $payload);
    $st->execute();
    resp(['ok' => true]);
}

if ($acao === 'receber') {
    $desde = (int)($_GET['desde'] ?? 0);
    $st = $conn->prepare("SELECT id, tipo, payload FROM sinalizacao WHERE consulta_id = ? AND papel <> ? AND id > ? ORDER BY id ASC LIMIT 100");
    $st->bind_param('isi', $cid, $papel, $desde);
    $st->execute();
    resp(['mensagens' => $st->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

if ($acao === 'finalizar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($papel !== 'medico') resp(['erro' => 'apenas o médico pode finalizar'], 403);
    $st = $conn->prepare("UPDATE consultas SET status = 'Realizada' WHERE id = ?");
    $st->bind_param('i', $cid);
    $st->execute();
    $st = $conn->prepare("DELETE FROM sinalizacao WHERE consulta_id = ?");
    $st->bind_param('i', $cid);
    $st->execute();
    resp(['ok' => true]);
}

resp(['erro' => 'ação inválida'], 400);