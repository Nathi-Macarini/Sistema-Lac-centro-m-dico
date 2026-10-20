<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Acesso negado.');
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); exit('ID inválido.'); }

$st = $conn->prepare("SELECT arquivo, tipo FROM exportacoes WHERE id = ?");
$st->bind_param('i', $id);
$st->execute();
$row = $st->get_result()->fetch_assoc();

if (!$row) { http_response_code(404); exit('Exportação não encontrada.'); }

$caminho = __DIR__ . '/../../' . $row['arquivo'];
if (!is_file($caminho)) {
    http_response_code(404);
    exit('Arquivo não encontrado no disco.');
}

// Nome amigável para download
$nomeDownload = basename($caminho);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nomeDownload . '"');
header('Content-Length: ' . filesize($caminho));
header('Cache-Control: no-store');

readfile($caminho);
exit;