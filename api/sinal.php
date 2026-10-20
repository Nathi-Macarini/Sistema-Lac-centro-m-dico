<?php
// Silencia warnings que quebram o JSON
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../conexao.php';

use App\ActivityLogger;
use App\TiposAtendimento;
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

// ============================================================
// AUTENTICAÇÃO
// ============================================================
if (!isset($_SESSION['usuario_id']) && !souMedico()) {
    resp(['erro' => 'não autenticado'], 401);
}

$cid  = (int)($_REQUEST['consulta'] ?? 0);
$acao = $_REQUEST['acao'] ?? '';

// ============================================================
// VERIFICA ACESSO À CONSULTA
// ============================================================
$acesso = acessoConsulta($conn, $cid);
if (!$acesso) {
    resp(['erro' => 'sem acesso a esta consulta'], 403);
}
$papel    = $acesso['papel'];
$consulta = $acesso['consulta'];

// ============================================================
// AÇÃO: ENTRAR NA SALA
// ============================================================
if ($acao === 'entrar') {
    $mid = (int)($consulta['medico_id'] ?? 0);

    if ($papel === 'medico') {
        // 1) Se ainda não foi associado, associa agora
        if ($mid === 0) {
            $m = medicoAtual($conn);
            if ($m) {
                $mid = (int)$m['id'];
                $up = $conn->prepare("UPDATE consultas SET medico_id = ? WHERE id = ? AND medico_id IS NULL");
                $up->bind_param('ii', $mid, $cid);
                $up->execute();
            }
        }

        // 2) Atualiza o status para "Em atendimento" se aplicável
        $statusIniciais = [
            StatusConsulta::AGENDADA,
            StatusConsulta::CONFIRMADA,
            StatusConsulta::REAGENDADA,
            StatusConsulta::PRESENTE,
        ];

        if (in_array($consulta['status'], $statusIniciais, true) && $mid > 0) {
            $novo = StatusConsulta::EM_ATENDIMENTO;
            $up = $conn->prepare("UPDATE consultas SET status = ? WHERE id = ? AND medico_id = ?");
            $up->bind_param('sii', $novo, $cid, $mid);
            $up->execute();

            if ($up->affected_rows > 0) {
                $ehPA = ($consulta['tipo'] ?? '') === 'pronto_atendimento';
                $produto     = $ehPA ? TiposAtendimento::LAC_ATENDE : TiposAtendimento::LAC_TELEATENDIMENTO;
                $nomeProduto = $ehPA ? 'LAC Atende' : 'LAC Teleatendimento';

                $pacienteNome = $consulta['paciente_nome'] ?? null;
                if (!$pacienteNome && !empty($consulta['usuario_id'])) {
                    $q = $conn->prepare("SELECT nome FROM usuarios WHERE id = ?");
                    $q->bind_param('i', $consulta['usuario_id']);
                    $q->execute();
                    $pacienteNome = $q->get_result()->fetch_assoc()['nome'] ?? null;
                }

                ActivityLogger::log(
                    action: 'atendimento_iniciado',
                    entity: 'consulta',
                    entityId: $cid,
                    description: "{$nomeProduto}: Dr(a). {$_SESSION['nome_usuario']} iniciou atendimento com " . ($pacienteNome ?? 'paciente') . " — status: " . StatusConsulta::EM_ATENDIMENTO,
                    metadata: [
                        'consulta_id'     => $cid,
                        'medico_id'       => $mid,
                        'medico_nome'     => $_SESSION['nome_usuario'] ?? null,
                        'paciente_id'     => (int)($consulta['usuario_id'] ?? 0),
                        'paciente_nome'   => $pacienteNome,
                        'especialidade'   => $consulta['especialidade'] ?? null,
                        'modalidade'      => $consulta['modalidade'] ?? null,
                        'tipo_consulta'   => $consulta['tipo'] ?? null,
                        'iniciado_em'     => date('Y-m-d H:i:s'),
                        'status_anterior' => $consulta['status'],
                        'status_novo'     => StatusConsulta::EM_ATENDIMENTO,
                    ],
                    tags: [
                        $produto,
                        TiposAtendimento::INICIADO,
                        StatusConsulta::tag(StatusConsulta::EM_ATENDIMENTO),
                    ]
                );
            }
        }
    }

    // Limpa sinalização antiga e marca "entrou"
    $st = $conn->prepare("DELETE FROM sinalizacao WHERE consulta_id = ? AND (papel = ? OR tipo <> 'entrou')");
    $st->bind_param('is', $cid, $papel);
    $st->execute();

    $st = $conn->prepare("INSERT INTO sinalizacao (consulta_id, papel, tipo, payload) VALUES (?, ?, 'entrou', NULL)");
    $st->bind_param('is', $cid, $papel);
    $st->execute();

    resp(['ok' => true, 'papel' => $papel, 'medico_id' => $mid]);
}

// ============================================================
// AÇÃO: ENVIAR MENSAGEM DE SINALIZAÇÃO (WebRTC)
// ============================================================
if ($acao === 'enviar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? '';
    if (!in_array($tipo, ['offer', 'answer', 'candidate', 'saiu'], true)) {
        resp(['erro' => 'tipo inválido'], 400);
    }
    $payload = $_POST['payload'] ?? '';
    if (strlen($payload) > 200000) {
        resp(['erro' => 'payload grande demais'], 400);
    }
    $st = $conn->prepare("INSERT INTO sinalizacao (consulta_id, papel, tipo, payload) VALUES (?, ?, ?, ?)");
    $st->bind_param('isss', $cid, $papel, $tipo, $payload);
    $st->execute();
    resp(['ok' => true]);
}

// ============================================================
// AÇÃO: RECEBER MENSAGENS DE SINALIZAÇÃO
// ============================================================
if ($acao === 'receber') {
    $desde = (int)($_GET['desde'] ?? 0);
    $st = $conn->prepare("SELECT id, tipo, payload FROM sinalizacao WHERE consulta_id = ? AND papel <> ? AND id > ? ORDER BY id ASC LIMIT 100");
    $st->bind_param('isi', $cid, $papel, $desde);
    $st->execute();
    resp(['mensagens' => $st->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

// ============================================================
// AÇÃO: SAIR DA SALA (médico fecha a janela sem finalizar)
// ============================================================
if ($acao === 'sair_sala' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($papel === 'medico' && $consulta['status'] === StatusConsulta::EM_ATENDIMENTO) {
        $mid  = (int)$consulta['medico_id'];
        $novo = StatusConsulta::PRESENTE;
        $statusAtual = StatusConsulta::EM_ATENDIMENTO;

        $up = $conn->prepare("UPDATE consultas SET status = ? WHERE id = ? AND medico_id = ? AND status = ?");
        $up->bind_param('sisi', $novo, $cid, $mid, $statusAtual);
        $up->execute();

        if ($up->affected_rows > 0) {
            ActivityLogger::log(
                action: 'atendimento_pausado',
                entity: 'consulta',
                entityId: $cid,
                description: "Dr(a). {$_SESSION['nome_usuario']} saiu da sala — consulta em espera (Presente) para reabrir depois",
                metadata: [
                    'consulta_id' => $cid,
                    'medico_id'   => $mid,
                    'status_novo' => StatusConsulta::PRESENTE,
                ],
                tags: [
                    TiposAtendimento::LAC_TELEATENDIMENTO,
                    StatusConsulta::tag(StatusConsulta::PRESENTE),
                ]
            );
        }
    }

    // Limpa a sinalização WebRTC (mantém entrada do paciente para reentrada)
    $st = $conn->prepare("
        DELETE FROM sinalizacao
        WHERE consulta_id = ?
          AND (
              tipo IN ('offer', 'answer', 'candidate', 'saiu')
              OR (papel = ? AND tipo = 'entrou')
          )
    ");
    $st->bind_param('is', $cid, $papel);
    $st->execute();

    // 👇 NOVO: avisa a outra parte que este papel saiu da sala
    $st = $conn->prepare("
        INSERT INTO sinalizacao (consulta_id, papel, tipo, payload)
        VALUES (?, ?, 'saiu', NULL)
    ");
    $st->bind_param('is', $cid, $papel);
    $st->execute();

    resp(['ok' => true, 'novoStatus' => StatusConsulta::PRESENTE]);
}

// ============================================================
// AÇÃO: FINALIZAR CONSULTA
// ============================================================
if ($acao === 'finalizar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($papel !== 'medico') {
        resp(['erro' => 'apenas o médico pode finalizar'], 403);
    }

    $statusAtual = $consulta['status'] ?? '';

    if ($statusAtual === StatusConsulta::PACIENTE_ATENDIDO) {
        resp(['ok' => true, 'aviso' => 'consulta já estava finalizada']);
    }

    $statusFinalizaveis = [
        StatusConsulta::AGENDADA,
        StatusConsulta::CONFIRMADA,
        StatusConsulta::REAGENDADA,
        StatusConsulta::PRESENTE,
        StatusConsulta::EM_ATENDIMENTO,
    ];

    $up = $conn->prepare(
        "UPDATE consultas SET status = ? WHERE id = ? AND status IN (" .
        StatusConsulta::sqlIn($statusFinalizaveis) .
        ")"
    );
    $novoStatus = StatusConsulta::PACIENTE_ATENDIDO;
    $up->bind_param('si', $novoStatus, $cid);
    $up->execute();

    $linhasAfetadas = $up->affected_rows;
    $erroMySQL      = $up->error;

    if ($linhasAfetadas > 0) {
        $ehPA = ($consulta['tipo'] ?? '') === 'pronto_atendimento';
        $produto     = $ehPA ? TiposAtendimento::LAC_ATENDE : TiposAtendimento::LAC_TELEATENDIMENTO;
        $nomeProduto = $ehPA ? 'LAC Atende' : 'LAC Teleatendimento';

        $duracaoSeg = null;
        $inicioStr  = $consulta['data_hora'] ?? null;
        if ($inicioStr) {
            $duracaoSeg = time() - strtotime($inicioStr);
            if ($duracaoSeg < 0) $duracaoSeg = 0;
        }

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
            description: "{$nomeProduto}: Dr(a). {$_SESSION['nome_usuario']} finalizou atendimento com " . ($pacienteNome ?? 'paciente') . " — status: Paciente atendido",
            metadata: [
                'consulta_id'     => $cid,
                'medico_id'       => (int)($consulta['medico_id'] ?? 0),
                'medico_nome'     => $_SESSION['nome_usuario'] ?? null,
                'paciente_id'     => (int)($consulta['usuario_id'] ?? 0),
                'paciente_nome'   => $pacienteNome,
                'especialidade'   => $consulta['especialidade'] ?? null,
                'modalidade'      => $consulta['modalidade'] ?? null,
                'tipo_consulta'   => $consulta['tipo'] ?? null,
                'duracao_seg'     => $duracaoSeg,
                'finalizado_em'   => date('Y-m-d H:i:s'),
                'status_anterior' => $statusAtual,
                'status_novo'     => StatusConsulta::PACIENTE_ATENDIDO,
            ],
            tags: [
                $produto,
                TiposAtendimento::FINALIZADO,
                StatusConsulta::tag(StatusConsulta::PACIENTE_ATENDIDO),
            ]
        );
    }

    $st = $conn->prepare("DELETE FROM sinalizacao WHERE consulta_id = ?");
    $st->bind_param('i', $cid);
    $st->execute();

    resp([
        'ok'             => true,
        'linhasAfetadas' => $linhasAfetadas,
        'statusAnterior' => $statusAtual,
        'novoStatus'     => $linhasAfetadas > 0 ? $novoStatus : $statusAtual,
        'erroMySQL'      => $erroMySQL ?: null,
    ]);
}

resp(['erro' => 'ação inválida'], 400);