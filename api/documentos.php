<?php
// API de receitas e atestados.
//   POST acao=emitir            (só médico)  -> grava o documento
//   GET  acao=listar&consulta=  (médico ou paciente da consulta) -> documentos + status
//   GET  acao=listar&paciente=  (só médico)  -> todos os documentos do paciente
require __DIR__ . '/../conexao.php';
header('Content-Type: application/json; charset=utf-8');

function resp($dados, $codigo = 200) { http_response_code($codigo); echo json_encode($dados); exit; }

if (!isset($_SESSION['usuario_id']) && !souMedico()) resp(['erro' => 'não autenticado'], 401);

$acao = $_REQUEST['acao'] ?? '';

// ---------------------------------------------------------------- emitir
if ($acao === 'emitir' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!souMedico()) resp(['erro' => 'apenas médicos emitem documentos'], 403);
    $m = medicoAtual($conn);
    if (!$m) resp(['erro' => 'médico não encontrado'], 403);
    $mid = (int)$m['id'];

    $pid  = (int)($_POST['paciente_id'] ?? 0);
    $cid  = (int)($_POST['consulta_id'] ?? 0) ?: null;
    $tipo = $_POST['tipo'] ?? '';
    $conteudo = trim($_POST['conteudo'] ?? '');

    if (!in_array($tipo, ['receita', 'atestado'], true)) resp(['erro' => 'tipo inválido'], 400);
    if (mb_strlen($conteudo) > 5000) resp(['erro' => 'texto grande demais'], 400);

    $st = $conn->prepare("SELECT id FROM usuarios WHERE id = ? AND tipo = 'comum'");
    $st->bind_param('i', $pid);
    $st->execute();
    if (!$st->get_result()->fetch_assoc()) resp(['erro' => 'paciente não encontrado'], 404);

    if ($cid) {
        $st = $conn->prepare("SELECT id FROM consultas WHERE id = ? AND usuario_id = ? AND medico_id = ?");
        $st->bind_param('iii', $cid, $pid, $mid);
        $st->execute();
        if (!$st->get_result()->fetch_assoc()) resp(['erro' => 'consulta não pertence a você e a este paciente'], 403);
    }

    $dias = null; $cid10 = null;
    if ($tipo === 'receita') {
        if ($conteudo === '') resp(['erro' => 'Escreva os medicamentos e a posologia da receita.'], 400);
    } else {
        $dias = (int)($_POST['dias_afastamento'] ?? 0);
        if ($dias < 0 || $dias > 365) resp(['erro' => 'Dias de afastamento deve ficar entre 0 e 365.'], 400);
        if (!empty($_POST['incluir_cid'])) {
            $c = strtoupper(trim($_POST['cid10'] ?? ''));
            if ($c !== '') {
                if (!preg_match('/^[A-Z][0-9]{2}(\.[0-9A-Z]{1,4})?$/', $c)) resp(['erro' => 'CID-10 inválido (exemplo: J00 ou M54.5).'], 400);
                $cid10 = $c;
            }
        }
        if ($conteudo === '') $conteudo = null;
    }

    $codigo = strtoupper(bin2hex(random_bytes(6)));
    $st = $conn->prepare("INSERT INTO documentos (paciente_id, medico_id, consulta_id, tipo, conteudo, dias_afastamento, cid10, codigo) VALUES (?,?,?,?,?,?,?,?)");
    $st->bind_param('iiississ', $pid, $mid, $cid, $tipo, $conteudo, $dias, $cid10, $codigo);
    if (!$st->execute()) resp(['erro' => 'erro ao salvar: ' . $conn->error], 500);
    resp(['ok' => true, 'id' => (int)$conn->insert_id, 'tipo' => $tipo]);
}

// ---------------------------------------------------------------- listar
if ($acao === 'listar') {
    $extra = [];
    $docs  = [];

    if (isset($_GET['consulta'])) {
        $cid = (int)$_GET['consulta'];
        $acesso = acessoConsulta($conn, $cid);
        if (!$acesso) resp(['erro' => 'sem acesso a esta consulta'], 403);
        $c = $acesso['consulta'];
        $extra = ['status' => $c['status'], 'medico_nome' => $c['medico_nome'] ?? null];
        $st = $conn->prepare(
            "SELECT d.id, d.tipo, d.criado_em, d.dias_afastamento, d.conteudo, m.nome AS medico_nome
             FROM documentos d JOIN medicos m ON m.id = d.medico_id
             WHERE d.consulta_id = ? ORDER BY d.id DESC");
        $st->bind_param('i', $cid);
    } elseif (isset($_GET['paciente'])) {
        if (!souMedico()) resp(['erro' => 'apenas médicos'], 403);
        $pid = (int)$_GET['paciente'];
        $st = $conn->prepare(
            "SELECT d.id, d.tipo, d.criado_em, d.dias_afastamento, d.conteudo, m.nome AS medico_nome
             FROM documentos d JOIN medicos m ON m.id = d.medico_id
             WHERE d.paciente_id = ? ORDER BY d.id DESC LIMIT 50");
        $st->bind_param('i', $pid);
    } else {
        resp(['erro' => 'informe consulta ou paciente'], 400);
    }

    $st->execute();
    foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $d) {
        $resumo = $d['tipo'] === 'atestado'
            ? ($d['dias_afastamento'] ? $d['dias_afastamento'] . ' dia(s) de afastamento' : 'Atestado de comparecimento')
            : mb_substr(preg_replace('/\s+/', ' ', (string)$d['conteudo']), 0, 70);
        $docs[] = [
            'id'      => (int)$d['id'],
            'tipo'    => $d['tipo'],
            'data'    => date('d/m/Y H:i', strtotime($d['criado_em'])),
            'resumo'  => $resumo,
            'medico'  => $d['medico_nome'],
        ];
    }
    resp(['documentos' => $docs] + $extra);
}

resp(['erro' => 'ação inválida'], 400);