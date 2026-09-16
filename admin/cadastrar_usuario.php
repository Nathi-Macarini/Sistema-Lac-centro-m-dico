<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'lac_centro_medico');
if ($conn->connect_error) die("Erro de conexão: " . $conn->connect_error);

$msg = ""; $tipo = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $cpf      = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $nasc     = $_POST['data_nascimento'] ?? '';
    $endereco = trim($_POST['endereco'] ?? '');

    if ($nome === '' || $telefone === '' || $nasc === '' || $endereco === '') {
        $msg = "Preencha todos os campos obrigatórios."; $tipo = "erro";
    } elseif ($email === '' && $cpf === '') {
        $msg = "Informe pelo menos E-mail ou CPF."; $tipo = "erro";
    } else {
        // Verifica duplicidade
        $dup = false;
        if ($cpf !== '') {
            $r = $conn->query("SELECT id FROM usuarios WHERE cpf = '$cpf'");
            if ($r && $r->num_rows > 0) $dup = true;
        }
        if ($email !== '') {
            $r = $conn->query("SELECT id FROM usuarios WHERE email = '$email'");
            if ($r && $r->num_rows > 0) $dup = true;
        }

        if ($dup) {
            $msg = "Já existe um usuário com esse e-mail ou CPF."; $tipo = "erro";
        } else {
            // Primeira senha = CPF (se não tiver CPF, usa padrão)
            $senhaInicial = $cpf !== '' ? $cpf : '123456';

            $emailEsc = $email !== '' ? "'" . $conn->real_escape_string($email) . "'" : "NULL";
            $cpfEsc   = $cpf !== ''   ? "'" . $conn->real_escape_string($cpf) . "'"   : "NULL";

            $sql = "INSERT INTO usuarios (nome, email, cpf, senha, telefone, data_nascimento, endereco, tipo)
                    VALUES (
                        '" . $conn->real_escape_string($nome) . "',
                        $emailEsc,
                        $cpfEsc,
                        '" . $conn->real_escape_string($senhaInicial) . "',
                        '" . $conn->real_escape_string($telefone) . "',
                        '" . $conn->real_escape_string($nasc) . "',
                        '" . $conn->real_escape_string($endereco) . "',
                        'comum'
                    )";

            if ($conn->query($sql)) {
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
            <img src="../logo_lac.png" alt="LAC" class="h-10 w-auto object-contain">
            <a href="dashboard.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition">
                <i class="fa-solid fa-arrow-left"></i> Voltar ao Painel
            </a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-8">
        <section class="bg-white p-8 rounded-3xl border border-[#E6D5B8]/40 shadow-xl space-y-6">
            <div>
                <h1 class="text-2xl font-serif text-[#3E352E]">Cadastrar Novo Usuário</h1>
                <p class="text-sm text-stone-500 mt-1">Preencha todos os dados. A senha inicial será o <strong>CPF</strong> (somente números).</p>
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

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">E-mail</label>
                        <input type="email" name="email"
                               class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]"
                               placeholder="seu@email.com">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">CPF</label>
                        <input type="text" name="cpf" maxlength="14"
                               class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]"
                               placeholder="000.000.000-00">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">Telefone *</label>
                        <input type="text" name="telefone" required
                               class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]"
                               placeholder="(00) 00000-0000">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">Data de Nascimento *</label>
                        <input type="date" name="data_nascimento" required
                               class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">Endereço Completo *</label>
                        <input type="text" name="endereco" required
                               class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]"
                               placeholder="Rua, número, bairro, cidade - UF">
                    </div>
                </div>

                <div class="bg-[#F9F4EC] border border-[#E6D5B8] p-3.5 rounded-xl text-xs text-[#5A4A3A] flex items-start gap-2">
                    <i class="fa-solid fa-circle-info mt-0.5"></i>
                    <span><strong>Importante:</strong> E-mail <em>ou</em> CPF devem ser preenchidos. Se o CPF for informado, ele será a senha inicial (apenas números).</span>
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