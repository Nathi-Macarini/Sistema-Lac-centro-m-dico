<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../conexao.php';

use App\ActivityLogger;
use App\TiposAtendimento;

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

    // 👇 LOG: só se realmente atualizou (evita log duplicado se apertar 2x)
    if ($st->affected_rows > 0) {
        $ehPA = ($consulta['tipo'] ?? '') === 'pronto_atendimento';
        $produto = $ehPA ? TiposAtendimento::LAC_ATENDE : TiposAtendimento::LAC_TELEATENDIMENTO;
        $nomeProduto = $ehPA ? 'LAC Atende' : 'LAC Teleatendimento';

        // Calcula duração desde o início (se tiver)
        $duracaoSeg = null;
        $inicioStr = $consulta['data_hora'] ?? null;
        if ($inicioStr) {
            $duracaoSeg = time() - strtotime($inicioStr);
            if ($duracaoSeg < 0) $duracaoSeg = 0;
        }

        // Busca nome do paciente para descrição mais rica
        $pacienteNome = $consulta['paciente_nome'] ?? null;
        if (!$pacienteNome && !empty($consulta['usuario_id'])) {
            $q = $conn->prepare("SELECT nome FROM usuarios WHERE id = ?");
            $q->bind_param('i', $consulta['usuario_id']);
            $q->execute();
            $pacienteNome = $q->get_result()->fetch_assoc()['nome'] ?? null;
        }

        ActivityLogger::log(
            action: 'atendimento_finalizado',
            entity: 'consulta',
            entityId: $cid,
            description: "{$nomeProduto}: Dr(a). {$_SESSION['nome_usuario']} finalizou atendimento com " . ($pacienteNome ?? 'paciente'),
            metadata: [
                'consulta_id'    => $cid,
                'medico_id'      => (int)($consulta['medico_id'] ?? 0),
                'medico_nome'    => $_SESSION['nome_usuario'] ?? null,
                'paciente_id'    => (int)($consulta['usuario_id'] ?? 0),
                'paciente_nome'  => $pacienteNome,
                'especialidade'  => $consulta['especialidade'] ?? null,
                'modalidade'     => $consulta['modalidade'] ?? null,
                'tipo_consulta'  => $consulta['tipo'] ?? null,
                'duracao_seg'    => $duracaoSeg,
                'finalizado_em'  => date('Y-m-d H:i:s'),
            ],
            tags: [
                $produto,
                TiposAtendimento::FINALIZADO,
            ]
        );
    }

    $st = $conn->prepare("DELETE FROM sinalizacao WHERE consulta_id = ?");
    $st->bind_param('i', $cid);
    $st->execute();
    resp(['ok' => true]);
}

resp(['erro' => 'ação inválida'], 400);