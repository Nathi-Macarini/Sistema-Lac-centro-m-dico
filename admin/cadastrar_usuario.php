<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../conexao.php';

use App\CPF;

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$nomeAdmin = $_SESSION['nome_usuario'] ?? 'Administrador';

$msg = ""; $tipo = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome'] ?? '');
    $nomeMae  = trim($_POST['nome_mae'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $cpf      = CPF::limpar($_POST['cpf'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $nasc     = $_POST['data_nascimento'] ?? '';
    $endereco = trim($_POST['endereco'] ?? '');

    if ($nome === '' || $nomeMae === '' || $cpf === '' || $telefone === '' || $nasc === '' || $endereco === '') {
        $msg = "Preencha todos os campos obrigatórios, incluindo CPF e Nome da Mãe."; $tipo = "erro";
    } elseif (strlen($cpf) !== 11) {
        $msg = "CPF inválido. Informe os 11 dígitos."; $tipo = "erro";
    } else {
        // ---------- Hash do CPF para busca ----------
        $cpfHash     = CPF::hash($cpf);
        $cpfUltimos4 = CPF::ultimos4($cpf);
        $cpfEnc      = CPF::criptografar($cpf);

        // ---------- Verifica duplicidade ----------
        $dup = false;

        // Por CPF (usa hash)
        $q = $conn->prepare("SELECT id FROM usuarios WHERE cpf_hash = ?");
        $q->bind_param('s', $cpfHash);
        $q->execute();
        if ($q->get_result()->fetch_assoc()) $dup = true;

        // Por e-mail
        if (!$dup && $email !== '') {
            $q = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
            $q->bind_param('s', $email);
            $q->execute();
            if ($q->get_result()->fetch_assoc()) $dup = true;
        }

        if ($dup) {
            $msg = "Já existe um usuário com esse e-mail ou CPF."; $tipo = "erro";
        } else {
            $senhaInicial = $cpf;
            $senhaHash    = password_hash($senhaInicial, PASSWORD_DEFAULT);

            $emailEsc = $email !== '' ? $email : null;
            $nascEsc  = $nasc !== ''  ? $nasc  : null;

            $st = $conn->prepare("
                INSERT INTO usuarios
                    (nome, nome_mae, email, cpf, cpf_hash, cpf_ultimos4, cpf_enc, senha, telefone, data_nascimento, endereco, tipo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'comum')
            ");
            $st->bind_param(
                'sssssssssss',
                $nome, $nomeMae, $emailEsc, $cpf, $cpfHash, $cpfUltimos4, $cpfEnc,
                $senhaHash, $telefone, $nascEsc, $endereco
            );

            if ($st->execute()) {
                $msg = "Usuário cadastrado com sucesso! Senha inicial: <strong>" . htmlspecialchars($senhaInicial) . "</strong>";
                $tipo = "sucesso";
            } else {
                $msg = "Erro ao cadastrar: " . $conn->error; $tipo = "erro";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Usuário - Admin LAC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen">

    <header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="../logo_lac.png" alt="LAC Centro Médico" class="h-10 w-auto object-contain">
                <span class="hidden sm:inline text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-2.5 py-1 rounded-full uppercase tracking-wider">
                    Painel Administrativo
                </span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition">
                    <i class="fa-solid fa-arrow-left"></i> <span class="hidden sm:inline">Voltar ao Painel</span>
                </a>
                <span class="text-sm font-medium text-stone-700 hidden sm:inline"><?php echo htmlspecialchars($nomeAdmin); ?></span>
                <a href="../logout.php" class="text-stone-400 hover:text-amber-700 transition" title="Sair">
                    <i class="fa-solid fa-right-from-bracket text-lg"></i>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-8">
        <section class="bg-white p-8 rounded-3xl border border-[#E6D5B8]/40 shadow-xl space-y-6">
            <div>
                <h1 class="text-2xl font-serif text-[#3E352E]">Cadastrar Novo Usuário</h1>
                <p class="text-sm text-stone-500 mt-1">A senha inicial será o <strong>CPF</strong> (somente números).</p>
            </div>

            <?php if ($msg): ?>
                <div class="<?php echo $tipo==='sucesso' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'; ?> p-3.5 rounded-xl text-sm border">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">Nome Completo *</label>
                        <input type="text" name="nome" required
                               class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">Nome da Mãe *</label>
                        <input type="text" name="nome_mae" required
                               class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">CPF *</label>
                        <input type="text" name="cpf" maxlength="14" required
                               class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]"
                               placeholder="000.000.000-00">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">E-mail</label>
                        <input type="email" name="email"
                               class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]"
                               placeholder="seu@email.com">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">Telefone *</label>
                        <input type="text" name="telefone" required
                               class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">Data de Nascimento *</label>
                        <input type="date" name="data_nascimento" required
                               class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">Endereço Completo *</label>
                        <input type="text" name="endereco" required
                               class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                    </div>
                </div>

                <div class="bg-[#F9F4EC] border border-[#E6D5B8] p-3.5 rounded-xl text-xs text-[#5A4A3A] flex items-start gap-2">
                    <i class="fa-solid fa-shield-halved mt-0.5"></i>
                    <span><strong>LGPD:</strong> O CPF é armazenado em formato criptografado. A senha inicial é o CPF (somente números).</span>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="dashboard.php" class="px-5 py-3 border border-stone-300 text-stone-700 rounded-xl text-sm font-medium hover:bg-stone-50">Cancelar</a>
                    <button type="submit" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-6 py-3 rounded-xl text-sm font-medium transition shadow-sm">
                        <i class="fa-solid fa-user-plus mr-1"></i> Cadastrar Usuário
                    </button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>