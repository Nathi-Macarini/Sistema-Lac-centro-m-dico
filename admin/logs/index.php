<?php
require_once __DIR__ . '/../../bootstrap.php';

use App\Database;
use App\Auth;
use App\TiposAtendimento;

if (!Auth::isAdmin()) {
    header("Location: ../dashboard.php");
    exit;
}

$nomeAdmin = $_SESSION['nome_usuario'] ?? 'Administrador';
$pdo = Database::conn();

// Filtros
$action   = $_GET['action']    ?? '';
$entity   = $_GET['entity']    ?? '';
$tag      = $_GET['tag']       ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to']   ?? '';
$search   = $_GET['search']    ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 50;
$offset   = ($page - 1) * $perPage;

$where = []; $params = [];
if ($action !== '') { $where[] = 'action = :action'; $params[':action'] = $action; }
if ($entity !== '') { $where[] = 'entity = :entity'; $params[':entity'] = $entity; }
if ($tag !== '')    { $where[] = 'FIND_IN_SET(:tag, tags)'; $params[':tag'] = $tag; }
if ($dateFrom)      { $where[] = 'DATE(created_at) >= :df'; $params[':df'] = $dateFrom; }
if ($dateTo)        { $where[] = 'DATE(created_at) <= :dt'; $params[':dt'] = $dateTo; }
if ($search !== '') { $where[] = 'description LIKE :s';    $params[':s']  = "%{$search}%"; }

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, ceil($total / $perPage));

$stmt = $pdo->prepare("SELECT * FROM activity_logs $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Tags rápidas
$allTags = [
    TiposAtendimento::LAC_TELEATENDIMENTO,
    TiposAtendimento::LAC_ATENDE,
    TiposAtendimento::EM_ANDAMENTO,
    TiposAtendimento::FINALIZADO,
    TiposAtendimento::CANCELADO,
];

function badgeAction(string $action): string {
    $map = [
        'created'                 => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'updated'                 => 'bg-amber-100 text-amber-800 border-amber-200',
        'deleted'                 => 'bg-red-100 text-red-800 border-red-200',
        'login'                   => 'bg-blue-100 text-blue-800 border-blue-200',
        'logout'                  => 'bg-stone-100 text-stone-700 border-stone-200',
        'consulta_agendada'       => 'bg-blue-100 text-blue-800 border-blue-200',
        'consulta_cancelada'      => 'bg-red-100 text-red-800 border-red-200',
        'lac_atende_iniciado'     => 'bg-amber-100 text-amber-800 border-amber-200',
        'atendimento_iniciado'    => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'atendimento_finalizado'  => 'bg-stone-100 text-stone-700 border-stone-200',
    ];
    $cls = $map[$action] ?? 'bg-[#F9F4EC] text-[#8C6D36] border-[#E6D5B8]';
    return "<span class=\"inline-block px-2.5 py-1 rounded-full text-xs font-semibold border $cls\">" . htmlspecialchars($action) . "</span>";
}

function badgeTag(string $tag): string {
    $map = [
        TiposAtendimento::LAC_TELEATENDIMENTO => 'bg-[#8C6D36] text-white border-[#8C6D36]',
        TiposAtendimento::LAC_ATENDE          => 'bg-[#C5A059] text-white border-[#C5A059]',
        TiposAtendimento::AGENDADO            => 'bg-amber-100 text-amber-800 border-amber-300',
        TiposAtendimento::INICIADO            => 'bg-amber-100 text-amber-800 border-amber-300',
        TiposAtendimento::EM_ANDAMENTO        => 'bg-emerald-100 text-emerald-800 border-emerald-300',
        TiposAtendimento::FINALIZADO          => 'bg-stone-100 text-stone-700 border-stone-300',
        TiposAtendimento::CANCELADO           => 'bg-red-100 text-red-800 border-red-300',
    ];
    $cls = $map[$tag] ?? 'bg-[#F9F4EC] text-[#5A4A3A] border-[#E6D5B8]';
    return "<span class=\"inline-block px-2.5 py-1 rounded-full text-xs font-medium border $cls\">" . htmlspecialchars(TiposAtendimento::label($tag)) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log de Atividades - LAC Centro Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>.border-gold { border-color: #E6D5B8; }</style>
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen">

    <!-- Header Admin -->
    <header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="../../logo_lac.png" alt="LAC Centro Médico" class="h-10 w-auto object-contain">
                <span class="hidden sm:inline text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-2.5 py-1 rounded-full uppercase tracking-wider">
                    Painel Administrativo
                </span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="../dashboard.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition">
                    <i class="fa-solid fa-arrow-left"></i> <span class="hidden sm:inline">Voltar ao Painel</span>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Cabeçalho da página -->
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-serif text-[#3E352E] flex items-center gap-3">
                    <i class="fa-solid fa-clipboard-list text-[#8C6D36]"></i> Log de Atividades
                </h1>
                <p class="text-stone-600 text-sm mt-1">Acompanhe em tempo real as ações de usuários, médicos e atendimentos.</p>
            </div>
            <a href="export.php?<?php echo http_build_query($_GET); ?>"
               class="inline-flex items-center gap-2 bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-5 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                <i class="fa-solid fa-file-csv text-[#C5A059]"></i> Exportar CSV
            </a>
        </section>

        <!-- Filtros rápidos por tag -->
        <section class="bg-white p-5 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <div class="text-xs font-bold text-stone-600 uppercase tracking-wider mb-3">
                <i class="fa-solid fa-filter text-[#8C6D36]"></i> Filtros rápidos
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="?<?php echo http_build_query(array_merge($_GET, ['tag' => '', 'page' => 1])); ?>"
                   class="px-4 py-2 rounded-xl text-xs font-medium border transition
                          <?php echo $tag === '' ? 'bg-[#3E352E] text-white border-[#3E352E]' : 'bg-white text-stone-700 border-stone-300 hover:border-[#8C6D36] hover:text-[#8C6D36]'; ?>">
                    Todas
                </a>
                <?php foreach ($allTags as $t): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['tag' => $t, 'page' => 1])); ?>"
                       class="px-4 py-2 rounded-xl text-xs font-medium border transition
                              <?php echo $tag === $t ? 'bg-[#3E352E] text-white border-[#3E352E]' : 'bg-white text-stone-700 border-stone-300 hover:border-[#8C6D36] hover:text-[#8C6D36]'; ?>">
                        <?php echo TiposAtendimento::label($t); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Filtros detalhados -->
        <section class="bg-white p-5 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Buscar</label>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-stone-400 text-sm"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                               placeholder="Descrição, médico, paciente..."
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-stone-300 focus:outline-none focus:border-[#8C6D36] text-sm bg-[#FAF9F6]">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">De</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>"
                           class="w-full px-3 py-2.5 rounded-xl border border-stone-300 focus:outline-none focus:border-[#8C6D36] text-sm bg-[#FAF9F6]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Até</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>"
                           class="w-full px-3 py-2.5 rounded-xl border border-stone-300 focus:outline-none focus:border-[#8C6D36] text-sm bg-[#FAF9F6]">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-4 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                    <a href="index.php" class="bg-white hover:bg-stone-50 text-stone-700 border border-stone-300 px-3 py-2.5 rounded-xl text-sm transition" title="Limpar">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
                <?php if ($tag): ?>
                    <input type="hidden" name="tag" value="<?php echo htmlspecialchars($tag); ?>">
                <?php endif; ?>
            </form>
        </section>

        <!-- Tabela -->
        <section class="bg-white rounded-2xl border border-[#E6D5B8]/40 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-[#E6D5B8]/40 flex items-center justify-between">
                <h2 class="text-lg font-serif text-[#3E352E]">
                    <?php echo $total; ?> <?php echo $total === 1 ? 'registro' : 'registros'; ?>
                </h2>
                <span class="text-xs text-stone-500">Página <?php echo $page; ?> de <?php echo $pages; ?></span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#FAF9F6]">
                        <tr class="text-left text-xs font-bold text-stone-600 uppercase tracking-wider">
                            <th class="px-4 py-3">Data</th>
                            <th class="px-4 py-3">Usuário</th>
                            <th class="px-4 py-3">Ação</th>
                            <th class="px-4 py-3">Entidade</th>
                            <th class="px-4 py-3">Tags</th>
                            <th class="px-4 py-3">Descrição</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                    <?php if (!$logs): ?>
                        <tr>
                            <td colspan="7" class="text-center text-stone-500 py-12">
                                <i class="fa-regular fa-folder-open text-3xl text-[#C5A059] mb-2 block"></i>
                                Nenhum log encontrado com esses filtros.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-[#FAF9F6] transition">
                            <td class="px-4 py-3 text-stone-600 text-xs whitespace-nowrap">
                                <i class="fa-regular fa-clock text-[#8C6D36] mr-1"></i>
                                <?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-stone-800"><?php echo htmlspecialchars($log['user_name'] ?? '—'); ?></div>
                                <?php if ($log['user_role']): ?>
                                    <div class="text-xs text-stone-500"><?php echo htmlspecialchars($log['user_role']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3"><?php echo badgeAction($log['action']); ?></td>
                            <td class="px-4 py-3 text-stone-700 text-xs">
                                <?php if ($log['entity']): ?>
                                    <span class="font-medium"><?php echo htmlspecialchars($log['entity']); ?></span>
                                    <?php if ($log['entity_id']): ?>
                                        <span class="text-stone-400">#<?php echo (int)$log['entity_id']; ?></span>
                                    <?php endif; ?>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <?php if (!empty($log['tags'])): ?>
                                        <?php foreach (explode(',', $log['tags']) as $t): ?>
                                            <?php echo badgeTag(trim($t)); ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-stone-700 max-w-md">
                                <?php echo htmlspecialchars($log['description'] ?? ''); ?>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="show.php?id=<?php echo (int)$log['id']; ?>"
                                   class="inline-flex items-center gap-1 text-xs font-medium text-[#8C6D36] hover:text-[#5A4A3A] transition px-3 py-1.5 rounded-lg border border-[#E6D5B8] hover:bg-[#F9F4EC]">
                                    <i class="fa-regular fa-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Paginação -->
        <?php if ($pages > 1): ?>
            <nav class="flex justify-center">
                <ul class="inline-flex items-center gap-1 bg-white border border-[#E6D5B8]/40 rounded-xl p-1.5 shadow-sm">
                    <?php
                    $inicio = max(1, $page - 3);
                    $fim    = min($pages, $page + 3);
                    ?>

                    <?php if ($page > 1): ?>
                        <li>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                               class="px-3 py-1.5 rounded-lg text-sm text-stone-700 hover:bg-[#F9F4EC] transition">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = $inicio; $i <= $fim; $i++): ?>
                        <li>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                               class="px-3.5 py-1.5 rounded-lg text-sm font-medium transition
                                      <?php echo $i === $page ? 'bg-[#3E352E] text-white' : 'text-stone-700 hover:bg-[#F9F4EC]'; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $pages): ?>
                        <li>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                               class="px-3 py-1.5 rounded-lg text-sm text-stone-700 hover:bg-[#F9F4EC] transition">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

    </main>
</body>
</html>