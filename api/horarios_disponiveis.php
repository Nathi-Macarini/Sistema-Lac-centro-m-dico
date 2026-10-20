<?php
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../conexao.php';

use App\StatusConsulta;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function resp($dados, $codigo = 200) {
    if (ob_get_level() > 0) ob_end_clean();
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    resp(['erro' => 'não autenticado'], 401);
}

$uid  = (int)$_SESSION['usuario_id'];
$esp  = trim($_GET['especialidade'] ?? '');
$data = trim($_GET['data'] ?? '');

// Validações
if ($esp === '') {
    resp(['erro' => 'informe a especialidade'], 400);
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    resp(['erro' => 'data inválida'], 400);
}

// Não permite datas no passado
$dt = DateTime::createFromFormat('Y-m-d', $data);
if (!$dt) resp(['erro' => 'data inválida'], 400);

$hoje = new DateTime('today');
if ($dt < $hoje) {
    resp(['ok' => true, 'slots' => [], 'motivo' => 'data_passada']);
}

// Domingo bloqueado
if ((int)$dt->format('w') === 0) {
    resp(['ok' => true, 'slots' => [], 'motivo' => 'domingo']);
}

// ============================================================
// Médicos ativos da especialidade
// ============================================================
$st = $conn->prepare("
    SELECT COUNT(*) AS n
    FROM medicos
    WHERE especialidade = ? AND ativo = 1
");
$st->bind_param('s', $esp);
$st->execute();
$totalMedicos = (int)$st->get_result()->fetch_assoc()['n'];

if ($totalMedicos === 0) {
    resp(['ok' => true, 'slots' => [], 'motivo' => 'sem_medico']);
}

// ============================================================
// Gera slots de 30 em 30 min (08:00 às 18:00)
// ============================================================
$slots = [];
$inicio = new DateTime($data . ' 08:00:00');
$fim    = new DateTime($data . ' 18:00:00');
$agora  = new DateTime();

while ($inicio < $fim) {
    $horaSlot = $inicio->format('H:i');
    $dataHora = $inicio->format('Y-m-d H:i:s');

    // Se for hoje e o slot já passou, pula
    if ($dt->format('Y-m-d') === $agora->format('Y-m-d') && $inicio < $agora) {
        $inicio->modify('+30 minutes');
        continue;
    }

    // -------- Médicos ocupados nesse slot --------
    $st = $conn->prepare("
        SELECT COUNT(DISTINCT medico_id) AS n
        FROM consultas
        WHERE especialidade = ?
          AND data_hora = ?
          AND medico_id IS NOT NULL
          AND status NOT IN (?, ?)
    ");
    $statusCancel = StatusConsulta::CANCELAR;
    $statusRemarc = StatusConsulta::REMARCAR;
    $st->bind_param('ssss', $esp, $dataHora, $statusCancel, $statusRemarc);
    $st->execute();
    $ocupados = (int)$st->get_result()->fetch_assoc()['n'];

    // -------- Paciente já tem consulta nesse horário? --------
    $st = $conn->prepare("
        SELECT id FROM consultas
        WHERE usuario_id = ?
          AND data_hora = ?
          AND status NOT IN (?, ?)
        LIMIT 1
    ");
    $st->bind_param('isss', $uid, $dataHora, $statusCancel, $statusRemarc);
    $st->execute();
    $euOcupado = (bool)$st->get_result()->fetch_assoc();

    $disponivel = ($ocupados < $totalMedicos) && !$euOcupado;
    $motivo     = $euOcupado ? 'voce_ja_tem' : ($ocupados >= $totalMedicos ? 'lotado' : null);

    $slots[] = [
        'hora'       => $horaSlot,
        'disponivel' => $disponivel,
        'motivo'     => $motivo,
    ];

    $inicio->modify('+30 minutes');
}

resp([
    'ok'            => true,
    'especialidade' => $esp,
    'data'          => $data,
    'total_medicos' => $totalMedicos,
    'slots'         => $slots,
]);