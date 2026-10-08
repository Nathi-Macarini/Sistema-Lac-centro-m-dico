<?php
require_once __DIR__ . '/../bootstrap.php';
// Cabeçalho/rodapé comuns das páginas da visão do médico
function cabecalho($titulo, $ativo = '', $embed = false) {
    $nome = $_SESSION['nome_usuario'] ?? 'Médico';
    $link = function ($arquivo, $rotulo, $chave) use ($ativo) {
        $cls = $ativo === $chave ? 'text-[#8C6D36] border-b-2 border-[#8C6D36] pb-1 font-semibold' : 'hover:text-[#8C6D36] transition';
        return "<a href=\"$arquivo\" class=\"$cls\">$rotulo</a>";
    };
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($titulo); ?> - LAC Centro Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen">
<?php if (!$embed): ?>
<header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <img src="../logo_lac.png" alt="LAC Centro Médico" class="h-10 w-auto object-contain">
            <span class="hidden sm:inline text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-2.5 py-1 rounded-full uppercase tracking-wider">Área do Médico</span>
        </div>
        <nav class="hidden md:flex space-x-8 font-medium text-stone-600">
            <?php echo $link('dashboard.php', 'Minha agenda', 'agenda'); ?>
            <?php echo $link('pacientes.php', 'Pacientes', 'pacientes'); ?>
        </nav>
        <div class="flex items-center space-x-4">
            <span class="text-sm font-medium text-stone-700 hidden sm:inline"><?php echo h($nome); ?></span>
            <a href="../logout.php" class="text-stone-400 hover:text-amber-700 transition" title="Sair"><i class="fa-solid fa-right-from-bracket text-lg"></i></a>
        </div>
    </div>
</header>
<main class="max-w-7xl mx-auto px-4 py-8 space-y-8">
<?php else: ?>
<main class="px-3 py-3 space-y-6">
<?php endif; ?>
<?php }

function rodape() { ?>
</main>
</body>
</html>
<?php } ?>