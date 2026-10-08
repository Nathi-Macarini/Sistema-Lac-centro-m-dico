<?php
require_once __DIR__ . '/bootstrap.php'; 
require __DIR__ . '/conexao.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit; }

$uid         = (int)$_SESSION['usuario_id'];
$tipoUsuario = $_SESSION['tipo_usuario'] ?? 'comum';

$st = $conn->prepare("SELECT * FROM dependentes WHERE titular_id = ? ORDER BY nome ASC");
$st->bind_param('i', $uid);
$st->execute();
$dependentes = $st->get_result()->fetch_all(MYSQLI_ASSOC);

function idadeDe($data) {
    if (!$data) return null;
    try { return (new DateTime($data))->diff(new DateTime('today'))->y; }
    catch (Exception $e) { return null; }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Dependentes - LAC Centro Médico</title>
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
            <a href="plano.php" class="hover:text-[#8C6D36] transition">Plano</a>
            <a href="dependentes.php" class="text-[#8C6D36] border-b-2 border-[#8C6D36] pb-1 font-semibold">Dependentes</a>
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
        <h1 class="text-3xl font-serif text-[#3E352E]">Meus Dependentes</h1>
        <p class="text-stone-600 text-sm">Familiares vinculados ao seu cadastro pelo LAC Centro Médico.</p>
    </section>

    <?php if (!$dependentes): ?>
        <section class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm text-center space-y-4">
            <i class="fa-solid fa-users text-4xl text-stone-300"></i>
            <p class="text-stone-600">Você ainda não possui dependentes cadastrados.</p>
            <p class="text-sm text-stone-500">Se precisar incluir um dependente, entre em contato com a recepção.</p>
            <a href="https://wa.me/5541999999999" target="_blank" class="inline-flex items-center gap-2 bg-[#008069] hover:bg-[#006e5a] text-white font-medium px-5 py-3 rounded-xl transition shadow-sm">
                <i class="fa-brands fa-whatsapp text-lg"></i> Falar com a recepção
            </a>
        </section>
    <?php else: ?>
        <section class="bg-white rounded-2xl border border-[#E6D5B8]/40 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F9F4EC] text-[#5A4A3A] text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left">Nome</th>
                            <th class="px-4 py-3 text-left">Parentesco</th>
                            <th class="px-4 py-3 text-left">Nascimento</th>
                            <th class="px-4 py-3 text-left">Idade</th>
                            <th class="px-4 py-3 text-left">CPF</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php foreach ($dependentes as $d): $idade = idadeDe($d['data_nascimento']); ?>
                            <tr class="hover:bg-[#FAF9F6]">
                                <td class="px-4 py-3 font-medium text-stone-800"><?php echo h($d['nome']); ?></td>
                                <td class="px-4 py-3">
                                    <span class="text-xs font-bold bg-[#F9F4EC] text-[#8C6D36] border border-[#E6D5B8] px-2.5 py-1 rounded-full">
                                        <?php echo h($d['parentesco']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-stone-600">
                                    <?php echo $d['data_nascimento'] ? date('d/m/Y', strtotime($d['data_nascimento'])) : '—'; ?>
                                </td>
                                <td class="px-4 py-3 text-stone-600"><?php echo $idade !== null ? $idade . ' ' . ($idade === 1 ? 'ano' : 'anos') : '—'; ?></td>
                                <td class="px-4 py-3 text-stone-600 font-mono text-xs">
                                    <?php echo $d['cpf'] ? h(formatarCpf($d['cpf'])) : '—'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <p class="text-xs text-stone-500 text-center">
            <i class="fa-solid fa-circle-info"></i>
            Os dependentes são cadastrados pela recepção. Para agendar uma consulta em nome de um dependente, fale com a recepção pelo WhatsApp.
        </p>
    <?php endif; ?>

</main>
</body>
</html>