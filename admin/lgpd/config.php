<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../conexao.php';

use App\ActivityLogger;
use App\LGPD;

if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$nomeAdmin = $_SESSION['nome_usuario'] ?? 'Administrador';
$msg = ''; $msgTipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = ['dpo_nome', 'dpo_email', 'dpo_telefone', 'retencao_logs_meses', 'retencao_cadastro_anos', 'retencao_clinico_anos', 'politica_versao'];

    foreach ($campos as $c) {
        $valor = trim($_POST[$c] ?? '');
        LGPD::setConfig($conn, $c, $valor);
    }

    ActivityLogger::log(
        action: 'lgpd_config_atualizada',
        entity: 'lgpd_config',
        description: "Admin {$nomeAdmin} atualizou as configurações LGPD",
        tags: ['lgpd', 'config']
    );

    $msg = "Configurações atualizadas.";
    $msgTipo = 'sucesso';
}

$dpoNome   = LGPD::config($conn, 'dpo_nome', '');
$dpoEmail  = LGPD::config($conn, 'dpo_email', '');
$dpoTel    = LGPD::config($conn, 'dpo_telefone', '');
$retLogs   = LGPD::config($conn, 'retencao_logs_meses', '6');
$retCad    = LGPD::config($conn, 'retencao_cadastro_anos', '5');
$retCli    = LGPD::config($conn, 'retencao_clinico_anos', '20');
$polVer    = LGPD::config($conn, 'politica_versao', 'v1.0');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações LGPD - Admin LAC</title>
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

    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <h1 class="text-2xl font-serif text-[#3E352E] flex items-center gap-2">
                <i class="fa-solid fa-gear text-[#8C6D36]"></i>
                Configurações LGPD
            </h1>
            <p class="text-sm text-stone-500 mt-1">
                Configure o DPO (Encarregado) e os prazos de retenção de dados.
            </p>
        </section>

        <?php if ($msg): ?>
            <div class="<?php echo $msgTipo === 'sucesso' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'; ?> p-3.5 rounded-xl text-sm border flex items-start gap-2">
                <i class="fa-solid <?php echo $msgTipo === 'sucesso' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> mt-0.5"></i>
                <span><?php echo htmlspecialchars($msg); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">

            <!-- DPO -->
            <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
                <h2 class="text-lg font-serif text-[#3E352E] flex items-center gap-2">
                    <i class="fa-solid fa-user-shield text-[#8C6D36]"></i>
                    Encarregado de Proteção de Dados (DPO)
                </h2>
                <p class="text-xs text-stone-500">
                    Estes dados aparecem na página <code class="bg-[#F9F4EC] px-1.5 py-0.5 rounded">privacidade.php</code> para os pacientes.
                </p>

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Nome do DPO</label>
                    <input type="text" name="dpo_nome" value="<?php echo htmlspecialchars($dpoNome); ?>"
                           class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]"
                           placeholder="Ex.: João Silva">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">E-mail</label>
                        <input type="email" name="dpo_email" value="<?php echo htmlspecialchars($dpoEmail); ?>"
                               class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]"
                               placeholder="dpo@laccentromedico.com.br">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Telefone</label>
                        <input type="text" name="dpo_telefone" value="<?php echo htmlspecialchars($dpoTel); ?>"
                               class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]"
                               placeholder="(41) 3500-8072">
                    </div>
                </div>
            </section>

            <!-- Retenção -->
            <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
                <h2 class="text-lg font-serif text-[#3E352E] flex items-center gap-2">
                    <i class="fa-solid fa-hourglass-half text-[#8C6D36]"></i>
                    Prazos de retenção
                </h2>
                <p class="text-xs text-stone-500">
                    Períodos definidos com base em exigências legais (CFM, Receita Federal e LGPD).
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Logs (meses)</label>
                        <input type="number" name="retencao_logs_meses" value="<?php echo htmlspecialchars($retLogs); ?>" min="1"
                               class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Cadastro (anos)</label>
                        <input type="number" name="retencao_cadastro_anos" value="<?php echo htmlspecialchars($retCad); ?>" min="1"
                               class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Clínico (anos)</label>
                        <input type="number" name="retencao_clinico_anos" value="<?php echo htmlspecialchars($retCli); ?>" min="1"
                               class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]">
                    </div>
                </div>

                <div class="bg-[#F9F4EC] border border-[#E6D5B8] p-3 rounded-xl text-xs text-[#5A4A3A] flex items-start gap-2">
                    <i class="fa-solid fa-circle-info mt-0.5"></i>
                    <span>
                        <strong>Recomendações:</strong> Logs 6 meses · Cadastro 5 anos · Dados clínicos 20 anos (CFM).
                    </span>
                </div>
            </section>

            <!-- Política -->
            <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
                <h2 class="text-lg font-serif text-[#3E352E] flex items-center gap-2">
                    <i class="fa-solid fa-file-contract text-[#8C6D36]"></i>
                    Versão da política de privacidade
                </h2>
                <div>
                    <input type="text" name="politica_versao" value="<?php echo htmlspecialchars($polVer); ?>"
                           class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]"
                           placeholder="v1.0">
                </div>
            </section>

            <div class="flex justify-end gap-3">
                <a href="index.php" class="px-5 py-2.5 rounded-xl text-sm font-medium text-stone-700 border border-stone-300 hover:bg-stone-50">
                    Cancelar
                </a>
                <button type="submit" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-6 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> Salvar configurações
                </button>
            </div>
        </form>

    </main>

</body>
</html>