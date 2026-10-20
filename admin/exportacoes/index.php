<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../conexao.php';

use App\ExportadorCSV;
use App\ExportadorAutomatico;

// ============================================================
// AUTENTICAÇÃO (antes de qualquer coisa)
// ============================================================
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$nomeAdmin = $_SESSION['nome_usuario'] ?? 'Administrador';
$uid       = (int)$_SESSION['usuario_id'];
$msg       = '';
$msgTipo   = '';

// ============================================================
// VERIFICAÇÃO AUTOMÁTICA (roda no máximo 1x por hora)
// ============================================================
try {
    if (ExportadorAutomatico::deveVerificar(60)) {
        ExportadorAutomatico::verificarEgerar();
    }
} catch (\Throwable $e) {
    error_log('[ExportadorAutomatico] ' . $e->getMessage());
}

// ============================================================
// AÇÃO: VERIFICAR AGORA (força)
// ============================================================
if (isset($_GET['verificar_agora'])) {
    try {
        $res = ExportadorAutomatico::verificarEgerar();
        $n = count($res['gerados']);
        header("Location: index.php?auto_ok={$n}");
        exit;
    } catch (\Throwable $e) {
        $msg = "Erro ao verificar: " . $e->getMessage();
        $msgTipo = 'erro';
    }
}

// ============================================================
// AÇÃO: GERAR EXPORT (manual)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'gerar') {
    $tipo    = $_POST['tipo'] ?? '';
    $periodo = $_POST['periodo'] ?? 'semanal';

    if (!array_key_exists($tipo, ExportadorCSV::TIPOS)) {
        $msg = "Tipo inválido.";
        $msgTipo = 'erro';
    } else {
        if ($periodo === 'personalizado') {
            $ini = $_POST['data_ini'] ?? '';
            $fim = $_POST['data_fim'] ?? '';
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ini) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fim)) {
                $msg = "Datas inválidas.";
                $msgTipo = 'erro';
            } elseif ($ini > $fim) {
                $msg = "A data inicial não pode ser maior que a final.";
                $msgTipo = 'erro';
            }
        } else {
            [$ini, $fim] = ExportadorCSV::intervaloPeriodo($periodo);
        }

        if ($msg === '') {
            try {
                $resultado = ExportadorCSV::gerar($tipo, $ini, $fim);

                $st = $conn->prepare("
                    INSERT INTO exportacoes
                        (tipo, periodo, periodo_inicio, periodo_fim, arquivo, tamanho_bytes, total_linhas, gerado_por)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $st->bind_param(
                    'sssssiii',
                    $tipo, $periodo, $resultado['periodo_inicio'], $resultado['periodo_fim'],
                    $resultado['arquivo'], $resultado['tamanho'], $resultado['linhas'], $uid
                );
                $st->execute();

                $msg = "CSV gerado com sucesso! {$resultado['linhas']} linha(s).";
                $msgTipo = 'sucesso';
            } catch (\Throwable $e) {
                $msg = "Erro ao gerar: " . $e->getMessage();
                $msgTipo = 'erro';
            }
        }
    }
}

// ============================================================
// AÇÃO: EXCLUIR EXPORT
// ============================================================
if (isset($_GET['excluir'])) {
    $idDel = (int)$_GET['excluir'];
    $st = $conn->prepare("SELECT arquivo FROM exportacoes WHERE id = ?");
    $st->bind_param('i', $idDel);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();

    if ($row) {
        $caminho = __DIR__ . '/../../' . $row['arquivo'];
        if (is_file($caminho)) @unlink($caminho);

        $st = $conn->prepare("DELETE FROM exportacoes WHERE id = ?");
        $st->bind_param('i', $idDel);
        $st->execute();
    }

    header("Location: index.php?excluido=1");
    exit;
}

// ============================================================
// STATUS DAS PRÓXIMAS EXPORTAÇÕES (auto)
// ============================================================
$statusAuto = ExportadorAutomatico::status();

// ============================================================
// LISTAGEM
// ============================================================
$filtroTipo = $_GET['tipo'] ?? '';
$where = []; $params = [];
if ($filtroTipo !== '') {
    $where[] = 'e.tipo = ?';
    $params[] = $filtroTipo;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT e.*, u.nome AS gerado_nome
        FROM exportacoes e
        LEFT JOIN usuarios u ON u.id = e.gerado_por
        $whereSql
        ORDER BY e.gerado_em DESC
        LIMIT 200";
$st = $conn->prepare($sql);
if ($params) {
    $st->bind_param(str_repeat('s', count($params)), ...$params);
}
$st->execute();
$exportacoes = $st->get_result()->fetch_all(MYSQLI_ASSOC);

function fmtBytes(int $b): string {
    if ($b < 1024) return $b . ' B';
    if ($b < 1048576) return round($b / 1024, 1) . ' KB';
    return round($b / 1048576, 2) . ' MB';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exportações CSV - Admin LAC</title>
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
                    <i class="fa-solid fa-arrow-left"></i> Voltar ao Painel
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
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <h1 class="text-2xl font-serif text-[#3E352E] flex items-center gap-2">
                <i class="fa-solid fa-file-csv text-[#8C6D36]"></i>
                Exportações CSV
            </h1>
            <p class="text-sm text-stone-500 mt-1">
                Relatórios em CSV para auditoria, LGPD e acompanhamento. Arquivos ficam disponíveis por 90 dias.
            </p>
        </section>

        <!-- Mensagens -->
        <?php if ($msg): ?>
            <div class="<?php echo $msgTipo === 'sucesso' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'; ?> p-3.5 rounded-xl text-sm border flex items-start gap-2">
                <i class="fa-solid <?php echo $msgTipo === 'sucesso' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> mt-0.5"></i>
                <span><?php echo htmlspecialchars($msg); ?></span>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['excluido'])): ?>
            <div class="bg-amber-50 text-amber-800 border-amber-200 p-3.5 rounded-xl text-sm border flex items-start gap-2">
                <i class="fa-solid fa-circle-check mt-0.5"></i>
                <span>Exportação excluída.</span>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['auto_ok'])): ?>
            <div class="bg-blue-50 text-blue-800 border-blue-200 p-3.5 rounded-xl text-sm border flex items-start gap-2">
                <i class="fa-solid fa-rotate mt-0.5"></i>
                <span>
                    Verificação concluída.
                    <?php if ((int)$_GET['auto_ok'] > 0): ?>
                        <strong><?php echo (int)$_GET['auto_ok']; ?> exportação(ões) gerada(s) automaticamente.</strong>
                    <?php else: ?>
                        Nenhuma exportação estava atrasada.
                    <?php endif; ?>
                </span>
            </div>
        <?php endif; ?>

        <!-- ============================================================ -->
        <!-- PAINEL: PRÓXIMAS EXPORTAÇÕES AUTOMÁTICAS                     -->
        <!-- ============================================================ -->
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h2 class="text-lg font-serif text-[#3E352E] flex items-center gap-2">
                        <i class="fa-solid fa-robot text-[#8C6D36]"></i>
                        Exportações automáticas
                    </h2>
                    <p class="text-xs text-stone-500 mt-1">
                        O sistema gera automaticamente quando o painel é acessado (verificação a cada 1 hora).
                    </p>
                </div>
                <a href="?verificar_agora=1"
                   class="inline-flex items-center gap-2 bg-[#8C6D36] hover:bg-[#755a2c] text-white px-4 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                    <i class="fa-solid fa-rotate"></i> Verificar agora
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <?php foreach ($statusAuto as $s):
                    $atrasado = $s['nunca_gerado'] || ($s['dias_restantes'] !== null && $s['dias_restantes'] <= 0);
                    $proximo  = $s['dias_restantes'] !== null && $s['dias_restantes'] > 0;
                ?>
                    <div class="border rounded-xl p-4 <?php echo $atrasado ? 'border-amber-300 bg-amber-50/40' : 'border-stone-200 bg-[#FAF9F6]'; ?>">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-stone-500 uppercase tracking-wider">
                                    <?php echo htmlspecialchars($s['tipo_label']); ?>
                                </div>
                                <div class="text-sm font-semibold text-[#3E352E] mt-0.5">
                                    <?php echo htmlspecialchars(ExportadorCSV::PERIODOS[$s['periodo']] ?? $s['periodo_label']); ?>
                                </div>
                            </div>
                            <?php if ($atrasado): ?>
                                <span class="text-[10px] font-bold text-amber-800 bg-amber-100 border border-amber-300 px-2 py-1 rounded-full whitespace-nowrap">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Atrasado
                                </span>
                            <?php elseif ($proximo): ?>
                                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-1 rounded-full whitespace-nowrap">
                                    <i class="fa-solid fa-check"></i> Em dia
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="text-xs text-stone-500 space-y-0.5">
                            <?php if ($s['nunca_gerado']): ?>
                                <div>Nunca gerado</div>
                            <?php else: ?>
                                <div>Última: <strong><?php echo date('d/m/Y H:i', strtotime($s['ultima_em'])); ?></strong></div>
                                <div>Próxima: <strong><?php echo htmlspecialchars($s['proxima_em']); ?></strong></div>
                            <?php endif; ?>
                            <div class="text-stone-400">Frequência: a cada <?php echo (int)$s['frequencia_dias']; ?> dia(s)</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- GERAR MANUAL                                                  -->
        <!-- ============================================================ -->
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
            <h2 class="text-lg font-serif text-[#3E352E]">
                <i class="fa-solid fa-plus-circle text-[#8C6D36] mr-1"></i> Gerar exportação manual
            </h2>

            <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                <input type="hidden" name="acao" value="gerar">

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Tipo de dados</label>
                    <select name="tipo" required class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]">
                        <?php foreach (ExportadorCSV::TIPOS as $k => $v): ?>
                            <option value="<?php echo htmlspecialchars($k); ?>"><?php echo htmlspecialchars($v); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Período</label>
                    <select name="periodo" id="sel-periodo" required class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]">
                        <?php foreach (ExportadorCSV::PERIODOS as $k => $v): ?>
                            <option value="<?php echo htmlspecialchars($k); ?>"><?php echo htmlspecialchars($v); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-5 py-2.5 rounded-xl text-sm font-medium transition shadow-sm h-[42px]">
                    <i class="fa-solid fa-file-export mr-1"></i> Gerar
                </button>

                <!-- Campos de período personalizado -->
                <div class="md:col-span-4 hidden grid-cols-2 gap-3" id="bloco-personalizado">
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Data inicial</label>
                        <input type="date" name="data_ini" class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Data final</label>
                        <input type="date" name="data_fim" class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]">
                    </div>
                </div>
            </form>
        </section>

        <!-- ============================================================ -->
        <!-- LISTA DE ARQUIVOS                                             -->
        <!-- ============================================================ -->
        <section class="bg-white rounded-2xl border border-[#E6D5B8]/40 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-[#E6D5B8]/40 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-serif text-[#3E352E]">
                    <?php echo count($exportacoes); ?> arquivo(s)
                </h2>
                <form method="GET" class="flex gap-2 items-center">
                    <select name="tipo" class="px-3 py-2 rounded-xl border border-stone-300 text-xs bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]">
                        <option value="">Todos os tipos</option>
                        <?php foreach (ExportadorCSV::TIPOS as $k => $v): ?>
                            <option value="<?php echo htmlspecialchars($k); ?>" <?php echo $filtroTipo === $k ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($v); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="px-3 py-2 rounded-xl bg-stone-100 hover:bg-stone-200 text-xs font-medium transition">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F9F4EC] text-[#5A4A3A] text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left">Tipo</th>
                            <th class="px-4 py-3 text-left">Período</th>
                            <th class="px-4 py-3 text-right">Linhas</th>
                            <th class="px-4 py-3 text-right">Tamanho</th>
                            <th class="px-4 py-3 text-left">Gerado em</th>
                            <th class="px-4 py-3 text-left">Por</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (!$exportacoes): ?>
                            <tr>
                                <td colspan="7" class="text-center text-stone-500 py-12">
                                    <i class="fa-regular fa-folder-open text-3xl text-[#C5A059] mb-2 block"></i>
                                    Nenhuma exportação gerada ainda.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($exportacoes as $e):
                            $arquivoFisico = __DIR__ . '/../../' . $e['arquivo'];
                            $existe = is_file($arquivoFisico);
                            $auto = ($e['gerado_por'] === null);
                        ?>
                            <tr class="hover:bg-[#FAF9F6] transition">
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 text-xs font-bold bg-[#F9F4EC] text-[#8C6D36] border border-[#E6D5B8] px-2.5 py-1 rounded-full">
                                        <?php echo htmlspecialchars(ExportadorCSV::TIPOS[$e['tipo']] ?? $e['tipo']); ?>
                                    </span>
                                    <?php if ($auto): ?>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-700 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-full ml-1">
                                            <i class="fa-solid fa-robot"></i> Auto
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-xs text-stone-600">
                                    <?php echo date('d/m/Y', strtotime($e['periodo_inicio'])); ?>
                                    &rarr;
                                    <?php echo date('d/m/Y', strtotime($e['periodo_fim'])); ?>
                                    <span class="text-stone-400">(<?php echo htmlspecialchars($e['periodo']); ?>)</span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-stone-700"><?php echo (int)$e['total_linhas']; ?></td>
                                <td class="px-4 py-3 text-right text-stone-500 text-xs"><?php echo fmtBytes((int)$e['tamanho_bytes']); ?></td>
                                <td class="px-4 py-3 text-xs text-stone-500">
                                    <?php echo date('d/m/Y H:i', strtotime($e['gerado_em'])); ?>
                                </td>
                                <td class="px-4 py-3 text-xs text-stone-500">
                                    <?php echo $auto ? '<em class="text-stone-400">Automático</em>' : htmlspecialchars($e['gerado_nome'] ?? '—'); ?>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <?php if ($existe): ?>
                                        <a href="download.php?id=<?php echo (int)$e['id']; ?>"
                                           class="inline-flex items-center gap-1 text-xs font-medium bg-[#3E352E] text-white px-3 py-1.5 rounded-lg hover:bg-[#5A4A3A] transition">
                                            <i class="fa-solid fa-download"></i> Baixar
                                        </a>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 text-xs font-medium bg-red-50 text-red-700 border border-red-200 px-3 py-1.5 rounded-lg">
                                            <i class="fa-solid fa-triangle-exclamation"></i> Ausente
                                        </span>
                                    <?php endif; ?>
                                    <a href="?excluir=<?php echo (int)$e['id']; ?>"
                                       onclick="return confirm('Excluir esta exportação? O arquivo será apagado.');"
                                       class="inline-flex items-center gap-1 text-xs font-medium bg-red-50 text-red-700 border border-red-200 px-3 py-1.5 rounded-lg hover:bg-red-100 transition ml-1">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const sel   = document.getElementById('sel-periodo');
        const bloco = document.getElementById('bloco-personalizado');
        if (!sel || !bloco) return;

        function atualizar() {
            if (sel.value === 'personalizado') {
                bloco.classList.remove('hidden');
                bloco.classList.add('grid');
            } else {
                bloco.classList.add('hidden');
                bloco.classList.remove('grid');
            }
        }
        sel.addEventListener('change', atualizar);
        atualizar();
    });
    </script>

</body>
</html>