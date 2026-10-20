<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../conexao.php';

use App\LGPD;

if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$nomeAdmin = $_SESSION['nome_usuario'] ?? 'Administrador';

// ============================================================
// ESTATÍSTICAS
// ============================================================
$stats = [
    'pendente'   => 0,
    'em_analise' => 0,
    'aprovada'   => 0,
    'rejeitada'  => 0,
    'concluida'  => 0,
    'total'      => 0,
];

$res = $conn->query("SELECT status, COUNT(*) AS n FROM lgpd_solicitacoes GROUP BY status");
while ($r = $res->fetch_assoc()) {
    $stats[$r['status']] = (int)$r['n'];
    $stats['total'] += (int)$r['n'];
}

// Atrasadas (prazo vencido e ainda pendente)
$res = $conn->query("
    SELECT COUNT(*) AS n FROM lgpd_solicitacoes
    WHERE status IN ('pendente', 'em_analise')
      AND prazo_resposta IS NOT NULL
      AND prazo_resposta < CURDATE()
");
$stats['atrasadas'] = (int)$res->fetch_assoc()['n'];

// ============================================================
// FILTROS
// ============================================================
$filtroTipo   = $_GET['tipo']   ?? '';
$filtroStatus = $_GET['status'] ?? '';
$busca        = trim($_GET['busca'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 25;
$offset       = ($page - 1) * $perPage;

$where = []; $params = []; $types = '';

if ($filtroTipo !== '')   { $where[] = 's.tipo = ?';   $params[] = $filtroTipo;   $types .= 's'; }
if ($filtroStatus !== '') { $where[] = 's.status = ?'; $params[] = $filtroStatus; $types .= 's'; }
if ($busca !== '')        {
    $where[] = '(s.protocolo LIKE ? OR u.nome LIKE ? OR u.email LIKE ?)';
    $params[] = "%{$busca}%"; $params[] = "%{$busca}%"; $params[] = "%{$busca}%";
    $types .= 'sss';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Total
$sql = "SELECT COUNT(*) FROM lgpd_solicitacoes s JOIN usuarios u ON u.id = s.usuario_id $whereSql";
$st = $conn->prepare($sql);
if ($params) $st->bind_param($types, ...$params);
$st->execute();
$total = (int)$st->get_result()->fetch_row()[0];
$pages = max(1, ceil($total / $perPage));

// Lista
$sql = "SELECT s.*, u.nome AS usuario_nome, u.email AS usuario_email
        FROM lgpd_solicitacoes s
        JOIN usuarios u ON u.id = s.usuario_id
        $whereSql
        ORDER BY 
            CASE s.status 
                WHEN 'pendente' THEN 1 
                WHEN 'em_analise' THEN 2 
                ELSE 3 
            END,
            s.prazo_resposta ASC,
            s.criado_em DESC
        LIMIT $perPage OFFSET $offset";
$st = $conn->prepare($sql);
if ($params) $st->bind_param($types, ...$params);
$st->execute();
$solicitacoes = $st->get_result()->fetch_all(MYSQLI_ASSOC);

function statusBadge(string $status): string {
    $map = [
        'pendente'   => 'bg-amber-50 text-amber-800 border-amber-200',
        'em_analise' => 'bg-blue-50 text-blue-800 border-blue-200',
        'aprovada'   => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        'rejeitada'  => 'bg-red-50 text-red-700 border-red-200',
        'concluida'  => 'bg-stone-100 text-stone-700 border-stone-200',
    ];
    $cls = $map[$status] ?? 'bg-stone-100 text-stone-700';
    $lbl = str_replace('_', ' ', ucfirst($status));
    return "<span class=\"inline-block text-xs font-semibold border $cls px-2.5 py-1 rounded-full\">" . htmlspecialchars($lbl) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitações LGPD - Admin LAC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen">

    <header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="../../logo_lac.png" alt="LAC" class="h-10 w-auto object-contain">
                <span class="hidden sm:inline text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-2.5 py-1 rounded-full uppercase tracking-wider">
                    Painel Administrativo
                </span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="../dashboard.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition">
                    <i class="fa-solid fa-arrow-left"></i> <span class="hidden sm:inline">Voltar ao Painel</span>
                </a>
                <span class="text-sm font-medium text-stone-700 hidden sm:inline"><?php echo htmlspecialchars($nomeAdmin); ?></span>
                <a href="../../logout.php" class="text-stone-400 hover:text-amber-700 transition" title="Sair">
                    <i class="fa-solid fa-right-from-bracket text-lg"></i>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Cabeçalho -->
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-serif text-[#3E352E] flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-[#8C6D36]"></i>
                    Solicitações LGPD
                </h1>
                <p class="text-sm text-stone-500 mt-1">
                    Gerencie os pedidos dos titulares de dados (Lei 13.709/2018).
                </p>
            </div>
            <div class="flex gap-2 flex-wrap">
    <a href="registro-operacoes.php" class="inline-flex items-center gap-2 bg-white hover:bg-stone-50 text-stone-700 border border-stone-300 px-4 py-2.5 rounded-xl text-sm font-medium transition">
        <i class="fa-solid fa-list-check"></i> ROPA
    </a>
    <a href="incidentes.php" class="inline-flex items-center gap-2 bg-white hover:bg-stone-50 text-stone-700 border border-stone-300 px-4 py-2.5 rounded-xl text-sm font-medium transition">
        <i class="fa-solid fa-triangle-exclamation"></i> Incidentes
    </a>
    <a href="config.php" class="inline-flex items-center gap-2 bg-white hover:bg-stone-50 text-stone-700 border border-stone-300 px-4 py-2.5 rounded-xl text-sm font-medium transition">
        <i class="fa-solid fa-gear"></i> Configurações
    </a>
    <a href="acessos.php" class="inline-flex items-center gap-2 bg-white hover:bg-stone-50 text-stone-700 border border-stone-300 px-4 py-2.5 rounded-xl text-sm font-medium transition">
        <i class="fa-solid fa-eye"></i> Auditoria
    </a>
    <a href="retencao.php" class="inline-flex items-center gap-2 bg-white hover:bg-stone-50 text-stone-700 border border-stone-300 px-4 py-2.5 rounded-xl text-sm font-medium transition">
    <i class="fa-solid fa-clock-rotate-left"></i> Retenção
</a>
<a href="seguranca.php" class="inline-flex items-center gap-2 bg-white hover:bg-stone-50 text-stone-700 border border-stone-300 px-4 py-2.5 rounded-xl text-sm font-medium transition">
    <i class="fa-solid fa-shield-halved"></i> Segurança
</a>
<a href="backups.php" class="inline-flex items-center gap-2 bg-white hover:bg-stone-50 text-stone-700 border border-stone-300 px-4 py-2.5 rounded-xl text-sm font-medium transition">
    <i class="fa-solid fa-database"></i> Backups
</a>
</div>
        </section>

        <!-- Estatísticas -->
        <section class="grid grid-cols-2 lg:grid-cols-6 gap-4">
            <div class="bg-white p-4 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Pendentes</span>
                    <i class="fa-solid fa-clock text-amber-600"></i>
                </div>
                <div class="text-3xl font-serif text-amber-700"><?php echo $stats['pendente']; ?></div>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Em análise</span>
                    <i class="fa-solid fa-magnifying-glass text-blue-600"></i>
                </div>
                <div class="text-3xl font-serif text-blue-700"><?php echo $stats['em_analise']; ?></div>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Aprovadas</span>
                    <i class="fa-solid fa-check text-emerald-600"></i>
                </div>
                <div class="text-3xl font-serif text-emerald-700"><?php echo $stats['aprovada']; ?></div>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Rejeitadas</span>
                    <i class="fa-solid fa-xmark text-red-600"></i>
                </div>
                <div class="text-3xl font-serif text-red-700"><?php echo $stats['rejeitada']; ?></div>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Concluídas</span>
                    <i class="fa-solid fa-circle-check text-stone-500"></i>
                </div>
                <div class="text-3xl font-serif text-stone-700"><?php echo $stats['concluida']; ?></div>
            </div>
            <div class="bg-white p-4 rounded-2xl border <?php echo $stats['atrasadas'] > 0 ? 'border-red-300 shadow-md' : 'border-[#E6D5B8]/40'; ?> shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Atrasadas</span>
                    <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
                </div>
                <div class="text-3xl font-serif text-red-700"><?php echo $stats['atrasadas']; ?></div>
            </div>
        </section>

        <!-- Filtros -->
        <section class="bg-white p-5 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Buscar</label>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-stone-400 text-sm"></i>
                        <input type="text" name="busca" value="<?php echo htmlspecialchars($busca); ?>"
                               placeholder="Protocolo, nome ou e-mail..."
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-stone-300 focus:outline-none focus:border-[#8C6D36] text-sm bg-[#FAF9F6]">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Tipo</label>
                    <select name="tipo" class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6]">
                        <option value="">Todos</option>
                        <?php foreach (LGPD::TIPOS as $k => $v): ?>
                            <option value="<?php echo htmlspecialchars($k); ?>" <?php echo $filtroTipo === $k ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($v); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-2">
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Status</label>
                        <select name="status" class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6]">
                            <option value="">Todos</option>
                            <?php foreach (['pendente','em_analise','aprovada','rejeitada','concluida'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $filtroStatus === $s ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(str_replace('_', ' ', $s)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="self-end bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-4 py-2.5 rounded-xl text-sm font-medium transition shadow-sm h-[42px]">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <a href="index.php" class="self-end bg-white hover:bg-stone-50 text-stone-700 border border-stone-300 px-3 py-2.5 rounded-xl text-sm transition h-[42px] flex items-center" title="Limpar">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>
        </section>

        <!-- Lista -->
        <section class="bg-white rounded-2xl border border-[#E6D5B8]/40 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-[#E6D5B8]/40 flex items-center justify-between">
                <h2 class="text-lg font-serif text-[#3E352E]">
                    <?php echo $total; ?> <?php echo $total === 1 ? 'solicitação' : 'solicitações'; ?>
                </h2>
                <span class="text-xs text-stone-500">Página <?php echo $page; ?> de <?php echo $pages; ?></span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F9F4EC] text-[#5A4A3A] text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left">Protocolo</th>
                            <th class="px-4 py-3 text-left">Tipo</th>
                            <th class="px-4 py-3 text-left">Usuário</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Prazo</th>
                            <th class="px-4 py-3 text-left">Criada em</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (!$solicitacoes): ?>
                            <tr>
                                <td colspan="7" class="text-center text-stone-500 py-12">
                                    <i class="fa-regular fa-folder-open text-3xl text-[#C5A059] mb-2 block"></i>
                                    Nenhuma solicitação encontrada.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($solicitacoes as $s):
                            $atrasada = in_array($s['status'], ['pendente','em_analise'], true)
                                     && !empty($s['prazo_resposta'])
                                     && $s['prazo_resposta'] < date('Y-m-d');
                        ?>
                            <tr class="hover:bg-[#FAF9F6] transition">
                                <td class="px-4 py-3">
                                    <span class="font-mono text-xs bg-white border border-stone-300 px-2 py-1 rounded">
                                        <?php echo htmlspecialchars($s['protocolo']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-stone-700 text-xs">
                                    <?php echo htmlspecialchars(LGPD::TIPOS[$s['tipo']] ?? $s['tipo']); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-stone-800"><?php echo htmlspecialchars($s['usuario_nome']); ?></div>
                                    <div class="text-xs text-stone-500"><?php echo htmlspecialchars($s['usuario_email']); ?></div>
                                </td>
                                <td class="px-4 py-3"><?php echo statusBadge($s['status']); ?></td>
                                <td class="px-4 py-3 text-xs">
                                    <?php if (!empty($s['prazo_resposta'])): ?>
                                        <div class="<?php echo $atrasada ? 'text-red-600 font-bold' : 'text-stone-600'; ?>">
                                            <?php echo date('d/m/Y', strtotime($s['prazo_resposta'])); ?>
                                            <?php if ($atrasada): ?>
                                                <i class="fa-solid fa-triangle-exclamation ml-1"></i>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-stone-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-xs text-stone-500">
                                    <?php echo date('d/m/Y H:i', strtotime($s['criado_em'])); ?>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="ver.php?id=<?php echo (int)$s['id']; ?>"
                                       class="inline-flex items-center gap-1 text-xs font-medium bg-[#3E352E] text-white px-3 py-1.5 rounded-lg hover:bg-[#5A4A3A] transition">
                                        <i class="fa-solid fa-arrow-right"></i> Analisar
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
                    <?php $inicio = max(1, $page - 3); $fim = min($pages, $page + 3); ?>
                    <?php if ($page > 1): ?>
                        <li><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="px-3 py-1.5 rounded-lg text-sm text-stone-700 hover:bg-[#F9F4EC]"><i class="fa-solid fa-chevron-left"></i></a></li>
                    <?php endif; ?>
                    <?php for ($i = $inicio; $i <= $fim; $i++): ?>
                        <li><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="px-3.5 py-1.5 rounded-lg text-sm font-medium <?php echo $i === $page ? 'bg-[#3E352E] text-white' : 'text-stone-700 hover:bg-[#F9F4EC]'; ?>"><?php echo $i; ?></a></li>
                    <?php endfor; ?>
                    <?php if ($page < $pages): ?>
                        <li><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="px-3 py-1.5 rounded-lg text-sm text-stone-700 hover:bg-[#F9F4EC]"><i class="fa-solid fa-chevron-right"></i></a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

    </main>
</body>
</html>