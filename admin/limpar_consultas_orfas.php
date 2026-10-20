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

// ============================================================
// CONSULTAS TRAVADAS EM "EM ATENDIMENTO"
// ============================================================
$horasLimite = 2;
$limite = date('Y-m-d H:i:s', time() - ($horasLimite * 3600));

$statusAtual = StatusConsulta::EM_ATENDIMENTO;
$st = $conn->prepare("
    SELECT c.id, c.usuario_id, c.medico_id, c.tipo, c.especialidade,
           c.data_hora, c.status,
           u.nome AS paciente_nome,
           m.nome AS medico_nome
    FROM consultas c
    LEFT JOIN usuarios u ON u.id = c.usuario_id
    LEFT JOIN medicos m ON m.id = c.medico_id
    WHERE c.status = ?
      AND c.data_hora < ?
    ORDER BY c.data_hora ASC
");
$st->bind_param('ss', $statusAtual, $limite);
$st->execute();
$travadas = $st->get_result()->fetch_all(MYSQLI_ASSOC);

// ============================================================
// AÇÃO: LIMPAR (confirmada)
// ============================================================
$limpas = 0;
if (isset($_GET['confirmar']) && $_GET['confirmar'] === '1' && $travadas) {
    $novoStatus = StatusConsulta::CANCELAR;
    $ids = array_column($travadas, 'id');

    $up = $conn->prepare("UPDATE consultas SET status = ? WHERE id = ?");
    foreach ($ids as $id) {
        $up->bind_param('si', $novoStatus, $id);
        $up->execute();
        $limpas++;
    }
    $up->close();

    ActivityLogger::log(
        action: 'consultas_orfas_limpas',
        entity: 'consulta',
        description: "Admin limpou {$limpas} consulta(s) órfã(s) em 'Em atendimento' há mais de {$horasLimite}h",
        metadata: [
            'ids'          => $ids,
            'horas_limite' => $horasLimite,
        ],
        tags: ['consulta', 'limpeza']
    );

    header("Location: limpar_consultas_orfas.php?ok={$limpas}");
    exit;
}

if (isset($_GET['ok'])) {
    $limpas = (int)$_GET['ok'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Limpar Consultas Órfãs - Admin LAC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>.border-gold { border-color: #E6D5B8; }</style>
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen">

    <!-- Header Admin -->
    <header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="../logo_lac.png" alt="LAC Centro Médico" class="h-10 w-auto object-contain">
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

        <!-- Cabeçalho da página -->
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-red-50 border border-red-200 flex items-center justify-center text-red-700 shrink-0">
                        <i class="fa-solid fa-broom text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-serif text-[#3E352E]">Limpar Consultas Órfãs</h1>
                        <p class="text-sm text-stone-500 mt-1 max-w-2xl">
                            Consultas que ficaram em <strong>"<?php echo h(StatusConsulta::EM_ATENDIMENTO); ?>"</strong>
                            há mais de <strong><?php echo $horasLimite; ?> horas</strong> (geralmente porque o médico
                            fechou o navegador sem finalizar). Serão marcadas como
                            <strong>"<?php echo h(StatusConsulta::label(StatusConsulta::CANCELAR)); ?>"</strong>.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Aviso de sucesso -->
        <?php if ($limpas > 0): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span><strong><?php echo $limpas; ?></strong> consulta(s) órfã(s) foram marcadas como canceladas.</span>
            </div>
        <?php endif; ?>

        <!-- Lista -->
        <section class="bg-white rounded-2xl border border-[#E6D5B8]/40 shadow-sm overflow-hidden">
            <?php if ($travadas): ?>
                <div class="px-6 py-4 border-b border-[#E6D5B8]/40 flex items-center justify-between">
                    <h2 class="text-lg font-serif text-[#3E352E] flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                        <?php echo count($travadas); ?> consulta(s) travada(s)
                    </h2>
                    <span class="text-xs text-stone-500">Mais antigas primeiro</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-[#F9F4EC] text-[#5A4A3A] text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 text-left">ID</th>
                                <th class="px-4 py-3 text-left">Paciente</th>
                                <th class="px-4 py-3 text-left">Médico</th>
                                <th class="px-4 py-3 text-left">Especialidade</th>
                                <th class="px-4 py-3 text-left">Tipo</th>
                                <th class="px-4 py-3 text-left">Início</th>
                                <th class="px-4 py-3 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <?php foreach ($travadas as $t):
                                $minutos = max(0, (int)floor((time() - strtotime($t['data_hora'])) / 60));
                                $horas   = floor($minutos / 60);
                                $durTxt  = $horas >= 1
                                    ? "{$horas}h " . ($minutos % 60) . "min"
                                    : "{$minutos} min";
                            ?>
                                <tr class="hover:bg-[#FAF9F6] transition">
                                    <td class="px-4 py-3 font-mono text-xs text-stone-500">#<?php echo (int)$t['id']; ?></td>
                                    <td class="px-4 py-3 font-medium text-stone-800">
                                        <?php echo htmlspecialchars($t['paciente_nome'] ?? '—'); ?>
                                    </td>
                                    <td class="px-4 py-3 text-stone-600">
                                        <?php echo htmlspecialchars($t['medico_nome'] ?? '— não assumida'); ?>
                                    </td>
                                    <td class="px-4 py-3 text-stone-600">
                                        <?php echo htmlspecialchars($t['especialidade'] ?? '—'); ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if ($t['tipo'] === 'pronto_atendimento'): ?>
                                            <span class="text-xs font-bold text-amber-800 bg-amber-100 border border-amber-300 px-2.5 py-1 rounded-full">
                                                LAC Atende
                                            </span>
                                        <?php else: ?>
                                            <span class="text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-2.5 py-1 rounded-full">
                                                Teleatendimento
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-stone-500 text-xs">
                                        <div><?php echo date('d/m/Y H:i', strtotime($t['data_hora'])); ?></div>
                                        <div class="text-red-600 font-medium">
                                            <i class="fa-regular fa-clock mr-1"></i><?php echo $durTxt; ?> atrás
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="../teleconsulta.php?consulta=<?php echo (int)$t['id']; ?>"
                                           class="inline-flex items-center gap-1 text-xs font-medium text-stone-700 hover:text-[#8C6D36] transition px-3 py-1.5 rounded-lg border border-stone-300 hover:border-[#8C6D36] hover:bg-[#F9F4EC]"
                                           title="Abrir a sala para inspecionar">
                                            <i class="fa-solid fa-eye"></i> Ver
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-[#E6D5B8]/40 bg-[#FAF9F6] flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-xs text-stone-500">
                        <i class="fa-solid fa-circle-info mr-1 text-[#8C6D36]"></i>
                        Ao confirmar, as consultas acima serão <strong>canceladas</strong>. Esta ação fica registrada no log de atividades.
                    </p>
                    <div class="flex gap-3">
                        <a href="dashboard.php"
                           class="px-5 py-2.5 rounded-xl text-sm font-medium text-stone-700 border border-stone-300 hover:bg-white transition">
                            Cancelar
                        </a>
                        <a href="?confirmar=1"
                           onclick="return confirm('Marcar <?php echo count($travadas); ?> consulta(s) como cancelada(s)?');"
                           class="px-5 py-2.5 rounded-xl text-sm font-medium bg-red-600 hover:bg-red-700 text-white transition shadow-sm">
                            <i class="fa-solid fa-broom mr-1"></i> Limpar <?php echo count($travadas); ?> consulta(s)
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-16 px-4">
                    <div class="w-20 h-20 rounded-3xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 text-3xl mx-auto mb-4">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <h2 class="text-xl font-serif text-[#3E352E] mb-2">Tudo em ordem!</h2>
                    <p class="text-stone-500 text-sm max-w-md mx-auto">
                        Nenhuma consulta órfã encontrada. Não há nada travado em
                        <strong>"<?php echo h(StatusConsulta::EM_ATENDIMENTO); ?>"</strong> há mais de
                        <strong><?php echo $horasLimite; ?> horas</strong>.
                    </p>
                    <a href="dashboard.php"
                       class="inline-flex items-center gap-2 mt-6 px-5 py-2.5 rounded-xl text-sm font-medium bg-[#3E352E] hover:bg-[#5A4A3A] text-white transition shadow-sm">
                        <i class="fa-solid fa-arrow-left"></i> Voltar ao Painel
                    </a>
                </div>
            <?php endif; ?>
        </section>

        <!-- Card informativo -->
        <section class="bg-[#F9F4EC] border border-[#E6D5B8] p-5 rounded-2xl flex items-start gap-3">
            <i class="fa-solid fa-circle-info text-[#8C6D36] mt-0.5"></i>
            <div class="text-xs text-[#5A4A3A] leading-relaxed">
                <strong class="block text-sm mb-1">Por que isso acontece?</strong>
                Quando o médico fecha o navegador <strong>sem clicar em "Finalizar consulta"</strong>, o status da consulta fica
                preso em <strong>"<?php echo h(StatusConsulta::EM_ATENDIMENTO); ?>"</strong>. O sistema considera órfãs as consultas
                nesse estado por mais de <strong><?php echo $horasLimite; ?> horas</strong>. Cancelá-las libera o registro e
                mantém o banco consistente.
            </div>
        </section>

    </main>
</body>
</html>