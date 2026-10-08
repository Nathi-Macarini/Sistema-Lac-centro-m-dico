<?php
session_start();
require __DIR__ . '/../conexao.php';

// Só o administrador cadastra médicos
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$erro = '';
$especialidades = ['Cardiologia', 'Dermatologia', 'Ortopedia', 'Ginecologia', 'Clínica Geral'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome          = trim($_POST['nome'] ?? '');
    $crm           = trim($_POST['crm'] ?? '');
    $especialidade = trim($_POST['especialidade'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $telefone      = trim($_POST['telefone'] ?? '');
    $senha         = trim($_POST['senha'] ?? '');

    if ($nome === '' || $crm === '' || $especialidade === '' || $email === '' || $senha === '') {
        $erro = "Preencha Nome, CRM, Especialidade, E-mail e Senha.";
    } elseif (strlen($senha) < 6) {
        $erro = "A senha temporária deve ter pelo menos 6 caracteres.";
    } else {
        // CRM e e-mail não podem repetir na tabela medicos
        $st = $conn->prepare("SELECT id FROM medicos WHERE crm = ? OR email = ?");
        $st->bind_param("ss", $crm, $email);
        $st->execute();
        $st->store_result();

        if ($st->num_rows > 0) {
            $erro = "Já existe um médico com esse CRM ou e-mail.";
        } else {
            // Tudo do médico fica na tabela medicos (a senha vai criptografada)
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO medicos (nome, crm, especialidade, email, telefone, senha) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->bind_param("ssssss", $nome, $crm, $especialidade, $email, $telefone, $hash);

            if ($ins->execute()) {
                header("Location: cadastrar_medico.php?sucesso=1");
                exit();
            }
            $erro = "Erro ao salvar no banco de dados: " . $ins->error;
        }
    }
}

$campo = 'w-full px-4 py-3 rounded-xl border border-stone-300 focus:outline-none focus:border-[#8C6D36] text-sm bg-[#FAF9F6]';
$rotulo = 'block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Médico - LAC Centro Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen p-6 flex items-center justify-center">

<div class="max-w-xl w-full bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-6">
    <div class="flex items-center justify-between border-b border-stone-200 pb-4">
        <div>
            <h1 class="text-2xl font-serif text-[#3E352E]">Cadastrar Médico</h1>
            <p class="text-xs text-stone-500 mt-0.5">O médico poderá entrar no sistema com o e-mail e a senha cadastrados aqui</p>
        </div>
        <a href="dashboard.php" class="text-xs text-stone-500 hover:text-[#8C6D36] font-medium transition flex items-center">
            <i class="fa-solid fa-arrow-left mr-1"></i> Voltar
        </a>
    </div>

    <?php if (isset($_GET['sucesso'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm flex items-center">
            <i class="fa-solid fa-circle-check mr-2 text-base"></i>
            <span>Médico cadastrado! Ele já pode entrar pela tela de login. <a href="listar_medicos.php" class="underline font-medium">Ver médicos cadastrados</a></span>
        </div>
    <?php endif; ?>

    <?php if ($erro !== ''): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm flex items-center">
            <i class="fa-solid fa-circle-exclamation mr-2 text-base"></i> <?php echo h($erro); ?>
        </div>
    <?php endif; ?>

    <form action="cadastrar_medico.php" method="POST" class="space-y-4">
        <div>
            <label class="<?php echo $rotulo; ?>">Nome completo</label>
            <input type="text" name="nome" required value="<?php echo h($_POST['nome'] ?? ''); ?>" class="<?php echo $campo; ?>">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="<?php echo $rotulo; ?>">CRM</label>
                <input type="text" name="crm" required placeholder="Ex: 12345/PR" value="<?php echo h($_POST['crm'] ?? ''); ?>" class="<?php echo $campo; ?>">
            </div>
            <div>
                <label class="<?php echo $rotulo; ?>">Especialidade</label>
                <select name="especialidade" required class="<?php echo $campo; ?>">
                    <?php foreach ($especialidades as $e): ?>
                        <option value="<?php echo h($e); ?>" <?php echo (($_POST['especialidade'] ?? '') === $e) ? 'selected' : ''; ?>><?php echo h($e); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="<?php echo $rotulo; ?>">E-mail (login)</label>
                <input type="email" name="email" required value="<?php echo h($_POST['email'] ?? ''); ?>" class="<?php echo $campo; ?>">
            </div>
            <div>
                <label class="<?php echo $rotulo; ?>">Telefone / Celular</label>
                <input type="text" name="telefone" placeholder="(41) 99999-9999" value="<?php echo h($_POST['telefone'] ?? ''); ?>" class="<?php echo $campo; ?>">
            </div>
        </div>

        <div>
            <label class="<?php echo $rotulo; ?>">Senha temporária (mínimo 6 caracteres)</label>
            <input type="text" name="senha" required minlength="6" class="<?php echo $campo; ?>">
        </div>

        <button type="submit" class="w-full bg-[#3E352E] text-white py-3 rounded-xl font-medium hover:bg-[#5A4A3A] transition shadow-sm text-sm">
            Cadastrar Médico
        </button>
    </form>
</div>

</body>
</html>