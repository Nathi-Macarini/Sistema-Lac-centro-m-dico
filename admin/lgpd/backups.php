<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../conexao.php';

use App\Backup;
use App\LGPD;

if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$nomeAdmin = $_SESSION['nome_usuario'] ?? 'Administrador';
$uid = (int)$_SESSION['usuario_id'];
$msg = ''; $msgTipo = '';

// ============================================================
// AÇÃO: CRIAR BACKUP MANUAL
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'backup') {
    $res = Backup::executar($conn, $uid, 'manual');

    if ($res['ok']) {
        $msg = "Backup criado com sucesso: <strong>{$res['arquivo']}</strong> ("
             . Backup::formatarTamanho($res['tamanho']) . " em {$res['duracao']}s)";
        $msgTipo = 'sucesso';
    } else {
        $msg = "Falha ao criar backup: " . htmlspecialchars($res['erro'] ?? 'erro desconhecido');
        $msgTipo = 'erro';
    }
}

// ============================================================
// AÇÃO: EXCLUIR BACKUP
// ============================================================
if (isset($_GET['excluir'])) {
    $idDel = (int)$_GET['excluir'];
    Backup::excluir($conn, $idDel);
    header("Location: backups.php?excluido=1");
    exit;
}

// ============================================================
// AÇÃO: LIMPAR ANTIGOS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'limpar') {
    $n = Backup::limparAntigos($conn);
    $msg = "{$n} backup(s) antigos removidos.";
    $msgTipo = 'sucesso';
}

// ============================================================
// AÇÃO: SALVAR CONFIG
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'config') {
    LGPD::setConfig($conn, 'backup_manter_dias', trim($_POST['backup_manter_dias'] ?? '90'));
    LGPD::setConfig($conn, 'backup_auto_ativo',  isset($_POST['backup_auto_ativo']) ? '1' : '0');
    $msg = "Configurações salvas.";
    $msgTipo = 'sucesso';
}

// ============================================================
// LISTA
// ============================================================
$backups = Backup::listar($conn, 100);
$manterDias = LGPD::config($conn, 'backup_manter_dias', '90');
$autoAtivo  = LGPD::config($conn, 'backup_auto_ativo', '1') === '1';

// Estatísticas
$totalSize = 0;
$total = count($backups);
$ultimoOk = null;
foreach ($backups as $b) {
    if ($b['status'] === 'sucesso') {
        $totalSize += (int)$b['tamanho_bytes'];
        if (!$ultimoOk) $ultimoOk = $b;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backups - LGPD</title>
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
                    <i class="fa-solid fa-arrow-left"></i> <span class="hidden sm:inline">Voltar</span>
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
                    <i class="fa-solid fa-database text-[#8C6D36]"></i>
                    Backups do Banco de Dados
                </h1>
                <p class="text-sm text-stone-500 mt-1">
                    Armazenamento de longo prazo e recuperação de dados clínicos (LGPD Art. 46).
                </p>
            </div>
            <form method="POST">
                <input type="hidden" name="acao" value="backup">
                <button type="submit" class="inline-flex items-center gap-2 bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-5 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                    <i class="fa-solid fa-download"></i> Criar backup agora
                </button>
            </form>
        </section>

        <?php if ($msg): ?>
            <div class="<?php echo $msgTipo === 'sucesso' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'; ?> p-3.5 rounded-xl text-sm border flex items-start gap-2">
                <i class="fa-solid <?php echo $msgTipo === 'sucesso' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> mt-0.5"></i>
                <span><?php echo $msg; ?></span>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['excluido'])): ?>
            <div class="bg-amber-50 text-amber-800 border-amber-200 p-3.5 rounded-xl text-sm border flex items-start gap-2">
                <i class="fa-solid fa-circle-check mt-0.5"></i>
                <span>Backup excluído.</span>
            </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Total de backups</span>
                    <i class="fa-solid fa-database text-[#8C6D36]"></i>
                </div>
                <div class="text-3xl font-serif text-[#3E352E]"><?php echo $total; ?></div>
                <div class="text-xs text-stone-500 mt-1">últimos 100</div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Espaço usado</span>
                    <i class="fa-solid fa-hdd text-[#8C6D36]"></i>
                </div>
                <div class="text-3xl font-serif text-[#3E352E]"><?php echo Backup::formatarTamanho($totalSize); ?></div>
                <div class="text-xs text-stone-500 mt-1">em disco local</div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Último backup</span>
                    <i class="fa-solid fa-clock text-[#8C6D36]"></i>
                </div>
                <?php if ($ultimoOk): ?>
                    <div class="text-lg font-serif text-[#3E352E]"><?php echo date('d/m/Y H:i', strtotime($ultimoOk['criado_em'])); ?></div>
                    <div class="text-xs text-emerald-600 mt-1">
                        <i class="fa-solid fa-circle-check"></i> <?php echo Backup::formatarTamanho((int)$ultimoOk['tamanho_bytes']); ?>
                    </div>
                <?php else: ?>
                    <div class="text-lg font-serif text-stone-400">Nunca</div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Aviso -->
        <div class="bg-blue-50 border border-blue-200 text-blue-900 p-4 rounded-2xl text-sm flex items-start gap-3">
            <i class="fa-solid fa-circle-info mt-0.5"></i>
            <div>
                <strong>Estratégia de retenção:</strong> backups antigos são removidos automaticamente
                após <strong><?php echo (int)$manterDias; ?> dias</strong>. Para conformidade LGPD Saúde,
                guarde também uma cópia externa (nuvem/HD externo) dos últimos 20 anos de dados clínicos.
            </div>
        </div>

        <!-- Configurações -->
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
            <h2 class="text-lg font-serif text-[#3E352E]">
                <i class="fa-solid fa-sliders text-[#8C6D36] mr-1"></i>
                Configurações de backup
            </h2>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="acao" value="config">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Manter backups por (dias)</label>
                        <input type="number" name="backup_manter_dias" value="<?php echo (int)$manterDias; ?>" min="1" max="3650"
                               class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]">
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-start gap-2 text-sm text-stone-700 cursor-pointer">
                            <input type="checkbox" name="backup_auto_ativo" value="1" <?php echo $autoAtivo ? 'checked' : ''; ?>
                                   class="mt-1 w-4 h-4 accent-[#8C6D36]">
                            <span><strong>Backup automático diário</strong> (quando o admin acessa o painel)</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-between pt-2 border-t border-stone-100">
                    <button type="submit" formaction="?" formmethod="POST" name="acao" value="limpar"
                            onclick="this.form.acao.value='limpar'; return confirm('Remover backups com mais de <?php echo (int)$manterDias; ?> dias?');"
                            class="bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 px-5 py-2.5 rounded-xl text-sm font-medium transition">
                        <i class="fa-solid fa-broom mr-1"></i> Limpar antigos agora
                    </button>
                    <button type="submit" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-6 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> Salvar configurações
                    </button>
                </div>
            </form>
        </section>

        <!-- Lista -->
        <section class="bg-white rounded-2xl border border-[#E6D5B8]/40 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-[#E6D5B8]/40">
                <h2 class="text-lg font-serif text-[#3E352E]">Histórico de backups</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F9F4EC] text-[#5A4A3A] text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left">Data</th>
                            <th class="px-4 py-3 text-left">Arquivo</th>
                            <th class="px-4 py-3 text-right">Tamanho</th>
                            <th class="px-4 py-3 text-right">Tabelas</th>
                            <th class="px-4 py-3 text-right">Registros</th>
                            <th class="px-4 py-3 text-left">Tipo</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (!$backups): ?>
                            <tr><td colspan="8" class="text-center text-stone-500 py-12">
                                <i class="fa-regular fa-folder-open text-3xl text-[#C5A059] mb-2 block"></i>
                                Nenhum backup criado ainda.
                            </td></tr>
                        <?php endif; ?>

                        <?php foreach ($backups as $b):
                            $arquivoFisico = __DIR__ . '/../../' . $b['arquivo'];
                            $existe = is_file($arquivoFisico);
                        ?>
                            <tr class="hover:bg-[#FAF9F6]">
                                <td class="px-4 py-3 text-xs text-stone-500 whitespace-nowrap">
                                    <?php echo date('d/m/Y H:i', strtotime($b['criado_em'])); ?>
                                </td>
                                <td class="px-4 py-3 text-xs font-mono text-stone-700">
                                    <?php echo htmlspecialchars(basename($b['arquivo'])); ?>
                                </td>
                                <td class="px-4 py-3 text-right text-xs text-stone-600">
                                    <?php echo Backup::formatarTamanho((int)$b['tamanho_bytes']); ?>
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-xs"><?php echo (int)$b['tabelas']; ?></td>
                                <td class="px-4 py-3 text-right font-mono text-xs"><?php echo (int)$b['registros']; ?></td>
                                <td class="px-4 py-3">
                                    <span class="text-xs font-medium px-2 py-1 rounded-full border
                                        <?php echo $b['tipo'] === 'automatico' 
                                            ? 'bg-blue-50 text-blue-700 border-blue-200' 
                                            : 'bg-stone-100 text-stone-600 border-stone-200'; ?>">
                                        <?php echo $b['tipo'] === 'automatico' ? 'Auto' : 'Manual'; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($b['status'] === 'sucesso'): ?>
                                        <span class="text-xs font-semibold text-emerald-700">
                                            <i class="fa-solid fa-circle-check"></i> Sucesso
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs font-semibold text-red-700" title="<?php echo htmlspecialchars($b['erro'] ?? ''); ?>">
                                            <i class="fa-solid fa-circle-xmark"></i> Erro
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <?php if ($existe && $b['status'] === 'sucesso'): ?>
                                        <a href="../../<?php echo htmlspecialchars($b['arquivo']); ?>" download
                                           class="inline-flex items-center gap-1 text-xs font-medium bg-[#3E352E] text-white px-3 py-1.5 rounded-lg hover:bg-[#5A4A3A] transition">
                                            <i class="fa-solid fa-download"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="?excluir=<?php echo (int)$b['id']; ?>"
                                       onclick="return confirm('Excluir este backup? O arquivo será apagado.');"
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

        <!-- Documentação: estratégia de longo prazo -->
        <section class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
            <h2 class="text-xl font-serif text-[#3E352E]">
                <i class="fa-solid fa-graduation-cap text-[#8C6D36] mr-1"></i>
                Estratégia de armazenamento de longo prazo
            </h2>

            <div class="prose prose-stone max-w-none
                        [&_h3]:text-base [&_h3]:font-bold [&_h3]:text-[#3E352E] [&_h3]:mt-4 [&_h3]:mb-2
                        [&_p]:text-sm [&_p]:text-stone-700 [&_p]:my-2
                        [&_ul]:text-sm [&_ul]:text-stone-700 [&_ul]:pl-5 [&_ul]:my-2 [&_ul]:list-disc
                        [&_li]:my-1 [&_strong]:text-[#3E352E]">

                <h3>Por que 20 anos?</h3>
                <p>
                    A <strong>Resolução CFM nº 1.821/2007</strong> exige que prontuários eletrônicos sejam
                    mantidos por no mínimo <strong>20 anos</strong> após o último atendimento.
                    A LGPD (Art. 16) permite a conservação para cumprimento de obrigação legal.
                </p>

                <h3>Arquitetura recomendada (3 camadas)</h3>
                <ul>
                    <li>
                        <strong>Camada 1 — Banco ativo</strong>: consultas dos últimos 2 anos + dados cadastrais ativos.
                        Rápido para o dia a dia.
                    </li>
                    <li>
                        <strong>Camada 2 — Backups locais</strong>: dumps diários mantidos por 90 dias
                        (o que este painel gerencia).
                    </li>
                    <li>
                        <strong>Camada 3 — Arquivo externo</strong>: backups mensais enviados para nuvem/HD externo,
                        guardados por 20 anos.
                    </li>
                </ul>

                <h3>O que fazer com a Camada 3 (arquivo externo)</h3>
                <ul>
                    <li>Copiar o dump mensal para <strong>Google Drive / OneDrive / Dropbox</strong> criptografado;</li>
                    <li>Ou usar <strong>Amazon S3 Glacier</strong> (armazenamento frio, ~R$ 0,02/GB/mês);</li>
                    <li>Ou <strong>Backblaze B2</strong> (mais barato ainda);</li>
                    <li>Manter <strong>2 cópias</strong> em locais diferentes (regra 3-2-1 de backup);</li>
                    <li>Testar a restauração <strong>a cada 6 meses</strong>;</li>
                    <li>Documentar quem tem acesso às chaves.</li>
                </ul>

                <h3>Frequência recomendada</h3>
                <ul>
                    <li><strong>Backup local automático</strong>: diário (este painel já faz);</li>
                    <li><strong>Cópia externa</strong>: semanal ou mensal;</li>
                    <li><strong>Backup completo anual</strong>: guardado por 20 anos;</li>
                    <li><strong>Teste de restore</strong>: semestral.</li>
                </ul>

                <h3>Quando o MySQL do XAMPP migrar para produção</h3>
                <p>
                    Se a LAC usar um servidor gerenciado (Railway, PlanetScale, AWS RDS),
                    a maioria oferece backup automático nativo — basta ativar no painel do provedor
                    e <strong>não desativar</strong>.
                </p>
            </div>
        </section>

    </main>

    <?php include __DIR__ . '/../../includes/rodape.php'; ?>
</body>
</html>