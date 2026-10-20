<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../conexao.php';

use App\LGPD;
use App\Retencao;

if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$nomeAdmin = $_SESSION['nome_usuario'] ?? 'Administrador';
$uid = (int)$_SESSION['usuario_id'];
$msg = ''; $msgTipo = '';

// ============================================================
// AÇÃO: EXECUTAR AGORA
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'executar') {
    if (($_POST['confirmar'] ?? '') !== '1') {
        $msg = "Você precisa marcar a caixa de confirmação.";
        $msgTipo = 'erro';
    } else {
        try {
            $res = Retencao::executar($conn, $uid);

            $msg = "Retenção executada em <strong>{$res['duracao_seg']}s</strong> · "
                 . "<strong>{$res['logs_removidos']}</strong> logs removidos · "
                 . "<strong>{$res['cookies_removidos']}</strong> consentimentos removidos · "
                 . "<strong>{$res['usuarios_anonimizados']}</strong> usuários anonimizados";

            if (!empty($res['erros'])) {
                $msg .= "<br><small class='text-amber-700'>Erros: " . count($res['erros']) . " (veja o histórico)</small>";
            }
            $msgTipo = 'sucesso';
        } catch (\Throwable $e) {
            $msg = "Erro: " . $e->getMessage();
            $msgTipo = 'erro';
        }
    }
}

// ============================================================
// AÇÃO: SALVAR CONFIGURAÇÕES
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar_config') {
    foreach (['retencao_logs_meses', 'retencao_cookies_meses', 'retencao_cadastro_anos', 'retencao_clinico_anos', 'retencao_auto_ativa'] as $k) {
        $v = trim($_POST[$k] ?? '');
        LGPD::setConfig($conn, $k, $v);
    }
    $msg = "Configurações atualizadas.";
    $msgTipo = 'sucesso';
}

// ============================================================
// ANÁLISE (preview)
// ============================================================
$analise = Retencao::analisar($conn);
$historico = Retencao::historico($conn, 15);

$autoAtiva = LGPD::config($conn, 'retencao_auto_ativa', '1') === '1';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retenção de Dados - LGPD</title>
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

        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-serif text-[#3E352E] flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-[#8C6D36]"></i>
                    Retenção e Anonimização
                </h1>
                <p class="text-sm text-stone-500 mt-1">
                    Eliminação automática de dados além do prazo legal (LGPD Art. 15 e 16).
                </p>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-xs text-right">
                    <div class="font-bold <?php echo $autoAtiva ? 'text-emerald-700' : 'text-stone-500'; ?>">
                        <?php echo $autoAtiva ? '● Auto ativo' : '○ Auto desativado'; ?>
                    </div>
                    <div class="text-stone-400">Roda 1x por dia</div>
                </div>
            </div>
        </section>

        <?php if ($msg): ?>
            <div class="<?php echo $msgTipo === 'sucesso' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'; ?> p-3.5 rounded-xl text-sm border flex items-start gap-2">
                <i class="fa-solid <?php echo $msgTipo === 'sucesso' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> mt-0.5"></i>
                <span><?php echo $msg; ?></span>
            </div>
        <?php endif; ?>

        <!-- Aviso importante -->
        <div class="bg-blue-50 border border-blue-200 text-blue-900 p-4 rounded-2xl text-sm flex items-start gap-3">
            <i class="fa-solid fa-circle-info mt-0.5"></i>
            <div>
                <strong>Como funciona:</strong> o sistema remove automaticamente logs e consentimentos antigos
                e <em>anonimiza</em> usuários que não têm atividade há mais de 5 anos.
                <strong>Dados clínicos são preservados por 20 anos</strong> (Resolução CFM nº 1.821/2007).
            </div>
        </div>

        <!-- Cards de análise -->
        <section class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Logs</span>
                    <i class="fa-solid fa-clipboard-list text-[#8C6D36]"></i>
                </div>
                <div class="text-3xl font-serif text-[#3E352E]"><?php echo $analise['totais']['logs']; ?></div>
                <div class="text-xs text-stone-500 mt-1">
                    &gt; <?php echo $analise['limites']['logs_meses']; ?> meses
                </div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Cookies</span>
                    <i class="fa-solid fa-cookie-bite text-[#8C6D36]"></i>
                </div>
                <div class="text-3xl font-serif text-[#3E352E]"><?php echo $analise['totais']['cookies']; ?></div>
                <div class="text-xs text-stone-500 mt-1">
                    &gt; <?php echo $analise['limites']['cookies_meses']; ?> meses
                </div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Usuários inativos</span>
                    <i class="fa-solid fa-user-slash text-amber-600"></i>
                </div>
                <div class="text-3xl font-serif text-amber-700"><?php echo $analise['totais']['inativos']; ?></div>
                <div class="text-xs text-stone-500 mt-1">
                    &gt; <?php echo $analise['limites']['cadastro_anos']; ?> anos sem consulta
                </div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Dados clínicos</span>
                    <i class="fa-solid fa-file-medical text-stone-500"></i>
                </div>
                <div class="text-3xl font-serif text-stone-600"><?php echo $analise['totais']['clinicos']; ?></div>
                <div class="text-xs text-stone-500 mt-1">
                    &gt; <?php echo $analise['limites']['clinico_anos']; ?> anos (preservados)
                </div>
            </div>
        </section>

        <!-- Executar agora -->
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
            <div>
                <h2 class="text-lg font-serif text-[#3E352E] flex items-center gap-2">
                    <i class="fa-solid fa-broom text-[#8C6D36]"></i>
                    Executar retenção agora
                </h2>
                <p class="text-sm text-stone-500 mt-1">
                    Remove <strong><?php echo $analise['totais']['logs'] + $analise['totais']['cookies']; ?></strong> registros e
                    anonimiza <strong><?php echo $analise['totais']['inativos']; ?></strong> usuários inativos.
                </p>
            </div>

            <div class="bg-amber-50 border border-amber-200 p-3 rounded-xl text-xs text-amber-800 flex items-start gap-2">
                <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                <span>
                    Esta ação é <strong>irreversível</strong>. O log de auditoria registrará o que foi removido.
                    Antes de rodar, confira se os prazos nas configurações estão corretos.
                </span>
            </div>

            <?php if ($analise['totais']['logs'] + $analise['totais']['cookies'] + $analise['totais']['inativos'] === 0): ?>
                <div class="bg-emerald-50 border border-emerald-200 p-3 rounded-xl text-sm text-emerald-800 flex items-center gap-2">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Nenhum dado elegível para remoção no momento. Tudo em conformidade.</span>
                </div>
            <?php else: ?>
                <form method="POST" onsubmit="return confirm('Confirma a execução da retenção? Esta ação é irreversível.');">
                    <input type="hidden" name="acao" value="executar">

                    <label class="flex items-start gap-2 text-sm text-stone-700 cursor-pointer mb-3">
                        <input type="checkbox" name="confirmar" value="1" required class="mt-1 w-4 h-4 accent-[#8C6D36]">
                        <span>Confirmo que revisei os prazos e autorizo a execução agora.</span>
                    </label>

                    <button type="submit" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-6 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                        <i class="fa-solid fa-broom mr-1"></i> Executar retenção
                    </button>
                </form>
            <?php endif; ?>
        </section>

        <!-- Configurações -->
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
            <h2 class="text-lg font-serif text-[#3E352E] flex items-center gap-2">
                <i class="fa-solid fa-sliders text-[#8C6D36]"></i>
                Prazos de retenção
            </h2>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="acao" value="salvar_config">

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Logs (meses)</label>
                        <input type="number" name="retencao_logs_meses" value="<?php echo (int)$analise['limites']['logs_meses']; ?>" min="1"
                               class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Cookies (meses)</label>
                        <input type="number" name="retencao_cookies_meses" value="<?php echo (int)$analise['limites']['cookies_meses']; ?>" min="1"
                               class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Cadastro (anos)</label>
                        <input type="number" name="retencao_cadastro_anos" value="<?php echo (int)$analise['limites']['cadastro_anos']; ?>" min="1"
                               class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Clínico (anos)</label>
                        <input type="number" name="retencao_clinico_anos" value="<?php echo (int)$analise['limites']['clinico_anos']; ?>" min="1"
                               class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]">
                    </div>
                </div>

                <label class="flex items-start gap-2 text-sm text-stone-700 cursor-pointer">
                    <input type="checkbox" name="retencao_auto_ativa" value="1" <?php echo $autoAtiva ? 'checked' : ''; ?>
                           class="mt-1 w-4 h-4 accent-[#8C6D36]">
                    <span>
                        <strong>Execução automática diária</strong> — o sistema roda a retenção silenciosamente
                        quando o painel admin é acessado, respeitando os prazos acima.
                    </span>
                </label>

                <div class="flex justify-end">
                    <button type="submit" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-6 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> Salvar configurações
                    </button>
                </div>
            </form>
        </section>

        <!-- Histórico -->
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
            <h2 class="text-lg font-serif text-[#3E352E] flex items-center gap-2">
                <i class="fa-solid fa-list-check text-[#8C6D36]"></i>
                Histórico de execuções
            </h2>

            <?php if (!$historico): ?>
                <p class="text-sm text-stone-500 py-2">Nenhuma execução registrada.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-[#F9F4EC] text-[#5A4A3A] text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-3 py-2 text-left">Data</th>
                                <th class="px-3 py-2 text-left">Por</th>
                                <th class="px-3 py-2 text-right">Logs</th>
                                <th class="px-3 py-2 text-right">Cookies</th>
                                <th class="px-3 py-2 text-right">Anonimizados</th>
                                <th class="px-3 py-2 text-right">Duração</th>
                                <th class="px-3 py-2 text-left">Erros</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <?php foreach ($historico as $h): ?>
                                <tr class="hover:bg-[#FAF9F6]">
                                    <td class="px-3 py-2 text-xs text-stone-500"><?php echo date('d/m/Y H:i', strtotime($h['criado_em'])); ?></td>
                                    <td class="px-3 py-2 text-xs"><?php echo htmlspecialchars($h['executado_nome'] ?? 'Automático'); ?></td>
                                    <td class="px-3 py-2 text-right font-mono text-xs"><?php echo (int)$h['logs_removidos']; ?></td>
                                    <td class="px-3 py-2 text-right font-mono text-xs"><?php echo (int)$h['cookies_removidos']; ?></td>
                                    <td class="px-3 py-2 text-right font-mono text-xs"><?php echo (int)$h['usuarios_anonimizados']; ?></td>
                                    <td class="px-3 py-2 text-right font-mono text-xs"><?php echo $h['duracao_seg']; ?>s</td>
                                    <td class="px-3 py-2 text-xs">
                                        <?php if ($h['erros']): ?>
                                            <span class="text-red-600" title="<?php echo htmlspecialchars($h['erros']); ?>">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Ver
                                            </span>
                                        <?php else: ?>
                                            <span class="text-emerald-600"><i class="fa-solid fa-check"></i></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- Amostras (o que será removido) -->
        <?php if (!empty($analise['amostras']['logs']) || !empty($analise['amostras']['usuarios'])): ?>
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
            <h2 class="text-lg font-serif text-[#3E352E] flex items-center gap-2">
                <i class="fa-solid fa-eye text-[#8C6D36]"></i>
                Prévia do que será removido/anonimizado
            </h2>

            <?php if (!empty($analise['amostras']['usuarios'])): ?>
                <div>
                    <h3 class="text-xs font-bold text-stone-500 uppercase tracking-wider mb-2">Usuários a anonimizar</h3>
                    <ul class="space-y-1 text-xs">
                        <?php foreach ($analise['amostras']['usuarios'] as $u): ?>
                            <li class="flex items-center justify-between gap-2 border border-stone-200 rounded-lg px-3 py-2 bg-[#FAF9F6]">
                                <span><strong>#<?php echo (int)$u['id']; ?></strong> · <?php echo htmlspecialchars($u['nome']); ?></span>
                                <span class="text-stone-400">cadastrado em <?php echo date('d/m/Y', strtotime($u['criado_em'])); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($analise['amostras']['logs'])): ?>
                <div>
                    <h3 class="text-xs font-bold text-stone-500 uppercase tracking-wider mb-2">Últimos logs a remover</h3>
                    <ul class="space-y-1 text-xs">
                        <?php foreach ($analise['amostras']['logs'] as $l): ?>
                            <li class="flex items-center justify-between gap-2 border border-stone-200 rounded-lg px-3 py-2 bg-[#FAF9F6]">
                                <span><strong>#<?php echo (int)$l['id']; ?></strong> · <?php echo htmlspecialchars($l['action']); ?> · <?php echo htmlspecialchars($l['user_name'] ?? '—'); ?></span>
                                <span class="text-stone-400"><?php echo date('d/m/Y H:i', strtotime($l['created_at'])); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

    </main>

</body>
</html>