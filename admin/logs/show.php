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
$id = (int)($_GET['id'] ?? 0);

$stmt = Database::conn()->prepare("SELECT * FROM activity_logs WHERE id = ?");
$stmt->execute([$id]);
$log = $stmt->fetch();

if (!$log) {
    http_response_code(404);
    header("Location: index.php");
    exit;
}

$metadata = $log['metadata'] ? json_decode($log['metadata'], true) : null;
$tags = $log['tags'] ? explode(',', $log['tags']) : [];

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
    return "<span class=\"inline-block px-3 py-1.5 rounded-full text-xs font-medium border $cls\">" . htmlspecialchars(TiposAtendimento::label($tag)) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhe do Log #<?php echo (int)$log['id']; ?> - LAC Centro Médico</title>
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
                <a href="index.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition">
                    <i class="fa-solid fa-arrow-left"></i> <span class="hidden sm:inline">Voltar aos Logs</span>
                </a>
                <span class="text-sm font-medium text-stone-700 hidden sm:inline"><?php echo htmlspecialchars($nomeAdmin); ?></span>
                <a href="../../logout.php" class="text-stone-400 hover:text-amber-700 transition" title="Sair">
                    <i class="fa-solid fa-right-from-bracket text-lg"></i>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Cabeçalho -->
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <span class="text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-3 py-1 rounded-full uppercase tracking-wider">
                        Log #<?php echo (int)$log['id']; ?>
                    </span>
                    <h1 class="text-2xl font-serif text-[#3E352E] mt-3"><?php echo htmlspecialchars($log['description'] ?? 'Sem descrição'); ?></h1>
                    <p class="text-sm text-stone-500 mt-2">
                        <i class="fa-regular fa-clock text-[#8C6D36] mr-1"></i>
                        <?php echo date('d/m/Y \à\s H:i:s', strtotime($log['created_at'])); ?>
                    </p>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-[#F9F4EC] border border-[#E6D5B8] flex items-center justify-center text-[#8C6D36] text-xl shrink-0">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
            </div>

            <?php if ($tags): ?>
                <div class="mt-4 pt-4 border-t border-[#E6D5B8]/40">
                    <div class="text-xs font-bold text-stone-600 uppercase tracking-wider mb-2">Tags</div>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($tags as $t): ?>
                            <?php echo badgeTag(trim($t)); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <!-- Detalhes -->
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <h2 class="text-lg font-serif text-[#3E352E] mb-4 flex items-center gap-2">
                <i class="fa-solid fa-circle-info text-[#8C6D36]"></i> Informações
            </h2>

            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-y-4 gap-x-6 text-sm">
                <div>
                    <dt class="text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Usuário</dt>
                    <dd class="text-stone-800 font-medium"><?php echo htmlspecialchars($log['user_name'] ?? '—'); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Papel</dt>
                    <dd class="text-stone-800"><?php echo htmlspecialchars($log['user_role'] ?? '—'); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Ação</dt>
                    <dd><span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold bg-[#F9F4EC] text-[#8C6D36] border border-[#E6D5B8]"><?php echo htmlspecialchars($log['action']); ?></span></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Entidade</dt>
                    <dd class="text-stone-800">
                        <?php echo htmlspecialchars($log['entity'] ?? '—'); ?>
                        <?php if ($log['entity_id']): ?><span class="text-stone-400">#<?php echo (int)$log['entity_id']; ?></span><?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">IP</dt>
                    <dd class="text-stone-800 font-mono text-xs"><?php echo htmlspecialchars($log['ip_address'] ?? '—'); ?></dd>
                </div>
                <div class="sm:col-span-1">
                    <dt class="text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">User Agent</dt>
                    <dd class="text-stone-500 text-xs break-words"><?php echo htmlspecialchars($log['user_agent'] ?? '—'); ?></dd>
                </div>
            </dl>
        </section>

        <!-- Metadata -->
        <?php if ($metadata): ?>
            <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
                <h2 class="text-lg font-serif text-[#3E352E] mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-code text-[#8C6D36]"></i> Metadata
                </h2>
                <pre class="bg-[#3E352E] text-[#F4E8D1] p-5 rounded-xl text-xs overflow-x-auto leading-relaxed"><?php echo htmlspecialchars(json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></pre>
            </section>
        <?php endif; ?>

        <!-- Ação de voltar -->
        <div class="flex justify-start">
            <a href="index.php" class="inline-flex items-center gap-2 bg-white border border-stone-300 hover:border-[#8C6D36] text-stone-700 hover:text-[#8C6D36] px-5 py-2.5 rounded-xl text-sm font-medium transition">
                <i class="fa-solid fa-arrow-left"></i> Voltar aos Logs
            </a>
        </div>

    </main>
</body>
</html>