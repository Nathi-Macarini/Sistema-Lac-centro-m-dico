<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../conexao.php';

// 1. Verifica se é admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    http_response_code(403);
    die('Acesso negado.');
}

// 2. Pega o ID do backup
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    die('ID inválido.');
}

// 3. Busca o registro no banco
$st = $conn->prepare("SELECT arquivo, status FROM lgpd_backups WHERE id = ?");
$st->bind_param('i', $id);
$st->execute();
$backup = $st->get_result()->fetch_assoc();

if (!$backup || $backup['status'] !== 'sucesso') {
    http_response_code(404);
    die('Backup não encontrado ou não foi concluído com sucesso.');
}

// 4. Monta o caminho físico e valida se está dentro da pasta permitida
$caminhoRelativo = $backup['arquivo']; // ex: storage/backups/backup_manual_....sql
$caminhoFisico = realpath(__DIR__ . '/../../' . $caminhoRelativo);
$pastaPermitida = realpath(__DIR__ . '/../../storage/backups');

// Proteção contra path traversal: o arquivo real precisa estar dentro da pasta de backups
if ($caminhoFisico === false || $pastaPermitida === false || strpos($caminhoFisico, $pastaPermitida) !== 0) {
    http_response_code(403);
    die('Arquivo inválido ou fora da área permitida.');
}

if (!is_file($caminhoFisico)) {
    http_response_code(404);
    die('O arquivo físico não foi encontrado no servidor.');
}

// 5. Envia os headers para download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($caminhoFisico) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($caminhoFisico));

// 6. Limpa o buffer e envia o arquivo
if (ob_get_level()) {
    ob_end_clean();
}
readfile($caminhoFisico);
exit;