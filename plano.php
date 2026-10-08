<?php
require_once __DIR__ . '/bootstrap.php'; 
require __DIR__ . '/conexao.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit; }

$uid         = (int)$_SESSION['usuario_id'];
$tipoUsuario = $_SESSION['tipo_usuario'] ?? 'comum';

$st = $conn->prepare("SELECT * FROM planos WHERE usuario_id = ? ORDER BY criado_em DESC");
$st->bind_param('i', $uid);
$st->execute();
$planos = $st->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Plano - LAC Centro Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen">

<header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-40 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
        <a href="index.php"><img src="logo_lac.png" alt="LAC Centro Médico" class="h-10 w-auto object-contain"></a>
        <nav class="hidden md:flex space-x-8 font-medium text-stone-600">
            <a href="index.php" class="hover:text-[#8C6D36] transition">Início</a>
            <a href="historico.php" class="hover:text-[#8C6D36] transition">Histórico</a>
            <a href="plano.php" class="text-[#8C6D36] border-b-2 border-[#8C6D36] pb-1 font-semibold">Plano</a>
            <a href="dependentes.php" class="hover:text-[#8C6D36] transition">Dependentes</a>
        </nav>
        <div class="flex items-center space-x-4">
            <?php if ($tipoUsuario === 'admin'): ?>
                <a href="admin/dashboard.php" class="hidden sm:flex items-center gap-2 bg-[#F9F4EC] text-[#8C6D36] border border-[#E6D5B8] px-3.5 py-2 rounded-xl text-sm font-semibold hover:bg-[#E6D5B8]/50 transition">
                    <i class="fa-solid fa-shield-halved"></i> Painel Admin
                </a>
            <?php endif; ?>
            <a href="perfil.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition hidden sm:inline"><?php echo h($_SESSION['nome_usuario']); ?></a>
            <a href="logout.php" class="text-stone-400 hover:text-amber-700 transition" title="Sair"><i class="fa-solid fa-right-from-bracket text-lg"></i></a>
        </div>
    </div>
</header>

<main class="max-w-4xl mx-auto px-4 py-8 space-y-6">

    <section class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-2">
        <h1 class="text-3xl font-serif text-[#3E352E]">Meu Plano de Saúde</h1>
        <p class="text-stone-600 text-sm">Dados do seu plano cadastrados pela recepção do LAC Centro Médico.</p>
    </section>

    <?php if (!$planos): ?>
        <section class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm text-center space-y-4">
            <i class="fa-solid fa-id-card text-4xl text-stone-300"></i>
            <p class="text-stone-600">Você ainda não possui um plano de saúde cadastrado.</p>
            <p class="text-sm text-stone-500">Se você tem um plano, entre em contato com a recepção para cadastrá-lo, ou fale pelo WhatsApp.</p>
            <a href="https://wa.me/5541999999999" target="_blank" class="inline-flex items-center gap-2 bg-[#008069] hover:bg-[#006e5a] text-white font-medium px-5 py-3 rounded-xl transition shadow-sm">
                <i class="fa-brands fa-whatsapp text-lg"></i> Falar com a recepção
            </a>
        </section>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($planos as $i => $p): ?>
                <section class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-5">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-[#F9F4EC] border border-[#E6D5B8] flex items-center justify-center text-[#8C6D36] text-xl">
                                <i class="fa-solid fa-id-card"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-serif text-[#3E352E]"><?php echo h($p['operadora']); ?></h2>
                                <?php if ($i === 0): ?>
                                    <span class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full">Plano atual</span>
                                <?php else: ?>
                                    <span class="text-xs font-bold text-stone-500 bg-stone-100 px-2.5 py-0.5 rounded-full">Histórico</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-stone-100 text-sm">
                        <div>
                            <dt class="text-xs uppercase font-bold text-stone-500">Número da carteirinha</dt>
                            <dd class="text-stone-800 font-mono"><?php echo h($p['numero_carteirinha'] ?: '—'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase font-bold text-stone-500">Validade</dt>
                            <dd class="text-stone-800">
                                <?php if ($p['validade']): ?>
                                    <?php echo date('d/m/Y', strtotime($p['validade'])); ?>
                                    <?php
                                        $diasRestantes = (strtotime($p['validade']) - time()) / 86400;
                                        if ($diasRestantes < 0) {
                                            echo '<span class="ml-2 text-xs font-bold text-red-700 bg-red-50 border border-red-200 px-2 py-0.5 rounded-full">Vencido</span>';
                                        } elseif ($diasRestantes < 30) {
                                            echo '<span class="ml-2 text-xs font-bold text-amber-800 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">Vence em breve</span>';
                                        }
                                    ?>
                                <?php else: ?>—<?php endif; ?>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase font-bold text-stone-500">Acomodação</dt>
                            <dd class="text-stone-800"><?php echo h($p['acomodacao'] ?: '—'); ?></dd>
                        </div>
                    </dl>

                    <?php if (!empty($p['observacoes'])): ?>
                        <div class="bg-[#F9F4EC] border border-[#E6D5B8] rounded-xl p-4 text-sm text-[#5A4A3A]">
                            <strong>Observações:</strong> <?php echo nl2br(h($p['observacoes'])); ?>
                        </div>
                    <?php endif; ?>

                    <p class="text-xs text-stone-400 pt-2 border-t border-stone-100">
                        Cadastrado em <?php echo date('d/m/Y', strtotime($p['criado_em'])); ?>
                    </p>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>
</body>
</html>