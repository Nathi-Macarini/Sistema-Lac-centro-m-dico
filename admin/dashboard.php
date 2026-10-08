<?php
require_once __DIR__ . '/../bootstrap.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
$nomeAdmin = $_SESSION['nome_usuario'] ?? 'Administrador';

// Estatísticas rápidas (opcional, mas dá vida ao painel)
try {
    $pdo = \App\Database::conn();
    $stats = [
        'usuarios'  => (int) $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
        'medicos'   => (int) $pdo->query("SELECT COUNT(*) FROM medicos")->fetchColumn(),
        'consultas' => (int) $pdo->query("SELECT COUNT(*) FROM consultas WHERE status IN ('Agendada','Em andamento')")->fetchColumn(),
        'logs_hoje' => (int) $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
    ];
} catch (\Throwable $e) {
    $stats = ['usuarios' => '—', 'medicos' => '—', 'consultas' => '—', 'logs_hoje' => '—'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - LAC Centro Médico</title>
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
                <a href="../index.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition">
                    <i class="fa-solid fa-house"></i> <span class="hidden sm:inline">Voltar ao Site</span>
                </a>
                <span class="text-sm font-medium text-stone-700 hidden sm:inline"><?php echo htmlspecialchars($nomeAdmin); ?></span>
                <a href="../logout.php" class="text-stone-400 hover:text-amber-700 transition" title="Sair">
                    <i class="fa-solid fa-right-from-bracket text-lg"></i>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        <!-- Hero / Boas-vindas -->
        <section class="bg-gradient-to-br from-[#3E352E] to-[#5A4A3A] text-white p-8 rounded-3xl shadow-xl border border-[#C5A059]/30 relative overflow-hidden">
            <div class="absolute -right-10 -top-10 w-48 h-48 bg-[#C5A059]/10 rounded-full blur-3xl"></div>
            <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-[#C5A059]/5 rounded-full blur-3xl"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <span class="inline-block text-xs font-bold text-[#F4E8D1] bg-white/10 border border-white/20 px-3 py-1.5 rounded-full uppercase tracking-wider mb-3">
                        <i class="fa-solid fa-shield-halved text-[#C5A059] mr-1"></i> Administração
                    </span>
                    <h1 class="text-3xl md:text-4xl font-serif text-white">
                        Olá, <?php echo htmlspecialchars($nomeAdmin); ?>.
                    </h1>
                    <p class="text-stone-300 text-sm md:text-base mt-2 max-w-2xl">
                        Bem-vindo ao centro de controle do LAC. Gerencie usuários, médicos, acompanhe atendimentos e audite todas as ações do sistema.
                    </p>
                </div>
                <a href="logs/index.php"
                   class="inline-flex items-center gap-2 bg-white text-[#3E352E] hover:bg-[#F4E8D1] font-medium px-5 py-3 rounded-xl shadow-lg transition whitespace-nowrap">
                    <i class="fa-solid fa-clipboard-list text-[#8C6D36]"></i> Ver Logs Recentes
                </a>
            </div>
        </section>

        <!-- Estatísticas -->
        <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-[#F9F4EC] border border-[#E6D5B8] flex items-center justify-center text-[#8C6D36]">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Usuários</span>
                </div>
                <div class="text-3xl font-serif text-[#3E352E]"><?php echo $stats['usuarios']; ?></div>
                <div class="text-xs text-stone-500 mt-1">pacientes cadastrados</div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-[#F9F4EC] border border-[#E6D5B8] flex items-center justify-center text-[#8C6D36]">
                        <i class="fa-solid fa-user-doctor"></i>
                    </div>
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Médicos</span>
                </div>
                <div class="text-3xl font-serif text-[#3E352E]"><?php echo $stats['medicos']; ?></div>
                <div class="text-xs text-stone-500 mt-1">profissionais ativos</div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-700">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Consultas</span>
                </div>
                <div class="text-3xl font-serif text-[#3E352E]"><?php echo $stats['consultas']; ?></div>
                <div class="text-xs text-stone-500 mt-1">agendadas / em andamento</div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-700">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Hoje</span>
                </div>
                <div class="text-3xl font-serif text-[#3E352E]"><?php echo $stats['logs_hoje']; ?></div>
                <div class="text-xs text-stone-500 mt-1">ações registradas</div>
            </div>
        </section>

        <!-- Ações Rápidas — Cadastros -->
        <section>
            <div class="flex items-center gap-3 mb-4">
                <div class="h-px flex-1 bg-[#E6D5B8]/60"></div>
                <span class="text-xs font-bold text-[#8C6D36] uppercase tracking-wider">
                    <i class="fa-solid fa-plus-circle mr-1"></i> Cadastros
                </span>
                <div class="h-px flex-1 bg-[#E6D5B8]/60"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <a href="cadastrar_usuario.php"
                   class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm hover:shadow-lg hover:border-[#8C6D36] transition group">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-[#F9F4EC] border border-[#E6D5B8] flex items-center justify-center text-[#8C6D36] group-hover:bg-[#8C6D36] group-hover:text-white transition shrink-0">
                            <i class="fa-solid fa-user-plus text-lg"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-serif text-lg text-[#3E352E] flex items-center justify-between">
                                Cadastrar Usuário
                                <i class="fa-solid fa-arrow-right text-[#C5A059] text-sm opacity-0 group-hover:opacity-100 transition"></i>
                            </h3>
                            <p class="text-sm text-stone-500 mt-1">Novo paciente com dados completos, contato e endereço.</p>
                        </div>
                    </div>
                </a>

                <a href="cadastrar_medico.php"
                   class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm hover:shadow-lg hover:border-[#8C6D36] transition group">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-[#F9F4EC] border border-[#E6D5B8] flex items-center justify-center text-[#8C6D36] group-hover:bg-[#8C6D36] group-hover:text-white transition shrink-0">
                            <i class="fa-solid fa-user-doctor text-lg"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-serif text-lg text-[#3E352E] flex items-center justify-between">
                                Cadastrar Médico
                                <i class="fa-solid fa-arrow-right text-[#C5A059] text-sm opacity-0 group-hover:opacity-100 transition"></i>
                            </h3>
                            <p class="text-sm text-stone-500 mt-1">Novo profissional com CRM, especialidade e e-mail.</p>
                        </div>
                    </div>
                </a>
            </div>
        </section>

        <!-- Ações Rápidas — Gerenciamento -->
        <section>
            <div class="flex items-center gap-3 mb-4">
                <div class="h-px flex-1 bg-[#E6D5B8]/60"></div>
                <span class="text-xs font-bold text-[#8C6D36] uppercase tracking-wider">
                    <i class="fa-solid fa-list-check mr-1"></i> Gerenciamento
                </span>
                <div class="h-px flex-1 bg-[#E6D5B8]/60"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <a href="listar_usuarios.php"
                   class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm hover:shadow-lg hover:border-[#8C6D36] transition group">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-[#F9F4EC] border border-[#E6D5B8] flex items-center justify-center text-[#8C6D36] group-hover:bg-[#8C6D36] group-hover:text-white transition shrink-0">
                            <i class="fa-solid fa-users text-lg"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-serif text-lg text-[#3E352E] flex items-center justify-between">
                                Usuários
                                <i class="fa-solid fa-arrow-right text-[#C5A059] text-sm opacity-0 group-hover:opacity-100 transition"></i>
                            </h3>
                            <p class="text-sm text-stone-500 mt-1">Listar, editar e gerenciar todos os pacientes.</p>
                        </div>
                    </div>
                </a>

                <a href="listar_medicos.php"
                   class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm hover:shadow-lg hover:border-[#8C6D36] transition group">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-[#F9F4EC] border border-[#E6D5B8] flex items-center justify-center text-[#8C6D36] group-hover:bg-[#8C6D36] group-hover:text-white transition shrink-0">
                            <i class="fa-solid fa-stethoscope text-lg"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-serif text-lg text-[#3E352E] flex items-center justify-between">
                                Médicos
                                <i class="fa-solid fa-arrow-right text-[#C5A059] text-sm opacity-0 group-hover:opacity-100 transition"></i>
                            </h3>
                            <p class="text-sm text-stone-500 mt-1">Listar, editar e gerenciar todos os profissionais.</p>
                        </div>
                    </div>
                </a>
            </div>
        </section>

        <!-- Ações Rápidas — Sistema -->
        <section>
            <div class="flex items-center gap-3 mb-4">
                <div class="h-px flex-1 bg-[#E6D5B8]/60"></div>
                <span class="text-xs font-bold text-[#8C6D36] uppercase tracking-wider">
                    <i class="fa-solid fa-gear mr-1"></i> Sistema
                </span>
                <div class="h-px flex-1 bg-[#E6D5B8]/60"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <a href="logs/index.php"
                   class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm hover:shadow-lg hover:border-[#8C6D36] transition group">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-[#F9F4EC] border border-[#E6D5B8] flex items-center justify-center text-[#8C6D36] group-hover:bg-[#8C6D36] group-hover:text-white transition shrink-0">
                            <i class="fa-solid fa-clipboard-list text-lg"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-serif text-lg text-[#3E352E] flex items-center justify-between">
                                Log de Atividades
                                <i class="fa-solid fa-arrow-right text-[#C5A059] text-sm opacity-0 group-hover:opacity-100 transition"></i>
                            </h3>
                            <p class="text-sm text-stone-500 mt-1">Auditoria completa de logins, atendimentos e ações do sistema.</p>
                        </div>
                    </div>
                </a>
            </div>
        </section>

    </main>
</body>
</html>