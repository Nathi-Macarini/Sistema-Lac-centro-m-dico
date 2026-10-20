<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../conexao.php';

use App\ActivityLogger;
use App\StatusConsulta;

if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$nomeAdmin = $_SESSION['nome_usuario'] ?? 'Administrador';
$msg = ''; $msgTipo = '';

// ============================================================
// AÇÃO: TROCAR TIPO DE ATENDIMENTO (SUS <-> Particular)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'trocar_tipo') {
    $cid  = (int)($_POST['consulta_id'] ?? 0);
    $novo = $_POST['novo_tipo'] ?? '';

    if (!in_array($novo, ['particular', 'sus'], true)) {
        $msg = "Tipo inválido.";
        $msgTipo = 'erro';
    } else {
        // Busca o valor atual para o log
        $q = $conn->prepare("
            SELECT c.atendimento_tipo, c.especialidade, c.data_hora,
                   u.nome AS paciente_nome, m.nome AS medico_nome
            FROM consultas c
            LEFT JOIN usuarios u ON u.id = c.usuario_id
            LEFT JOIN medicos m ON m.id = c.medico_id
            WHERE c.id = ?
        ");
        $q->bind_param('i', $cid);
        $q->execute();
        $info = $q->get_result()->fetch_assoc();

        if (!$info) {
            $msg = "Consulta não encontrada.";
            $msgTipo = 'erro';
        } elseif ($info['atendimento_tipo'] === $novo) {
            $msg = "A consulta já está como " . ($novo === 'sus' ? 'SUS' : 'Particular') . ".";
            $msgTipo = 'erro';
        } else {
            $up = $conn->prepare("UPDATE consultas SET atendimento_tipo = ? WHERE id = ?");
            $up->bind_param('si', $novo, $cid);

            if ($up->execute() && $up->affected_rows > 0) {
                $msg = "Tipo de atendimento alterado para " . ($novo === 'sus' ? 'SUS' : 'Particular') . ".";
                $msgTipo = 'sucesso';

                ActivityLogger::log(
                    action: 'tipo_atendimento_alterado',
                    entity: 'consulta',
                    entityId: $cid,
                    description: "Admin alterou o tipo da consulta #{$cid} de " .
                                 ($info['atendimento_tipo'] === 'sus' ? 'SUS' : 'Particular') . " para " .
                                 ($novo === 'sus' ? 'SUS' : 'Particular'),
                    metadata: [
                        'consulta_id'      => $cid,
                        'paciente_nome'    => $info['paciente_nome'] ?? null,
                        'medico_nome'      => $info['medico_nome'] ?? null,
                        'tipo_anterior'    => $info['atendimento_tipo'],
                        'tipo_novo'        => $novo,
                    ],
                    tags: ['consulta', 'atendimento_tipo']
                );
            } else {
                $msg = "Não foi possível alterar.";
                $msgTipo = 'erro';
            }
        }
    }
}

// ============================================================
// FILTROS
// ============================================================
$filtroTipo   = $_GET['tipo']   ?? '';
$filtroStatus = $_GET['status'] ?? '';
$busca        = $_GET['busca']  ?? '';
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 30;
$offset       = ($page - 1) * $perPage;

$where = []; $params = [];

if ($filtroTipo !== '') {
    $where[] = 'c.atendimento_tipo = ?';
    $params[] = $filtroTipo;
}
if ($filtroStatus !== '') {
    $where[] = 'c.status = ?';
    $params[] = $filtroStatus;
}
if ($busca !== '') {
    $where[] = '(u.nome LIKE ? OR m.nome LIKE ? OR c.especialidade LIKE ?)';
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Total
$sqlCount = "SELECT COUNT(*) 
             FROM consultas c
             LEFT JOIN usuarios u ON u.id = c.usuario_id
             LEFT JOIN medicos m ON m.id = c.medico_id
             $whereSql";
$st = $conn->prepare($sqlCount);
if ($params) {
    $st->bind_param(str_repeat('s', count($params)), ...$params);
}
$st->execute();
$total = (int)$st->get_result()->fetch_column();
$pages = max(1, ceil($total / $perPage));

// Lista
$sqlList = "SELECT c.*, u.nome AS paciente_nome, m.nome AS medico_nome
            FROM consultas c
            LEFT JOIN usuarios u ON u.id = c.usuario_id
            LEFT JOIN medicos m ON m.id = c.medico_id
            $whereSql
            ORDER BY c.data_hora DESC
            LIMIT $perPage OFFSET $offset";
$st = $conn->prepare($sqlList);
if ($params) {
    $st->bind_param(str_repeat('s', count($params)), ...$params);
}
$st->execute();
$consultas = $st->get_result()->fetch_all(MYSQLI_ASSOC);

// Estatísticas rápidas (contadores topo)
$stats = [
    'particular' => (int)$conn->query("SELECT COUNT(*) FROM consultas WHERE atendimento_tipo = 'particular'")->fetch_row()[0],
    'sus'        => (int)$conn->query("SELECT COUNT(*) FROM consultas WHERE atendimento_tipo = 'sus'")->fetch_row()[0],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Consultas - Admin LAC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen">

    <header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="../logo_lac.png" alt="LAC" class="h-10 w-auto object-contain">
                <span class="hidden sm:inline text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-2.5 py-1 rounded-full uppercase tracking-wider">
                    Painel Administrativo
                </span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition">
                    <i class="fa-solid fa-arrow-left"></i> <span class="hidden sm:inline">Voltar ao Painel</span>
                </a>
                <span class="text-sm font-medium text-stone-700 hidden sm:inline"><?php echo htmlspecialchars($nomeAdmin); ?></span>
                <a href="../logout.php" class="text-stone-400 hover:text-amber-700 transition" title="Sair">
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
                    <i class="fa-solid fa-hand-holding-medical text-[#8C6D36]"></i>
                    Gerenciar Consultas
                </h1>
                <p class="text-sm text-stone-500 mt-1">
                    Altere o tipo de atendimento de cada consulta entre <strong>SUS</strong> e <strong>Particular</strong>.
                </p>
            </div>
        </section>

        <!-- Contadores -->
        <section class="grid grid-cols-2 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-10 h-10 rounded-xl bg-[#F9F4EC] border border-[#E6D5B8] flex items-center justify-center text-[#8C6D36]">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Particular</span>
                </div>
                <div class="text-3xl font-serif text-[#3E352E]"><?php echo $stats['particular']; ?></div>
                <div class="text-xs text-stone-500 mt-1">consultas particulares</div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-200 flex items-center justify-center text-blue-700">
                        <i class="fa-solid fa-hospital-user"></i>
                    </div>
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">SUS</span>
                </div>
                <div class="text-3xl font-serif text-blue-700"><?php echo $stats['sus']; ?></div>
                <div class="text-xs text-stone-500 mt-1">consultas via SUS</div>
            </div>
        </section>

        <!-- Mensagem de retorno -->
        <?php if ($msg): ?>
            <div class="<?php echo $msgTipo === 'sucesso' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'; ?> p-3.5 rounded-xl text-sm border flex items-start gap-2">
                <i class="fa-solid <?php echo $msgTipo === 'sucesso' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> mt-0.5"></i>
                <span><?php echo htmlspecialchars($msg); ?></span>
            </div>
        <?php endif; ?>

        <!-- Filtros -->
        <section class="bg-white p-5 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Buscar</label>
                    <input type="text" name="busca" value="<?php echo htmlspecialchars($busca); ?>"
                           placeholder="Paciente, médico, especialidade..."
                           class="w-full px-3 py-2.5 rounded-xl border border-stone-300 focus:outline-none focus:border-[#8C6D36] text-sm bg-[#FAF9F6]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Tipo</label>
                    <select name="tipo" class="w-full px-3 py-2.5 rounded-xl border border-stone-300 focus:outline-none focus:border-[#8C6D36] text-sm bg-[#FAF9F6]">
                        <option value="">Todos</option>
                        <option value="particular" <?php echo $filtroTipo === 'particular' ? 'selected' : ''; ?>>Particular</option>
                        <option value="sus" <?php echo $filtroTipo === 'sus' ? 'selected' : ''; ?>>SUS</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Status</label>
                    <select name="status" class="w-full px-3 py-2.5 rounded-xl border border-stone-300 focus:outline-none focus:border-[#8C6D36] text-sm bg-[#FAF9F6]">
                        <option value="">Todos</option>
                        <?php foreach (StatusConsulta::opcoes() as $v => $l): ?>
                            <option value="<?php echo htmlspecialchars($v); ?>" <?php echo $filtroStatus === $v ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($l); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-4 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                        <i class="fa-solid fa-filter mr-1"></i> Filtrar
                    </button>
                    <a href="gerenciar_consultas.php" class="bg-white hover:bg-stone-50 text-stone-700 border border-stone-300 px-3 py-2.5 rounded-xl text-sm transition" title="Limpar">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>
        </section>

        <!-- Lista -->
        <section class="bg-white rounded-2xl border border-[#E6D5B8]/40 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-[#E6D5B8]/40 flex items-center justify-between">
                <h2 class="text-lg font-serif text-[#3E352E]">
                    <?php echo $total; ?> <?php echo $total === 1 ? 'consulta' : 'consultas'; ?>
                </h2>
                <span class="text-xs text-stone-500">Página <?php echo $page; ?> de <?php echo $pages; ?></span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F9F4EC] text-[#5A4A3A] text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Paciente</th>
                            <th class="px-4 py-3 text-left">Médico</th>
                            <th class="px-4 py-3 text-left">Especialidade</th>
                            <th class="px-4 py-3 text-left">Data</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Atendimento</th>
                            <th class="px-4 py-3 text-right">Trocar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (!$consultas): ?>
                            <tr>
                                <td colspan="8" class="text-center text-stone-500 py-12">
                                    <i class="fa-regular fa-folder-open text-3xl text-[#C5A059] mb-2 block"></i>
                                    Nenhuma consulta encontrada com esses filtros.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($consultas as $c):
                            $tipoAtual = $c['atendimento_tipo'] ?? 'particular';
                            $isSUS = $tipoAtual === 'sus';
                        ?>
                            <tr class="hover:bg-[#FAF9F6] transition">
                                <td class="px-4 py-3 font-mono text-xs text-stone-500">#<?php echo (int)$c['id']; ?></td>
                                <td class="px-4 py-3 font-medium text-stone-800">
                                    <?php echo htmlspecialchars($c['paciente_nome'] ?? '—'); ?>
                                </td>
                                <td class="px-4 py-3 text-stone-600">
                                    <?php echo htmlspecialchars($c['medico_nome'] ?? '— aguardando'); ?>
                                </td>
                                <td class="px-4 py-3 text-stone-600"><?php echo htmlspecialchars($c['especialidade'] ?? '—'); ?></td>
                                <td class="px-4 py-3 text-stone-500 text-xs whitespace-nowrap">
                                    <?php echo date('d/m/Y H:i', strtotime($c['data_hora'])); ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-xs font-semibold border <?php echo StatusConsulta::badgeClasses($c['status']); ?> px-2.5 py-1 rounded-full">
                                        <?php echo htmlspecialchars(StatusConsulta::label($c['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ($isSUS): ?>
                                        <span class="inline-flex items-center gap-1 text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-1 rounded-full">
                                            <i class="fa-solid fa-hospital-user"></i> SUS
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 text-xs font-bold bg-[#F9F4EC] text-[#8C6D36] border border-[#E6D5B8] px-2.5 py-1 rounded-full">
                                            <i class="fa-solid fa-user"></i> Particular
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="acao" value="trocar_tipo">
                                        <input type="hidden" name="consulta_id" value="<?php echo (int)$c['id']; ?>">
                                        <input type="hidden" name="novo_tipo" value="<?php echo $isSUS ? 'particular' : 'sus'; ?>">
                                        <button type="submit"
                                                onclick="return confirm('Trocar esta consulta para <?php echo $isSUS ? 'Particular' : 'SUS'; ?>?');"
                                                class="inline-flex items-center gap-1 text-xs font-medium px-3 py-1.5 rounded-lg border transition
                                                       <?php echo $isSUS ? 'bg-[#F9F4EC] text-[#8C6D36] border-[#E6D5B8] hover:bg-[#E6D5B8]/40' : 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100'; ?>">
                                            <i class="fa-solid fa-right-left"></i>
                                            <?php echo $isSUS ? '→ Particular' : '→ SUS'; ?>
                                        </button>
                                    </form>
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