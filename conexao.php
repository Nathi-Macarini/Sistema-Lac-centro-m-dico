<?php
require_once __DIR__ . '/bootstrap.php';

// ============================================================
// Conexão e funções compartilhadas (admin, médico, teleconsulta)
// ============================================================
// Lê das variáveis de ambiente (Vercel, Railway, etc)
// Se não existirem, usa os padrões do XAMPP local
// ============================================================

$servidor   = getenv('DB_HOST') ?: 'localhost';
$usuario_db = getenv('DB_USER') ?: 'root';
$senha_db   = getenv('DB_PASS') ?: '';
$banco      = getenv('DB_NAME') ?: 'lac_centro_medico';
$porta      = (int)(getenv('DB_PORT') ?: 3306);

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($servidor, $usuario_db, $senha_db, $banco, $porta);

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/Sao_Paulo');

// Escapa dados para HTML (evita XSS)
function h($string) {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

// Confere a senha digitada com a guardada (aceita texto puro e hash do password_hash)
function conferirSenha($digitada, $guardada) {
    $guardada = (string)$guardada;
    if (strpos($guardada, '$2y$') === 0 || strpos($guardada, '$argon') === 0) {
        return password_verify($digitada, $guardada);
    }
    return $guardada !== '' && hash_equals($guardada, (string)$digitada);
}

// ---------------- Médico (tabela medicos) ----------------

function souMedico() {
    return ($_SESSION['tipo_usuario'] ?? '') === 'medico' && isset($_SESSION['medico_id']);
}

function medicoAtual($conn) {
    $id = (int)($_SESSION['medico_id'] ?? 0);
    $st = $conn->prepare("SELECT id, nome, crm, especialidade, email, telefone FROM medicos WHERE id = ?");
    $st->bind_param('i', $id);
    $st->execute();
    return $st->get_result()->fetch_assoc() ?: null;
}

function exigirMedico($conn, $raiz = '../') {
    if (!souMedico()) { header("Location: {$raiz}login.php"); exit; }
    $m = medicoAtual($conn);
    if (!$m) { session_destroy(); header("Location: {$raiz}login.php"); exit; }
    return $m;
}

// ---------------- Utilidades ----------------

function calcularIdade($dataNasc) {
    if (!$dataNasc) return null;
    try { return (new DateTime($dataNasc))->diff(new DateTime('today'))->y; }
    catch (Exception $e) { return null; }
}

function textoIdade($dataNasc) {
    $i = calcularIdade($dataNasc);
    return $i === null ? 'Idade não informada' : $i . ' ' . ($i === 1 ? 'ano' : 'anos');
}

function formatarCpf($cpf) {
    $c = preg_replace('/\D/', '', (string)$cpf);
    return strlen($c) === 11 ? substr($c,0,3).'.'.substr($c,3,3).'.'.substr($c,6,3).'-'.substr($c,9,2) : (string)$cpf;
}

// ---------------- Teleconsulta ----------------

function acessoConsulta($conn, $consultaId) {
    $st = $conn->prepare(
        "SELECT c.*, p.nome AS paciente_nome, p.data_nascimento AS paciente_nasc, m.nome AS medico_nome
         FROM consultas c
         JOIN usuarios p ON p.id = c.usuario_id
         LEFT JOIN medicos m ON m.id = c.medico_id
         WHERE c.id = ?");
    $st->bind_param('i', $consultaId);
    $st->execute();
    $c = $st->get_result()->fetch_assoc();
    if (!$c || $c['modalidade'] !== 'Vídeo') return null;

    if (souMedico()) {
        $m = medicoAtual($conn);
        if (!$m) return null;
        $mid = (int)$m['id'];
        if ($c['medico_id'] === null && strcasecmp(trim($c['especialidade']), trim($m['especialidade'])) === 0) {
            $u = $conn->prepare("UPDATE consultas SET medico_id = ? WHERE id = ? AND medico_id IS NULL");
            $u->bind_param('ii', $mid, $consultaId);
            $u->execute();
            $c['medico_id'] = $mid; $c['medico_nome'] = $m['nome'];
        }
        if ((int)$c['medico_id'] === $mid) return ['consulta' => $c, 'papel' => 'medico'];
        return null;
    }

    if (isset($_SESSION['usuario_id']) && (int)$c['usuario_id'] === (int)$_SESSION['usuario_id']) {
        return ['consulta' => $c, 'papel' => 'paciente'];
    }
    return null;
}

// ---------------- Verificação do banco ----------------

function verificarEsquema($conn) {
    $falta = [];
    $q = $conn->query("SHOW COLUMNS FROM consultas LIKE 'tipo'");
    if (!$q || $q->num_rows === 0) $falta[] = "coluna consultas.tipo";
    $q = $conn->query("SHOW COLUMNS FROM consultas LIKE 'medico_id'");
    if (!$q || $q->num_rows === 0) $falta[] = "coluna consultas.medico_id";
    $q = $conn->query("SHOW COLUMNS FROM medicos LIKE 'senha'");
    if (!$q || $q->num_rows === 0) $falta[] = "coluna medicos.senha";
    $q = $conn->query("SHOW TABLES LIKE 'documentos'");
    if (!$q || $q->num_rows === 0) $falta[] = "tabela documentos";
    if ($falta) {
        http_response_code(500);
        die("<p style='font-family:sans-serif;padding:2rem'><strong>Banco desatualizado.</strong> Faltando: "
            . h(implode(', ', $falta)) . ".<br>Abra o phpMyAdmin, selecione o banco <code>lac_centro_medico</code>, "
            . "aba <em>SQL</em>, e execute o arquivo <code>migracao.sql</code>.</p>");
    }
}

// ---------------- Pronto atendimento ----------------

const PA_VALIDADE_HORAS = 3;
const PA_ESPECIALIDADES = ['Clínica Geral', 'Pediatria'];