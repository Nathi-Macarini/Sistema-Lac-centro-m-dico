<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$nomeAdmin = $_SESSION['nome_usuario'] ?? 'Administrador';

// ============================================================
// FILTROS
// ============================================================
$filtroMedico  = (int)($_GET['medico'] ?? 0);
$filtroPaciente = (int)($_GET['paciente'] ?? 0);
$dateFrom      = $_GET['date_from'] ?? '';
$dateTo        = $_GET['date_to'] ?? '';
$page          = max(1, (int)($_GET['page'] ?? 1));
$perPage       = 50;
$offset        = ($page - 1) * $perPage;

$where = []; $params = []; $types = '';

if ($filtroMedico > 0)   { $where[] = 'a.medico_id = ?';   $params[] = $filtroMedico;   $types .= 'i'; }
if ($filtroPaciente > 0) { $where[] = 'a.paciente_id = ?'; $params[] = $filtroPaciente; $types .= 'i'; }
if ($dateFrom) { $where[] = 'DATE(a.criado_em) >= ?'; $params[] = $dateFrom; $types .= 's'; }
if ($dateTo)   { $where[] = 'DATE(a.criado_em) <= ?'; $params[] = $dateTo;   $types .= 's'; }

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Total
$st = $conn->prepare("SELECT COUNT(*) FROM acessos_prontuario a $whereSql");
if ($params) $st->bind_param($types, ...$params);
$st->execute();
$total = (int)$st->get_result()->fetch_row()[0];
$pages = max(1, ceil($total / $perPage));

// Lista
$sql = "SELECT a.*,
               p.nome AS paciente_nome,
               m.nome AS medico_nome, m.crm,
               u.nome AS admin_nome
        FROM acessos_prontuario a
        LEFT JOIN usuarios p ON p.id = a.paciente_id
        LEFT JOIN medicos m ON m.id = a.medico_id
        LEFT JOIN usuarios u ON u.id = a.admin_id
        $whereSql
        ORDER BY a.criado_em DESC
        LIMIT $perPage OFFSET $offset";
$st = $conn->prepare($sql);
if ($params) $st->bind_param($types, ...$params);
$st->execute();
$acessos = $st->get_result()->fetch_all(MYSQLI_ASSOC);

// Listas para o filtro
$medicosLista = $conn->query("SELECT id, nome, crm FROM medicos ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoria de Acessos - LGPD</title>
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
                <a href="index.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition">
                    <i class="fa-solid fa-arrow-left"></i> <span class="hidden sm:inline">Voltar para as Solicitações LGPD</span>
                </a>
                <span class="text-sm font-medium text-stone-700 hidden sm:inline"><?php echo htmlspecialchars($nomeAdmin); ?></span>
                <a href="../../logout.php" class="text-stone-400 hover:text-amber-700 transition" title="Sair">
                    <i class="fa-solid fa-right-from-bracket text-lg"></i>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <h1 class="text-2xl font-serif text-[#3E352E] flex items-center gap-2">
                <i class="fa-solid fa-eye text-[#8C6D36]"></i>
                Auditoria de acesso a prontuários
            </h1>
            <p class="text-sm text-stone-500 mt-1">
                Registro de quem acessou qual prontuário, quando e de qual IP (LGPD Art. 37).
            </p>
        </section>

        <!-- Filtros -->
        <section class="bg-white p-5 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Médico</label>
                    <select name="medico" class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6]">
                        <option value="">Todos</option>
                        <?php foreach ($medicosLista as $m): ?>
                            <option value="<?php echo (int)$m['id']; ?>" <?php echo $filtroMedico === (int)$m['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m['nome'] . ' · ' . $m['crm']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">ID Paciente</label>
                    <input type="number" name="paciente" value="<?php echo $filtroPaciente ?: ''; ?>"
                           class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6]"
                           placeholder="Ex.: 3">
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">De</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>"
                           class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Até</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>"
                           class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6]">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-4 py-2.5 rounded-xl text-sm font-medium">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <a href="acessos.php" class="bg-white hover:bg-stone-50 text-stone-700 border border-stone-300 px-3 py-2.5 rounded-xl text-sm flex items-center">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>
        </section>

        <!-- Lista -->
        <section class="bg-white rounded-2xl border border-[#E6D5B8]/40 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-[#E6D5B8]/40 flex items-center justify-between">
                <h2 class="text-lg font-serif text-[#3E352E]">
                    <?php echo $total; ?> registros
                </h2>
                <span class="text-xs text-stone-500">Página <?php echo $page; ?> de <?php echo $pages; ?></span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F9F4EC] text-[#5A4A3A] text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left">Data/Hora</th>
                            <th class="px-4 py-3 text-left">Quem acessou</th>
                            <th class="px-4 py-3 text-left">Prontuário de</th>
                            <th class="px-4 py-3 text-left">Ação</th>
                            <th class="px-4 py-3 text-left">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (!$acessos): ?>
                            <tr><td colspan="5" class="text-center text-stone-500 py-12">
                                <i class="fa-regular fa-folder-open text-3xl text-[#C5A059] mb-2 block"></i>
                                Nenhum acesso registrado.
                            </td></tr>
                        <?php endif; ?>

                        <?php foreach ($acessos as $a): ?>
                            <tr class="hover:bg-[#FAF9F6] transition">
                                <td class="px-4 py-3 text-xs text-stone-600 whitespace-nowrap">
                                    <?php echo date('d/m/Y H:i:s', strtotime($a['criado_em'])); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if (!empty($a['medico_nome'])): ?>
                                        <div class="font-medium text-stone-800">
                                            <i class="fa-solid fa-user-doctor text-[#8C6D36] mr-1"></i>
                                            <?php echo htmlspecialchars($a['medico_nome']); ?>
                                        </div>
                                        <div class="text-xs text-stone-500">CRM <?php echo htmlspecialchars($a['crm']); ?></div>
                                    <?php elseif (!empty($a['admin_nome'])): ?>
                                        <div class="font-medium text-stone-800">
                                            <i class="fa-solid fa-shield-halved text-amber-600 mr-1"></i>
                                            <?php echo htmlspecialchars($a['admin_nome']); ?>
                                        </div>
                                        <div class="text-xs text-stone-500">Administrador</div>
                                    <?php else: ?>
                                        <span class="text-stone-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-stone-700">
                                    <?php echo htmlspecialchars($a['paciente_nome'] ?? '#' . $a['paciente_id']); ?>
                                    <span class="text-xs text-stone-400">#<?php echo (int)$a['paciente_id']; ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-xs font-medium bg-[#F9F4EC] border border-[#E6D5B8] text-[#8C6D36] px-2.5 py-1 rounded-full">
                                        <?php echo htmlspecialchars($a['acao']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-stone-500">
                                    <?php echo htmlspecialchars($a['ip_address'] ?? '—'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

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