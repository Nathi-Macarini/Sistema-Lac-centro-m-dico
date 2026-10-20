<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../conexao.php';

use App\LGPD;
use App\Certificado;

if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$nomeAdmin = $_SESSION['nome_usuario'] ?? 'Administrador';
$msg = ''; $msgTipo = '';

// ============================================================
// AÇÃO: VERIFICAR AGORA (chamada DIRETA, sem exec())
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'verificar') {
    $dominio = trim($_POST['dominio'] ?? '');

    if ($dominio === '') {
        $msg = "Informe o domínio (ex: app.laccentromedico.com.br).";
        $msgTipo = 'erro';
    } else {
        $resultado = Certificado::coletar($dominio);

        if (!$resultado) {
            $msg = "Não foi possível conectar em {$dominio}:443 ou obter o certificado. Verifique o domínio e a conexão.";
            $msgTipo = 'erro';
        } else {
            Certificado::salvar($conn, $resultado);
            Certificado::logarAlerta($resultado);

            if ($resultado['expirado']) {
                $msg = "Certificado EXPIRADO em {$resultado['valido_ate']}!";
                $msgTipo = 'erro';
            } elseif ($resultado['alerta']) {
                $msg = "Certificado expira em {$resultado['dias_restantes']} dias — renovar!";
                $msgTipo = 'erro';
            } else {
                $msg = "Certificado válido. Expira em {$resultado['dias_restantes']} dias ({$resultado['valido_ate']}).";
                $msgTipo = 'sucesso';
            }
        }
    }
}

// ============================================================
// LÊ STATUS SALVO
// ============================================================
$cert = [
    'dominio'         => LGPD::config($conn, 'certificado_dominio', '—'),
    'emissor'         => LGPD::config($conn, 'certificado_emissor', '—'),
    'valido_ate'      => LGPD::config($conn, 'certificado_valido_ate', '—'),
    'dias_restantes'  => LGPD::config($conn, 'certificado_dias_restantes', '—'),
    'verificado_em'   => LGPD::config($conn, 'certificado_verificado_em', '—'),
];

$dias = is_numeric($cert['dias_restantes']) ? (int)$cert['dias_restantes'] : null;

if ($dias === null) {
    $cor = 'stone'; $statusTxt = 'Não verificado'; $icone = 'fa-circle-question';
} elseif ($dias < 0) {
    $cor = 'red'; $statusTxt = 'EXPIRADO'; $icone = 'fa-circle-xmark';
} elseif ($dias <= 30) {
    $cor = 'amber'; $statusTxt = 'Renovar em breve'; $icone = 'fa-triangle-exclamation';
} else {
    $cor = 'emerald'; $statusTxt = 'Válido'; $icone = 'fa-circle-check';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoramento de Certificado SSL - LGPD</title>
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
                <a href="seguranca.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition">
                    <i class="fa-solid fa-arrow-left"></i> <span class="hidden sm:inline">Voltar</span>
                </a>
                <span class="text-sm font-medium text-stone-700 hidden sm:inline"><?php echo htmlspecialchars($nomeAdmin); ?></span>
                <a href="../../logout.php" class="text-stone-400 hover:text-amber-700 transition" title="Sair">
                    <i class="fa-solid fa-right-from-bracket text-lg"></i>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <h1 class="text-2xl font-serif text-[#3E352E] flex items-center gap-2">
                <i class="fa-solid fa-certificate text-[#8C6D36]"></i>
                Monitoramento de Certificado SSL
            </h1>
            <p class="text-sm text-stone-500 mt-1">
                Verifique a validade do certificado do seu domínio de produção.
            </p>
        </section>

        <?php if ($msg): ?>
            <div class="<?php echo $msgTipo === 'sucesso' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'; ?> p-3.5 rounded-xl text-sm border flex items-start gap-2">
                <i class="fa-solid <?php echo $msgTipo === 'sucesso' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> mt-0.5"></i>
                <span><?php echo htmlspecialchars($msg); ?></span>
            </div>
        <?php endif; ?>

        <!-- Painel de status -->
        <section class="bg-white p-6 rounded-2xl border border-<?php echo $cor; ?>-200 shadow-sm">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-<?php echo $cor; ?>-50 border border-<?php echo $cor; ?>-200 flex items-center justify-center text-<?php echo $cor; ?>-700 text-2xl">
                        <i class="fa-solid <?php echo $icone; ?>"></i>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-stone-500 uppercase tracking-wider">Status</div>
                        <div class="text-2xl font-serif text-<?php echo $cor; ?>-700"><?php echo $statusTxt; ?></div>
                    </div>
                </div>
                <?php if ($dias !== null): ?>
                    <div class="text-right">
                        <div class="text-xs font-bold text-stone-500 uppercase tracking-wider">Dias restantes</div>
                        <div class="text-4xl font-serif text-<?php echo $cor; ?>-700"><?php echo $dias; ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($dias !== null): ?>
                <div class="mt-4 pt-4 border-t border-stone-100 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div><span class="font-bold text-stone-500 uppercase text-xs">Domínio</span><div class="text-stone-800 font-mono"><?php echo htmlspecialchars($cert['dominio']); ?></div></div>
                    <div><span class="font-bold text-stone-500 uppercase text-xs">Emissor</span><div class="text-stone-800"><?php echo htmlspecialchars($cert['emissor']); ?></div></div>
                    <div><span class="font-bold text-stone-500 uppercase text-xs">Válido até</span><div class="text-stone-800"><?php echo htmlspecialchars($cert['valido_ate']); ?></div></div>
                    <div><span class="font-bold text-stone-500 uppercase text-xs">Verificado em</span><div class="text-stone-800"><?php echo htmlspecialchars($cert['verificado_em']); ?></div></div>
                </div>
            <?php endif; ?>
        </section>

        <!-- Verificar agora -->
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
            <h2 class="text-lg font-serif text-[#3E352E]">
                <i class="fa-solid fa-magnifying-glass text-[#8C6D36] mr-1"></i>
                Verificar certificado
            </h2>

            <form method="POST" class="flex flex-col sm:flex-row gap-3 items-end">
                <input type="hidden" name="acao" value="verificar">
                <div class="flex-1">
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Domínio</label>
                    <input type="text" name="dominio" required
                           value="<?php echo htmlspecialchars($cert['dominio'] !== '—' ? $cert['dominio'] : ''); ?>"
                           placeholder="ex: app.laccentromedico.com.br"
                           class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]">
                </div>
                <button type="submit" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-5 py-2.5 rounded-xl text-sm font-medium transition shadow-sm h-[42px] whitespace-nowrap">
                    <i class="fa-solid fa-shield-halved mr-1"></i> Verificar agora
                </button>
            </form>

            <div class="bg-[#F9F4EC] border border-[#E6D5B8] p-3 rounded-xl text-xs text-[#5A4A3A] flex items-start gap-2">
                <i class="fa-solid fa-circle-info mt-0.5"></i>
                <span>
                    Funciona com qualquer domínio público. Para testar localmente, use
                    <code class="bg-white px-1.5 py-0.5 rounded border border-[#E6D5B8]">google.com</code>.
                </span>
            </div>
        </section>

        <!-- Automação via cron -->
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
            <h2 class="text-lg font-serif text-[#3E352E]">
                <i class="fa-solid fa-clock text-[#8C6D36] mr-1"></i>
                Verificação automática (cron)
            </h2>

            <p class="text-sm text-stone-600">
                Configure o cron do servidor de produção para rodar diariamente. Exemplo:
            </p>

            <pre class="bg-[#3E352E] text-[#F4E8D1] p-4 rounded-xl text-xs overflow-x-auto">0 8 * * * /usr/bin/php /var/www/lac/scripts/check_certificado.php --domain=app.laccentromedico.com.br</pre>

            <p class="text-xs text-stone-500">
                Isso atualiza o status acima diariamente e registra alertas no log de atividades
                quando o certificado estiver a menos de 30 dias de expirar.
            </p>
        </section>

        <!-- Links úteis -->
        <section class="bg-[#F9F4EC] border border-[#E6D5B8] p-5 rounded-2xl">
            <h3 class="text-sm font-bold text-[#3E352E] uppercase tracking-wider mb-3">Ferramentas externas</h3>
            <div class="flex flex-wrap gap-2">
                <a href="https://www.ssllabs.com/ssltest/" target="_blank"
                   class="inline-flex items-center gap-2 bg-white hover:bg-stone-50 border border-[#E6D5B8] text-[#5A4A3A] px-4 py-2 rounded-xl text-xs font-medium transition">
                    <i class="fa-solid fa-flask"></i> SSL Labs
                </a>
                <a href="https://www.sslshopper.com/ssl-checker.html" target="_blank"
                   class="inline-flex items-center gap-2 bg-white hover:bg-stone-50 border border-[#E6D5B8] text-[#5A4A3A] px-4 py-2 rounded-xl text-xs font-medium transition">
                    <i class="fa-solid fa-check"></i> SSL Shopper
                </a>
                <a href="https://crt.sh/" target="_blank"
                   class="inline-flex items-center gap-2 bg-white hover:bg-stone-50 border border-[#E6D5B8] text-[#5A4A3A] px-4 py-2 rounded-xl text-xs font-medium transition">
                    <i class="fa-solid fa-certificate"></i> crt.sh
                </a>
            </div>
        </section>

    </main>

    <?php include __DIR__ . '/../../includes/rodape.php'; ?>
</body>
</html>