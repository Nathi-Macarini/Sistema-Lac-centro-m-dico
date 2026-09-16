<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
$nomeAdmin = $_SESSION['nome_usuario'] ?? 'Administrador';
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

    <main class="max-w-7xl mx-auto px-4 py-8 space-y-8">

        <!-- Saudação -->
        <section class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <h1 class="text-3xl font-serif text-[#3E352E] mb-2">
                Olá, <span><?php echo htmlspecialchars($nomeAdmin); ?></span>.
            </h1>
            <p class="text-stone-600">Bem-vindo ao painel administrativo. Gerencie usuários, médicos e consultas da clínica.</p>
        </section>

        <!-- Cards de Ação -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

            <a href="cadastrar_usuario.php"
               class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm hover:shadow-lg hover:border-[#8C6D36] transition group">
                <div class="w-12 h-12 rounded-2xl bg-[#F9F4EC] border border-[#E6D5B8] flex items-center justify-center text-[#8C6D36] mb-4 group-hover:bg-[#8C6D36] group-hover:text-white transition">
                    <i class="fa-solid fa-user-plus text-lg"></i>
                </div>
                <h3 class="font-serif text-lg text-[#3E352E]">Cadastrar Usuário</h3>
                <p class="text-sm text-stone-500 mt-1">Novo paciente com dados completos</p>
            </a>

            <a href="cadastrar_medico.php"
               class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm hover:shadow-lg hover:border-[#8C6D36] transition group">
                <div class="w-12 h-12 rounded-2xl bg-[#F9F4EC] border border-[#E6D5B8] flex items-center justify-center text-[#8C6D36] mb-4 group-hover:bg-[#8C6D36] group-hover:text-white transition">
                    <i class="fa-solid fa-user-doctor text-lg"></i>
                </div>
                <h3 class="font-serif text-lg text-[#3E352E]">Cadastrar Médico</h3>
                <p class="text-sm text-stone-500 mt-1">Novo profissional com CRM e especialidade</p>
            </a>

            <a href="listar_usuarios.php"
               class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm hover:shadow-lg hover:border-[#8C6D36] transition group">
                <div class="w-12 h-12 rounded-2xl bg-[#F9F4EC] border border-[#E6D5B8] flex items-center justify-center text-[#8C6D36] mb-4 group-hover:bg-[#8C6D36] group-hover:text-white transition">
                    <i class="fa-solid fa-users text-lg"></i>
                </div>
                <h3 class="font-serif text-lg text-[#3E352E]">Usuários</h3>
                <p class="text-sm text-stone-500 mt-1">Listar e gerenciar pacientes</p>
            </a>

            <a href="listar_medicos.php"
               class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm hover:shadow-lg hover:border-[#8C6D36] transition group">
                <div class="w-12 h-12 rounded-2xl bg-[#F9F4EC] border border-[#E6D5B8] flex items-center justify-center text-[#8C6D36] mb-4 group-hover:bg-[#8C6D36] group-hover:text-white transition">
                    <i class="fa-solid fa-stethoscope text-lg"></i>
                </div>
                <h3 class="font-serif text-lg text-[#3E352E]">Médicos</h3>
                <p class="text-sm text-stone-500 mt-1">Listar e gerenciar profissionais</p>
            </a>

        </section>
    </main>
</body>
</html>