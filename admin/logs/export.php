<?php
require_once __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/../../src/Database.php';
require __DIR__ . '/../../src/Auth.php';

use App\Database;
use App\Auth;

if (!Auth::isAdmin()) { http_response_code(403); exit('Acesso negado.'); }

$action   = $_GET['action']    ?? '';
$entity   = $_GET['entity']    ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to']   ?? '';
$search   = $_GET['search']    ?? '';

$where = []; $params = [];
if ($action !== '') { $where[] = 'action = :action'; $params[':action'] = $action; }
if ($entity !== '') { $where[] = 'entity = :entity'; $params[':entity'] = $entity; }
if ($dateFrom)      { $where[] = 'DATE(created_at) >= :df'; $params[':df'] = $dateFrom; }
if ($dateTo)        { $where[] = 'DATE(created_at) <= :dt'; $params[':dt'] = $dateTo; }
if ($search !== '') { $where[] = 'description LIKE :s';    $params[':s']  = "%{$search}%"; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=activity-logs-' . date('Y-m-d') . '.csv');

$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF"); // BOM para Excel abrir com acentos corretos
fputcsv($out, ['ID', 'Data', 'Usuário', 'Papel', 'Ação', 'Entidade', 'ID Entidade', 'Descrição', 'IP', 'User Agent']);

$stmt = Database::conn()->prepare("SELECT * FROM activity_logs $whereSql ORDER BY created_at DESC");
$stmt->execute($params);

while ($row = $stmt->fetch()) {
    fputcsv($out, [
        $row['id'],
        $row['created_at'],
        $row['user_name'],
        $row['user_role'],
        $row['action'],
        $row['entity'],
        $row['entity_id'],
        $row['description'],
        $row['ip_address'],
        $row['user_agent'],
    ]);
}
fclose($out);